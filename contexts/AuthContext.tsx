import React, { createContext, useState, useEffect, ReactNode, useCallback, useMemo } from 'react';
import { supabase } from '../services/supabaseClient';
import { Session, User } from '@supabase/supabase-js';
import { Profile } from '../types';
export interface AuthContextType {
  currentUser: Profile | null;
  login: (email: string, password: string) => Promise<{ user: User | null; success: boolean; error: string | null; }>;
  logout: () => Promise<void>;
  loading: boolean;
}

export const AuthContext = createContext<AuthContextType | null>(null);

export const AuthProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [currentUser, setCurrentUser] = useState<Profile | null>(null);
  const [loading, setLoading] = useState(true);

  const fetchUserProfile = useCallback(async (user: User | null): Promise<Profile | null> => {
    if (!user) return null;
    try {
      const { data: profile, error } = await supabase
        .from('profiles')
        .select('*')
        .eq('id', user.id)
        .single();
      
      if (error) {
        console.error('Error fetching user profile, signing out:', error);
        await supabase.auth.signOut();
        return null;
      }
      return profile;
    } catch (e: any) {
      console.error('Exception in fetchUserProfile, signing out:', e);
      await supabase.auth.signOut();
      return null;
    }
  }, []);

  useEffect(() => {
    // onAuthStateChange is the single source of truth. It fires on initial load
    // with the current session, and again whenever the auth state changes.
    // This is the most robust pattern and is resilient to Strict Mode.
    const { data: { subscription } } = supabase.auth.onAuthStateChange(async (_event, session) => {
      try {
        const profile = await fetchUserProfile(session?.user ?? null);
        setCurrentUser(profile);
      } catch (e) {
        console.error("Error in onAuthStateChange handler:", e);
        setCurrentUser(null);
      } finally {
        // This is the key. It guarantees the loading state is always resolved
        // after the first check, preventing the app from getting stuck.
        setLoading(false);
      }
    });

    return () => {
      subscription?.unsubscribe();
    };
  }, [fetchUserProfile]);

  const login = useCallback(async (email, password) => {
    const { data, error } = await supabase.auth.signInWithPassword({ email, password });
    // The onAuthStateChange listener will handle the user update.
    return { user: data.user, success: !!data.user, error: error?.message || null };
  }, []);

  const logout = useCallback(async () => {
    const userId = currentUser?.id;
    const { error } = await supabase.auth.signOut();

    if (error) {
        console.error("Error signing out:", error.message);
    }

    if (userId) {
      supabase.from('user_sessions').delete().eq('user_id', userId).then(({ error: deleteError }) => {
          if (deleteError) {
              console.warn("Could not clear user_session on logout:", deleteError.message);
          }
      });
    }
  }, [currentUser?.id]);

  const value = useMemo(() => ({
    currentUser,
    login,
    logout,
    loading
  }), [currentUser, login, logout, loading]);

  return (
    <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
  );
};
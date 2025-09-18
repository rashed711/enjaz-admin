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

  // This is a stable function that won't change on re-renders,
  // making it safe to use in the useEffect dependency array.
  const fetchUserProfile = useCallback(async (user: User | null): Promise<Profile | null> => {
    if (!user) return null;
    try {
      const { data: profile, error } = await supabase
        .from('profiles')
        .select('*')
        .eq('id', user.id)
        .single();
      
      if (error) {
        // If the error is because the user doesn't exist in profiles, that's a valid state (e.g., just signed up).
        // For other errors, log them. We shouldn't sign the user out here as it might be a temporary network issue.
        console.error('Error fetching user profile:', error);
        return null;
      }
      return profile;
    } catch (e: any) {
      // This might happen if the network is down.
      console.error('Exception in fetchUserProfile:', e);
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
        // The user is only considered "loaded" and "current" if they have a session AND a profile with a role.
        // Otherwise, they are treated as logged out.
        setCurrentUser(profile && profile.role ? profile : null);
      } catch (e) {
        console.error("Error in onAuthStateChange handler:", e);
        setCurrentUser(null);
      } finally {
        // This is the key. It guarantees the loading state is always resolved
        // after the first check, preventing the app from getting stuck.
        if (loading) {
            setLoading(false);
        }
      }
    });

    return () => {
      subscription?.unsubscribe();
    };
    // The dependency array ensures this effect runs only when fetchUserProfile changes,
    // which it shouldn't because of useCallback.
    // We add `loading` to ensure we can set it to false.
  }, [fetchUserProfile, loading]);

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
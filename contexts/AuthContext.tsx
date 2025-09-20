import React, { createContext, useState, useEffect, ReactNode, useCallback, useMemo } from 'react';
import { supabase } from '../services/supabaseClient';
import { User } from '@supabase/supabase-js';
import { Profile } from '../types';
export interface AuthContextType {
  currentUser: Profile | null;
  login: (email: string, password: string) => Promise<{ user: User | null; success: boolean; error: string | null }>;
  logout: () => Promise<void>;
  loading: boolean;
}

export const AuthContext = createContext<AuthContextType | null>(null);

export const AuthProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [currentUser, setCurrentUser] = useState<Profile | null>(null);
  const [loading, setLoading] = useState(true);

  // This function is memoized, so it's safe to use in the useEffect dependency array.
  // It fetches the user's profile from the 'profiles' table.
  const fetchUserProfile = useCallback(async (user: User | null): Promise<Profile | null> => {
    if (!user) return null;
    try {
      const { data: profile, error } = await supabase
        .from('profiles')
        .select('*')
        .eq('id', user.id)
        .single();
      
      if (error) {
        console.error('Error fetching user profile:', error);
        return null;
      }
      // A user is only valid if they have a profile with a role.
      return profile && profile.role ? profile : null;
    } catch (e: any) {
      console.error('Exception in fetchUserProfile:', e);
      return null;
    }
  }, []);

  useEffect(() => {
    // This effect provides a robust way to handle the session on initial load and subsequent changes,
    // making it resilient to browser-specific behaviors like Chrome's caching.
    let isMounted = true;

    // 1. Proactively fetch the session on initial load.
    const getInitialSession = async () => {
      try {
        const { data: { session }, error } = await supabase.auth.getSession();
        if (error) throw error;

        if (isMounted) {
          const profile = await fetchUserProfile(session?.user ?? null);
          setCurrentUser(profile);
        }
      } catch (e) {
        console.error("AuthContext: Error getting initial session:", e);
        if (isMounted) setCurrentUser(null);
      } finally {
        // 3. Crucially, always set loading to false after the initial check.
        // This prevents the app from getting stuck on a loading screen.
        if (isMounted) setLoading(false);
      }
    };

    getInitialSession();

    // 2. Set up a listener for any *future* changes in auth state.
    // This handles sign-ins, sign-outs from other tabs, token refreshes, etc.
    const { data: { subscription } } = supabase.auth.onAuthStateChange(async (_event, session) => {
      if (isMounted) {
        const profile = await fetchUserProfile(session?.user ?? null);
        setCurrentUser(profile);
      }
    });

    return () => {
      isMounted = false;
      subscription?.unsubscribe();
    };
  }, [fetchUserProfile]);

  const login = useCallback(async (email, password) => {
    const { data, error } = await supabase.auth.signInWithPassword({ email, password });
    // The onAuthStateChange listener will handle the user update.
    return { user: data.user, success: !!data.user, error: error?.message || null };
  }, []);

  const logout = useCallback(async () => {
    // Just sign out. The onAuthStateChange listener will detect the session change
    // and update the currentUser to null, which is a more robust pattern.
    const { error } = await supabase.auth.signOut();

    if (error) {
        console.error("Error signing out:", error.message);
    }
  }, []);

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
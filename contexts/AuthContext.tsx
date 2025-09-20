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
    // Attempt to sign out from the server.
    const { error } = await supabase.auth.signOut();

    if (error) {
        console.error("Error signing out from server:", error.message);
        // If signing out from the server fails (e.g., due to a network error),
        // we can't rely on onAuthStateChange to fire.
        // We must manually set the user to null to log them out of the UI,
        // allowing them to return to the login screen. This is the key to un-sticking the user.
        setCurrentUser(null);
    }
    // If signOut() is successful, the onAuthStateChange listener will automatically
    // detect the session change and update the currentUser to null.
  }, [setCurrentUser]);

  // This effect adds resilience for network interruptions.
  // When the browser comes back online, it proactively checks if the session is still valid.
  useEffect(() => {
    const handleOnline = async () => {
        console.log("Browser is back online. Checking session status.");
        // Attempt to refresh the session to ensure it's still valid.
        const { error } = await supabase.auth.refreshSession();
        if (error) {
            console.error("Failed to refresh session after coming back online. Forcing logout.", error);
            // The onAuthStateChange listener should handle the sign-out,
            // but we call logout() as a fallback to ensure the UI is updated and the user isn't stuck.
            logout();
        }
    };

    window.addEventListener('online', handleOnline);

    return () => {
        window.removeEventListener('online', handleOnline);
    };
  }, [logout]);

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
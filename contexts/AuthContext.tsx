import React, { createContext, useState, useEffect, ReactNode } from 'react';
import { supabase } from '../services/supabaseClient';
import { User } from '@supabase/supabase-js';
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

  const fetchUserProfile = async (user: User | null): Promise<Profile | null> => {
    if (!user) return null;
    const { data: profile, error } = await supabase
      .from('profiles')
      .select('*')
      .eq('id', user.id)
      .single();
    
    if (error) {
      console.error('Error fetching user profile:', error);
      return null;
    }
    return profile;
  };

  useEffect(() => {
    // This flag is used to prevent setting state on an unmounted component
    let isMounted = true;

    // Check initial session state once on mount
    supabase.auth.getSession().then(async ({ data: { session } }) => {
      if (isMounted) {
        const user = session?.user ?? null;
        const profile = await fetchUserProfile(user);
        setCurrentUser(profile);
        setLoading(false);
      }
    });

    // Listen for auth state changes (login, logout, token refresh)
    const { data: { subscription } } = supabase.auth.onAuthStateChange(async (_event, session) => {
      if (isMounted) {
        const user = session?.user ?? null;
        const profile = await fetchUserProfile(user);
        setCurrentUser(profile);
        // setLoading is only for the initial load, not for subsequent changes
      }
    });

    return () => {
      isMounted = false;
      subscription?.unsubscribe();
    };
  }, []);

  const login = async (email, password) => {
    const { data, error } = await supabase.auth.signInWithPassword({ email, password });

    if (error) {
      return { user: null, success: false, error: error.message };
    }

    if (data.user) {
      const profile = await fetchUserProfile(data.user);
      if (!profile) {
        await supabase.auth.signOut();
        return { user: null, success: false, error: 'User profile not found.' };
      }
      setCurrentUser(profile);
      return { user: data.user, success: true, error: null };
    }

    return { user: null, success: false, error: 'An unknown error occurred during login.' };
  };

  const logout = async () => {
    const { id: userId } = currentUser || {};
    await supabase.auth.signOut();
    setCurrentUser(null);
    if (userId) {
      // Best effort to remove the session, don't block logout if it fails
      supabase.from('user_sessions').delete().eq('user_id', userId).then();
    }
  };

  const value = { currentUser, login, logout, loading };

  // Always render the provider, but conditionally render children based on loading state.
  // This ensures the context is available even during the initial load.
  // Child components like AppRoutes are responsible for showing a spinner.
  return (
    <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
  );
};
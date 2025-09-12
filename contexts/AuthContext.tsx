import React, { createContext, useState, ReactNode, useEffect, useContext, useMemo } from 'react';
import { supabase } from '../services/supabaseClient';
import { User, Role } from '../types';
import { Session } from '@supabase/supabase-js';

interface AuthContextType {
  currentUser: User | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<{ success: boolean; error: string | null }>;
  logout: () => Promise<void>;
}

export const AuthContext = createContext<AuthContextType | undefined>(undefined);

interface AuthProviderProps {
  children: ReactNode;
}

const processSession = async (session: Session | null): Promise<User | null> => {
  if (!session?.user) return null; // Simplified check

  const { user } = session;

  try {
    const { data: profile, error } = await supabase
      .from('profiles')
      .select('name, role, email') // Fetch email from profiles as well
      .eq('id', user.id)
      .single();

    // If a profile doesn't exist, `error` will be set. This is not a fatal error.
    if (error) {
        console.warn(`Could not fetch profile for user ${user.id}: ${error.message}`);
    }

    return {
      id: user.id,
      // Prioritize email from profile table, fallback to auth user email
      email: profile?.email || user.email || '',
      name: profile?.name || user.email || 'User',
      role: (profile?.role as Role) || Role.CLIENT,
    };
  } catch (e: any) {
    console.error("A critical error occurred while processing user session:", e.message);
    // Return a default user object to prevent the app from crashing.
    return {
      id: user.id,
      email: user.email || '',
      name: 'Error loading profile',
      role: Role.CLIENT,
    };
  }
};

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
  const [currentUser, setCurrentUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;

    // Fetch the initial session
    const fetchInitialSession = async () => {
      try {
        const { data: { session } } = await supabase.auth.getSession();
        if (isMounted) {
          const user = await processSession(session);
          setCurrentUser(user);
        }
      } catch (err: any) {
        console.error("Failed to fetch initial session:", err.message);
      } finally {
        if (isMounted) {
          setLoading(false); // Critical: ensure loading is always set to false
        }
      }
    };
    
    fetchInitialSession();

    // Listen for subsequent auth changes
    const { data: { subscription } } = supabase.auth.onAuthStateChange(async (_event, session) => {
      if (isMounted) {
        const user = await processSession(session);
        setCurrentUser(user);
      }
    });

    return () => {
      isMounted = false;
      subscription?.unsubscribe();
    };
  }, []);

  const login = async (email: string, password: string) => {
    const { error } = await supabase.auth.signInWithPassword({
      email,
      password,
    });
    return { success: !error, error: error ? error.message : null };
  };

  const logout = async () => {
    await supabase.auth.signOut();
    setCurrentUser(null);
  };
  
  const value = useMemo(() => ({ currentUser, loading, login, logout }), [currentUser, loading]);

  return (
    <AuthContext.Provider value={value}>
      {!loading && children}
    </AuthContext.Provider>
  );
};
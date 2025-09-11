import React, { createContext, useState, ReactNode, useEffect, useContext } from 'react';
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
  if (!session) return null;

  const { user } = session;
  const { data: profile } = await supabase
    .from('profiles')
    .select('name, role')
    .eq('id', user.id)
    .single();

  return {
    id: user.id,
    email: user.email || '',
    name: profile?.name || user.email || 'User',
    role: (profile?.role as Role) || Role.CLIENT,
  };
};

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
  const [currentUser, setCurrentUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const getSession = async () => {
      const { data: { session } } = await supabase.auth.getSession();
      const user = await processSession(session);
      setCurrentUser(user);
      setLoading(false);
    };
    
    getSession();

    const { data: authListener } = supabase.auth.onAuthStateChange(async (_event, session) => {
      const user = await processSession(session);
      setCurrentUser(user);
      setLoading(false); // Ensure loading is false on auth changes
    });

    return () => {
      authListener.subscription.unsubscribe();
    };
  }, []); // FIX: Changed dependency array to run only once on mount

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
  
  const value = { currentUser, loading, login, logout };

  return (
    <AuthContext.Provider value={value}>
      {!loading && children}
    </AuthContext.Provider>
  );
};
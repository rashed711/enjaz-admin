import React, { createContext, useState, useEffect, ReactNode, useMemo, useCallback } from 'react';
import { supabase } from '../services/supabaseClient';
import { User } from '../types';
import { useAuth } from '../hooks/useAuth';

interface UserContextType {
  users: User[];
  loading: boolean;
  fetchUsers: () => Promise<void>;
}

export const UserContext = createContext<UserContextType | undefined>(undefined);

export const UserProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const { currentUser } = useAuth();

  const fetchUsers = useCallback(async () => {
    setLoading(true);
    try {
      const { data, error } = await supabase
        .from('profiles')
        .select('id, name, email, role');
      
      if (error) {
        // Handle cases where RLS might prevent fetching all users.
        if (error.message.includes('violates row-level security policy')) {
            console.warn("Could not fetch all users due to RLS policy. Only the current user's profile might be available.");
            if (currentUser) {
              setUsers([currentUser]);
            } else {
              setUsers([]);
            }
        } else {
            throw error;
        }
      } else if (data) {
        setUsers(data as User[]);
      }
    } catch (error: any) {
      console.error("Failed to fetch users:", error.message);
      setUsers([]);
    } finally {
      setLoading(false);
    }
  }, [currentUser]);

  useEffect(() => {
    if (currentUser) {
      fetchUsers();
    } else {
      setUsers([]);
      setLoading(false);
    }
  }, [currentUser, fetchUsers]);
  
  const value = useMemo(() => ({ users, loading, fetchUsers }), [users, loading, fetchUsers]);

  return (
    <UserContext.Provider value={value}>
      {children}
    </UserContext.Provider>
  );
};
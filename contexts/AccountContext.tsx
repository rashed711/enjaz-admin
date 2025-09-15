import React, { createContext, useState, useContext, ReactNode, useEffect, useCallback, useMemo } from 'react';
import { Account } from '../types';
import { supabase } from '../services/supabaseClient';
import { useAuth } from '../hooks/useAuth';

interface AccountContextType {
  accountsTree: Account[];
  accountsFlat: Account[];
  loading: boolean;
  fetchAccounts: () => Promise<void>;
  deleteAccount: (accountId: number) => Promise<{ success: boolean; error: string | null }>;
}

const AccountContext = createContext<AccountContextType | undefined>(undefined);

const buildTree = (accounts: Account[]): Account[] => {
    const map = new Map<number, Account>();
    const roots: Account[] = [];

    accounts.forEach(account => {
        map.set(account.id, { ...account, children: [] });
    });

    accounts.forEach(account => {
        const node = map.get(account.id)!;
        if (account.parent_id && map.has(account.parent_id)) {
            map.get(account.parent_id)!.children!.push(node);
        } else {
            roots.push(node);
        }
    });

    return roots;
};

export const AccountProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [accountsFlat, setAccountsFlat] = useState<Account[]>([]);
  const [accountsTree, setAccountsTree] = useState<Account[]>([]);
  const [loading, setLoading] = useState(true);
  const { currentUser, loading: authLoading } = useAuth(); // Get auth loading state
  
  const fetchAccounts = useCallback(async () => {
    setLoading(true);
    try {
      const { data, error } = await supabase.from('accounts').select('*').order('code');
      if (error) throw error;
      
      setAccountsFlat(data || []);
      setAccountsTree(buildTree(data || []));

    } catch (error: any) {
      console.error('Failed to fetch accounts:', error.message);
      setAccountsFlat([]);
      setAccountsTree([]);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    // Don't do anything until the authentication state is resolved.
    if (authLoading) {
      return;
    }

    if (currentUser) { // Now we know for sure if there's a user or not.
        fetchAccounts();
    } else {
      setAccountsFlat([]);
      setAccountsTree([]);
      setLoading(false);
    }
  }, [currentUser, authLoading, fetchAccounts]);

  const deleteAccount = useCallback(async (accountId: number): Promise<{ success: boolean; error: string | null }> => {
    const { error } = await supabase
        .from('accounts')
        .delete()
        .eq('id', accountId);

    if (error) {
        console.error('Error deleting account:', error);
        if (error.code === '23503') { // foreign key violation
            return { success: false, error: 'لا يمكن حذف هذا الحساب لأنه مرتبط بقيود يومية أو حسابات فرعية.' };
        }
        return { success: false, error: error.message };
    }

    // On success, refetch the accounts to update the tree
    await fetchAccounts();
    return { success: true, error: null };
  }, [fetchAccounts]);

  const value = useMemo(() => ({ accountsTree, accountsFlat, loading, fetchAccounts, deleteAccount }), [accountsTree, accountsFlat, loading, fetchAccounts, deleteAccount]);

  return <AccountContext.Provider value={value}>{children}</AccountContext.Provider>;
};

export const useAccounts = () => {
  const context = useContext(AccountContext);
  if (!context) {
    throw new Error('useAccounts must be used within an AccountProvider');
  }
  return context;
};

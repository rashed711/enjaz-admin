import React, { createContext, useState, useContext, ReactNode, useEffect, useCallback, useMemo } from 'react';
import { Account } from '../types';
import { supabase } from '../services/supabaseClient';
import { useAuth } from '../hooks/useAuth';

interface AccountContextType {
  accountsTree: Account[];
  accountsFlat: Account[];
  loading: boolean;
  refetch: () => Promise<void>;
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
  
  const fetchAccounts = useCallback(async (signal: AbortSignal) => {
    setLoading(true);
    try {
      // Fetch from the new view that includes pre-calculated balances
      const { data, error } = await supabase
        .from('accounts_with_balance') // Use the new view
        .select('*')
        .order('code')
        .abortSignal(signal);

      if (error) throw error;
      
      if (!signal.aborted) {
        setAccountsFlat(data || []);
        setAccountsTree(buildTree(data || []));
      }

    } catch (error: any) {
      if (error.name !== 'AbortError') {
        console.error('Failed to fetch accounts:', error.message);
        setAccountsFlat([]);
        setAccountsTree([]);
      }
    } finally {
      if (!signal.aborted) {
        setLoading(false);
      }
    }
  }, []);

  useEffect(() => {
    const abortController = new AbortController();
    // Don't do anything until the authentication state is resolved.
    if (authLoading) {
      return;
    }

    if (currentUser) { // Now we know for sure if there's a user or not.
        fetchAccounts(abortController.signal);
    } else {
      setAccountsFlat([]);
      setAccountsTree([]);
      setLoading(false);
    }

    return () => {
      abortController.abort();
    };
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
    // The context will refetch automatically if you want to keep this behavior,
    // but it's better to manage refetching from the component that calls delete.
    // For now, we'll just update the state locally.
    setAccountsFlat(prev => prev.filter(acc => acc.id !== accountId));
    setAccountsTree(prev => buildTree(accountsFlat.filter(acc => acc.id !== accountId)));
    return { success: true, error: null };
  }, [accountsFlat]);

  const value = useMemo(() => ({ accountsTree, accountsFlat, loading, fetchAccounts: () => {}, deleteAccount, refetch: () => {} }), [accountsTree, accountsFlat, loading, deleteAccount]);

  return <AccountContext.Provider value={value}>{children}</AccountContext.Provider>;
};

export const useAccounts = () => {
  const context = useContext(AccountContext);
  if (!context) {
    throw new Error('useAccounts must be used within an AccountProvider');
  }
  return context;
};

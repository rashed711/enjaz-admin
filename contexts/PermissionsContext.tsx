import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { supabase } from '../services/supabaseClient';
import { Role, PermissionModule, PermissionAction, PermissionsConfig } from '../types';

// --- Default Permissions Configuration ---
// This is the fallback configuration if nothing is found in the database.
const defaultPermissions: PermissionsConfig = {
    [Role.CEO]: {
        [PermissionModule.QUOTATIONS]: [PermissionAction.MANAGE],
        [PermissionModule.SALES_INVOICES]: [PermissionAction.MANAGE],
        [PermissionModule.PURCHASE_INVOICES]: [PermissionAction.MANAGE],
        [PermissionModule.PRODUCTS]: [PermissionAction.MANAGE],
        [PermissionModule.USERS]: [PermissionAction.MANAGE],
        [PermissionModule.PERMISSIONS]: [PermissionAction.MANAGE],
        [PermissionModule.ACCOUNTS]: [PermissionAction.MANAGE], // Chart of Accounts
        [PermissionModule.CUSTOMERS]: [PermissionAction.MANAGE],
        [PermissionModule.SUPPLIERS]: [PermissionAction.MANAGE],
        [PermissionModule.JOURNAL_ENTRIES]: [PermissionAction.MANAGE],
        [PermissionModule.RECEIPTS]: [PermissionAction.MANAGE],
        [PermissionModule.PAYMENT_VOUCHERS]: [PermissionAction.MANAGE],
    },
    // All other roles start with no permissions by default.
    // The CEO must grant them explicitly from the Permissions page.
    // This enforces a "secure-by-default" policy.
    [Role.ACCOUNTING_MANAGER]: {},
    [Role.ACCOUNTING_EMPLOYEE]: {},
    [Role.SALES_MANAGER]: {},
    [Role.SALES_EMPLOYEE]: {},
    [Role.CLIENT]: {},
};

interface PermissionsContextType {
    config: PermissionsConfig;
    loading: boolean;
    updateConfig: (newConfig: PermissionsConfig) => Promise<{ success: boolean; error: string | null }>;
}

const PermissionsContext = createContext<PermissionsContextType | undefined>(undefined);

export const PermissionsProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [config, setConfig] = useState<PermissionsConfig>(defaultPermissions);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const abortController = new AbortController();
        const { signal } = abortController;

        setLoading(true);
        const doFetch = async () => {
            try {
                const { data, error } = await supabase.from('settings').select('value').eq('key', 'permissions_config').abortSignal(signal).single();

                if (signal.aborted) return;

                if (data?.value) {
                    const dbConfig = data.value as PermissionsConfig;
                    const finalConfig: PermissionsConfig = {};

                    for (const roleKey in Role) {
                        const role = Role[roleKey as keyof typeof Role];
                        finalConfig[role] = {};
                        for (const moduleKey in PermissionModule) {
                            const module = PermissionModule[moduleKey as keyof typeof PermissionModule];
                            const dbPermissions = dbConfig[role]?.[module];
                            const defaultPerms = defaultPermissions[role]?.[module];
                            finalConfig[role]![module] = dbPermissions ?? defaultPerms ?? [];
                        }
                    }
                    setConfig(finalConfig);
                } else {
                    setConfig(defaultPermissions);
                    if (error && error.code !== 'PGRST116') console.error("Error fetching permissions config:", error);
                }
            } catch (e: any) {
                if (e.name !== 'AbortError') {
                    console.error("Error fetching permissions config:", e);
                    setConfig(defaultPermissions);
                }
            } finally {
                if (!signal.aborted) {
                    setLoading(false);
                }
            }
        };

        doFetch();

        return () => abortController.abort();
    }, []);

    const updateConfig = async (newConfig: PermissionsConfig) => {
        const { error } = await supabase.from('settings').upsert({ key: 'permissions_config', value: newConfig }, { onConflict: 'key' });
        if (error) return { success: false, error: error.message };
        setConfig(newConfig);
        return { success: true, error: null };
    };

    return <PermissionsContext.Provider value={{ config, loading, updateConfig }}>{children}</PermissionsContext.Provider>;
};

export const usePermissionsConfig = () => {
    const context = useContext(PermissionsContext);
    if (context === undefined) throw new Error('usePermissionsConfig must be used within a PermissionsProvider');
    return context;
};
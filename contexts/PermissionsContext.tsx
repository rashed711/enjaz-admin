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
        [PermissionModule.ACCOUNTS]: [PermissionAction.MANAGE],
        [PermissionModule.JOURNAL_ENTRIES]: [PermissionAction.MANAGE],
        [PermissionModule.RECEIPTS]: [PermissionAction.MANAGE],
        [PermissionModule.PAYMENT_VOUCHERS]: [PermissionAction.MANAGE],
    },
    [Role.ACCOUNTING_MANAGER]: {
        [PermissionModule.SALES_INVOICES]: [PermissionAction.VIEW_ALL, PermissionAction.CHANGE_STATUS],
        [PermissionModule.PURCHASE_INVOICES]: [PermissionAction.MANAGE],
        [PermissionModule.ACCOUNTS]: [PermissionAction.MANAGE],
        [PermissionModule.JOURNAL_ENTRIES]: [PermissionAction.MANAGE],
        [PermissionModule.RECEIPTS]: [PermissionAction.MANAGE],
        [PermissionModule.PAYMENT_VOUCHERS]: [PermissionAction.MANAGE],
    },
    [Role.ACCOUNTING_EMPLOYEE]: {
        [PermissionModule.SALES_INVOICES]: [PermissionAction.VIEW_ALL],
        [PermissionModule.PURCHASE_INVOICES]: [PermissionAction.CREATE, PermissionAction.VIEW_ALL, PermissionAction.EDIT_OWN, PermissionAction.DELETE_OWN],
        [PermissionModule.ACCOUNTS]: [PermissionAction.VIEW_ALL],
        [PermissionModule.JOURNAL_ENTRIES]: [PermissionAction.CREATE, PermissionAction.VIEW_ALL],
        [PermissionModule.RECEIPTS]: [PermissionAction.CREATE, PermissionAction.VIEW_ALL, PermissionAction.EDIT_OWN, PermissionAction.DELETE_OWN],
        [PermissionModule.PAYMENT_VOUCHERS]: [
            PermissionAction.CREATE,
            PermissionAction.VIEW_ALL,
            PermissionAction.EDIT_OWN,
            PermissionAction.DELETE_OWN,
        ],
    },
    [Role.SALES_MANAGER]: {
        [PermissionModule.QUOTATIONS]: [PermissionAction.MANAGE],
        [PermissionModule.SALES_INVOICES]: [PermissionAction.MANAGE],
        [PermissionModule.PRODUCTS]: [PermissionAction.MANAGE],
    },
    [Role.SALES_EMPLOYEE]: {
        [PermissionModule.QUOTATIONS]: [PermissionAction.CREATE, PermissionAction.VIEW_OWN, PermissionAction.EDIT_OWN, PermissionAction.DELETE_OWN],
        [PermissionModule.SALES_INVOICES]: [PermissionAction.CREATE, PermissionAction.VIEW_OWN, PermissionAction.EDIT_OWN, PermissionAction.DELETE_OWN],
    },
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

    const fetchConfig = useCallback(async () => {
        setLoading(true);
        const { data, error } = await supabase.from('settings').select('value').eq('key', 'permissions_config').single();

        if (data?.value) {
            const dbConfig = data.value as PermissionsConfig;
            // Create a deep copy of the default permissions to start with.
            const finalConfig = JSON.parse(JSON.stringify(defaultPermissions));

            // Iterate over default roles and modules to fill in any missing ones from the DB
            // This makes the system resilient to outdated DB configs.
            for (const role in defaultPermissions) {
                if (!dbConfig[role]) {
                    // If the entire role is missing from DB, we keep the default.
                    continue;
                }
                // Overwrite the default module permissions with the ones from the DB.
                Object.assign(finalConfig[role], dbConfig[role]);
            }
            setConfig(finalConfig);
        } else {
            setConfig(defaultPermissions);
            if (error && error.code !== 'PGRST116') console.error("Error fetching permissions config:", error);
        }
        setLoading(false);
    }, []);

    useEffect(() => { fetchConfig(); }, [fetchConfig]);

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
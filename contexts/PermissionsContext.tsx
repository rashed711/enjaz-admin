import React, { createContext, useContext, useMemo, useState, useEffect, useCallback } from 'react';
import { PermissionsConfig } from '../types';
import { supabase } from '../services/supabaseClient';
import { permissionsConfig as staticPermissionsConfig } from '../utils/permissionsConfig';

interface PermissionsContextType {
  config: PermissionsConfig;
  loading: boolean;
  updateConfig: (newConfig: PermissionsConfig) => Promise<{ success: boolean; error: string | null }>;
}

const PermissionsContext = createContext<PermissionsContextType | undefined>(undefined);

const CONFIG_KEY = 'permissions_config';

export const PermissionsProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [config, setConfig] = useState<PermissionsConfig>(staticPermissionsConfig);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchConfig = async () => {
      setLoading(true);
      try {
        const { data, error } = await supabase
          .from('app_config')
          .select('value')
          .eq('key', CONFIG_KEY)
          .single();

        if (error && error.code !== 'PGRST116') { // PGRST116 = 'single row not found'
          throw error;
        }

        if (data?.value) {
          setConfig(data.value as PermissionsConfig);
        } else {
          // If no config in DB, use the static one as a fallback/default.
          setConfig(staticPermissionsConfig);
        }
      } catch (err: any) {
        console.error("Failed to fetch permissions config:", err.message);
        // Fallback to static config on error
        setConfig(staticPermissionsConfig);
      } finally {
        setLoading(false);
      }
    };

    fetchConfig();
  }, []);

  const updateConfig = useCallback(async (newConfig: PermissionsConfig): Promise<{ success: boolean; error: string | null }> => {
    try {
      const { error } = await supabase
        .from('app_config')
        .upsert({ key: CONFIG_KEY, value: newConfig }, { onConflict: 'key' });

      if (error) throw error;

      setConfig(newConfig); // Update local state on successful save
      return { success: true, error: null };
    } catch (err: any) {
      console.error("Failed to update permissions config:", err.message);
      return { success: false, error: `فشل تحديث الصلاحيات: ${err.message}` };
    }
  }, []);
  
  const value = useMemo(() => ({ config, loading, updateConfig }), [config, loading, updateConfig]);

  return <PermissionsContext.Provider value={value}>{children}</PermissionsContext.Provider>;
};

export const usePermissionsConfig = () => {
  const context = useContext(PermissionsContext);
  if (context === undefined) {
    throw new Error('usePermissionsConfig must be used within a PermissionsProvider');
  }
  return context;
};
import React, { createContext, useState, useContext, useEffect, useCallback, useMemo } from 'react';
import { supabase } from '../services/supabaseClient';
import { PermissionsConfig } from '../types';
import { permissionsConfig as staticPermissionsConfig } from '../utils/permissionsConfig';
import { useAuth } from '../hooks/useAuth';

interface PermissionsContextType {
  config: PermissionsConfig;
  loading: boolean;
  updateConfig: (newConfig: PermissionsConfig) => Promise<{ success: boolean; error: string | null }>;
}

const PermissionsContext = createContext<PermissionsContextType | undefined>(undefined);

export const PermissionsProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [config, setConfig] = useState<PermissionsConfig>(staticPermissionsConfig);
  const [loading, setLoading] = useState(true);
  const { currentUser } = useAuth();

  const fetchConfig = useCallback(async () => {
    setLoading(true);
    try {
      const { data, error } = await supabase
        .from('app_settings')
        .select('value')
        .eq('key', 'permissions')
        .single();

      if (error) {
        console.warn('Could not fetch permissions from database, falling back to static config. Error:', error.message);
        setConfig(staticPermissionsConfig);
      } else if (data) {
        setConfig(data.value as PermissionsConfig);
      }
    } catch (e: any) {
      console.error('Failed to fetch permissions config:', e.message);
      setConfig(staticPermissionsConfig); // Fallback on any other error
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (currentUser) {
      fetchConfig();
    } else {
      setConfig(staticPermissionsConfig);
      setLoading(false);
    }
  }, [currentUser, fetchConfig]);

  const updateConfig = useCallback(async (newConfig: PermissionsConfig): Promise<{ success: boolean; error: string | null }> => {
    const { error } = await supabase
      .from('app_settings')
      .update({ value: newConfig, updated_at: new Date().toISOString() })
      .eq('key', 'permissions');

    if (error) {
      console.error('Error updating permissions:', error);
      return { success: false, error: `فشل تحديث الصلاحيات: ${error.message}` };
    }

    setConfig(newConfig);
    return { success: true, error: null };
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
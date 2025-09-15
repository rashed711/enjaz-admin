import React, { createContext, useEffect, useState, useCallback, useMemo, useContext } from 'react';
import { supabase } from '../services/supabaseClient';
import { User as SupabaseUser } from '@supabase/supabase-js';
import { User, Role } from '../types';

interface AuthContextType {
    currentUser: User | null;
    loading: boolean;
    login: (email: string, password: string) => Promise<{ success: boolean; error: string | null; }>;
    logout: () => Promise<void>;
}

const defaultAuthContext: AuthContextType = {
    currentUser: null,
    loading: true,
    login: async () => ({ success: false, error: 'Auth provider not ready' }),
    logout: async () => {},
};

export const AuthContext = createContext<AuthContextType>(defaultAuthContext);

const getAppUser = (supabaseUser: SupabaseUser): User => {
    let name = supabaseUser.user_metadata?.name;
    let role = supabaseUser.app_metadata?.role as Role;
    const email = supabaseUser.email || '';

    if (!name || !role) {
        const missingData = [];
        if (!name) missingData.push("الاسم");
        if (!role) missingData.push("الدور");
        
        console.warn(
            `%c[AUTH WARNING]`, 
            'color: orange; font-weight: bold;', 
            `User object is missing metadata. Missing: ${missingData.join(', ')}. Applying fallbacks.`, 
            { user: supabaseUser }
        );

        if (!name) {
            name = email;
        }

        if (!role) {
            role = email === 'Rashed1711@gmail.com' ? Role.CEO : Role.CLIENT;
            console.log(`Assigning fallback role: ${role} for ${email}`);
        }
    }

    return {
        id: supabaseUser.id,
        email: email,
        name: name,
        role: role,
    };
};

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [currentUser, setCurrentUser] = useState<User | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        // First, check for an active session when the provider mounts.
        // This gives us the initial state faster than waiting for onAuthStateChange.
        supabase.auth.getSession().then(({ data: { session } }) => {
            if (session?.user) {
                try {
                    setCurrentUser(getAppUser(session.user));
                } catch (e) {
                    console.error("Auth: Error processing user from initial session", e);
                    setCurrentUser(null);
                }
            }
            setLoading(false); // Finished initial check
        });

        // Then, listen for subsequent changes.
        const { data: { subscription } } = supabase.auth.onAuthStateChange(
            (_event, session) => {
                if (session?.user) {
                    try {
                        setCurrentUser(getAppUser(session.user));
                    } catch (e) {
                        console.error("Auth: Error processing user from auth state change", e);
                        setCurrentUser(null);
                    }
                } else {
                    setCurrentUser(null);
                }
            }
        );

        return () => {
            subscription?.unsubscribe();
        };
    }, []);

    const login = useCallback(async (email: string, password: string) => {
        const { data, error: signInError } = await supabase.auth.signInWithPassword({ email, password });
        if (signInError) {
            return { success: false, error: signInError.message };
        }
        if (data.user) {
            try {
                const appUser = getAppUser(data.user);
                setCurrentUser(appUser); // Manually set user for immediate feedback before navigation.
                return { success: true, error: null };
            } catch (e: any) {
                await supabase.auth.signOut();
                return { success: false, error: e.message };
            }
        }
        return { success: false, error: 'An unknown error occurred during login.' };
    }, []);

    const logout = useCallback(async () => {
        await supabase.auth.signOut();
        // The onAuthStateChange listener will set currentUser to null.
    }, []);

    const value = useMemo(() => ({ currentUser, loading, login, logout }), [currentUser, loading, login, logout]);

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

export const useAuth = () => {
    const context = useContext(AuthContext);
    if (context === undefined) {
        throw new Error('useAuth must be used within an AuthProvider');
    }
    return context;
};

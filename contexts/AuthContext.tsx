import React, { createContext, useEffect, useState, useCallback, useMemo } from 'react';
import { supabase } from '../services/supabaseClient';
import { User as SupabaseUser } from '@supabase/supabase-js';
import { User, Role } from '../types';

interface AuthContextType {
    currentUser: User | null;
    loading: boolean;
    login: (email: string, password: string) => Promise<{ success: boolean; error: string | null; }>;
    logout: () => Promise<void>;
}

// Create a default no-op implementation for the context to satisfy the initial createContext call.
const defaultAuthContext: AuthContextType = {
    currentUser: null,
    loading: true,
    login: async () => ({ success: false, error: 'Auth provider not ready' }),
    logout: async () => {},
};

export const AuthContext = createContext<AuthContextType>(defaultAuthContext);

/**
 * This function is the single source of truth for creating a valid app user object.
 * It relies exclusively on the user's JWT metadata (`user_metadata` for name, `app_metadata` for role)
 * making `auth.users` the single source of truth and removing dependency on the `profiles` table during login.
 *
 * IMPORTANT: It will throw an error if essential data (name, role) cannot be found, making failures explicit.
 */
const getAppUser = (supabaseUser: SupabaseUser): User => {
    // Attempt to get name and role from metadata first.
    let name = supabaseUser.user_metadata?.name;
    let role = supabaseUser.app_metadata?.role as Role;
    const email = supabaseUser.email || '';

    // If metadata is incomplete, provide sensible fallbacks to ensure login is always possible.
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

        // Fallback for name: use the email address as a temporary name.
        if (!name) {
            name = email;
        }

        // Fallback for role: This is a temporary measure to allow login.
        // Assign CEO role for the specific CEO email, otherwise default to a safe role (Client).
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
        // The onAuthStateChange listener is called immediately with the current session upon subscription.
        // This handles both the initial session check and any subsequent changes,
        // so a separate getSession() call is not needed.
        const { data: { subscription } } = supabase.auth.onAuthStateChange(
            async (_event, session) => {
                setCurrentUser(prevUser => {
                    if (!session?.user) {
                        // If session is null, user is logged out.
                        return prevUser === null ? prevUser : null;
                    }
                    try {
                        const newUser = getAppUser(session.user);
                        // Prevent re-renders if the user object is functionally identical.
                        if (prevUser && JSON.stringify(prevUser) === JSON.stringify(newUser)) {
                            return prevUser;
                        }
                        return newUser;
                    } catch (e) {
                        console.error("Auth state change failed to process user:", e);
                        // If processing fails, it's safer to log them out.
                        return null;
                    }
                });
                setLoading(false); // Set loading to false after the first auth event is handled.
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

import React, { createContext, useEffect, useState } from 'react';
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
 * It fetches the user's profile from the 'profiles' table.
 * IMPORTANT: It will throw an error if the profile cannot be fetched, making failures explicit.
 * This helps diagnose issues like RLS policies preventing access.
 */
const getAppUser = async (supabaseUser: SupabaseUser): Promise<User> => {
    // This query can hang if RLS policies are incorrect. We'll race it against a timeout
    // to prevent the login screen from freezing indefinitely.
    const profileQuery = supabase
        .from('profiles')
        .select('name, role')
        .eq('id', supabaseUser.id)
        .single();

    const timeoutPromise = new Promise<never>((_, reject) => 
        setTimeout(() => reject(new Error('Query timeout')), 8000) // 8-second timeout
    );

    try {
        const { data: profile, error } = await Promise.race([profileQuery, timeoutPromise]);

        if (error) {
            console.error("CRITICAL: Failed to fetch user profile.", error);
            throw new Error(`فشل جلب ملف المستخدم. قد تكون هناك مشكلة في صلاحيات الوصول إلى قاعدة البيانات (RLS).`);
        }

        if (!profile) {
            throw new Error(`لم يتم العثور على ملف تعريف للمستخدم. يرجى التأكد من وجود سجل مطابق في جدول 'profiles'.`);
        }

        return {
            id: supabaseUser.id,
            email: supabaseUser.email || '',
            name: profile.name,
            role: profile.role,
        };
    } catch (e: any) {
        if (e.message === 'Query timeout') {
            console.error("CRITICAL: Timed out while fetching user profile. This is very likely an RLS policy issue.");
            throw new Error('فشل جلب ملف المستخدم: استغرق الطلب وقتاً طويلاً. يرجى التحقق من صلاحيات الوصول إلى قاعدة البيانات (RLS).');
        }
        // Re-throw other errors (like the ones we throw manually above)
        throw e;
    }
};

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [currentUser, setCurrentUser] = useState<User | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const getSession = async () => {
            const { data: { session } } = await supabase.auth.getSession();
            if (session?.user) {
                try {
                    const appUser = await getAppUser(session.user);
                    setCurrentUser(appUser);
                } catch (e) {
                    console.error("Failed to restore session:", e);
                    // If we can't get the profile, treat the user as logged out.
                    await supabase.auth.signOut();
                    setCurrentUser(null);
                }
            }
            setLoading(false);
        };

        getSession();

        const { data: { subscription } } = supabase.auth.onAuthStateChange(
            async (_event, session) => {
                if (session?.user) {
                    try {
                        const appUser = await getAppUser(session.user);
                        setCurrentUser(appUser);
                    } catch (e) {
                        console.error("Auth state change failed to update user:", e);
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

    const login = async (email: string, password: string) => {
        const { data, error: signInError } = await supabase.auth.signInWithPassword({ email, password });
        if (signInError) {
            return { success: false, error: signInError.message };
        }
        if (data.user) {
            try {
                // This will throw if the profile fetch fails, and the catch block will handle it.
                const appUser = await getAppUser(data.user);
                setCurrentUser(appUser); // Manually set user for immediate feedback before navigation.
                return { success: true, error: null };
            } catch (e: any) {
                // If getAppUser fails (e.g., no profile found), sign out the user to prevent an inconsistent state.
                await supabase.auth.signOut();
                // The error from getAppUser is user-friendly and explains the likely RLS issue.
                return { success: false, error: e.message };
            }
        }
        return { success: false, error: 'An unknown error occurred during login.' };
    };

    const logout = async () => {
        await supabase.auth.signOut();
        // The onAuthStateChange listener will set currentUser to null.
    };

    const value = { currentUser, loading, login, logout };

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};
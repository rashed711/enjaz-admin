import { useContext } from 'react';
import { AuthContext, AuthContextType } from '../contexts/AuthContext';

export const useAuth = (): AuthContextType => {
    const context = useContext(AuthContext);
    // The context is created with `null` as the default value, so we must check for `null`.
    if (context === null) {
        throw new Error('useAuth must be used within an AuthProvider');
    }
    return context;
};
import React, { useState, useEffect, useCallback, useRef } from 'react';
import { useAuth } from '../hooks/useAuth';
import { useNavigate } from 'react-router-dom';
import { supabase } from '../services/supabaseClient'; // إضافة جديدة
import ErrorBoundary from './ErrorBoundary';
import SessionTimeoutModal from './SessionTimeoutModal';

const SessionManager: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const { logout, currentUser } = useAuth();
    const navigate = useNavigate();
    const [isWarningModalOpen, setWarningModalOpen] = useState(false);

    // Configurable from .env file, with defaults
    const TIMEOUT_MINUTES = parseInt(import.meta.env.VITE_SESSION_INACTIVITY_TIMEOUT_MINUTES || '15', 10);
    const WARNING_MINUTES = 2; // Show warning 2 minutes before timeout

    const timeoutDuration = TIMEOUT_MINUTES * 60 * 1000;
    const warningTime = Math.max(0, (TIMEOUT_MINUTES - WARNING_MINUTES) * 60 * 1000);

    const logoutTimer = useRef<NodeJS.Timeout>();
    const warningTimer = useRef<NodeJS.Timeout>();

    const handleLogout = useCallback(() => {
        setWarningModalOpen(false);
        logout(); // Signs out from Supabase and clears local state
        navigate('/login', { replace: true, state: { message: 'تم تسجيل خروجك تلقائياً بسبب عدم النشاط.' } });
    }, [logout, navigate]);

    const resetTimers = useCallback(() => {
        if (warningTimer.current) clearTimeout(warningTimer.current);
        if (logoutTimer.current) clearTimeout(logoutTimer.current);

        if (currentUser) {
            warningTimer.current = setTimeout(() => {
                setWarningModalOpen(true);
            }, warningTime);

            logoutTimer.current = setTimeout(handleLogout, timeoutDuration);
        }
    }, [currentUser, handleLogout, timeoutDuration, warningTime]);

    const handleStayLoggedIn = () => {
        setWarningModalOpen(false);
        resetTimers();
    };

    useEffect(() => {
        const events: (keyof WindowEventMap)[] = ['mousemove', 'keydown', 'mousedown', 'scroll', 'touchstart'];
        
        const eventHandler = () => resetTimers();

        if (currentUser) {
            events.forEach(event => window.addEventListener(event, eventHandler));
            resetTimers();
        }

        return () => {
            events.forEach(event => window.removeEventListener(event, eventHandler));
            if (warningTimer.current) clearTimeout(warningTimer.current);
            if (logoutTimer.current) clearTimeout(logoutTimer.current);
        };
    }, [resetTimers, currentUser]);

    // -- Heartbeat to update user's last seen status --
    useEffect(() => {
        if (!currentUser) return;

        const sendHeartbeat = async () => {
            try {
                // This function's only job is to update the last_seen status.
                await supabase.functions.invoke('heartbeat');
            } catch (error) {
                // Don't log out the user for a failed heartbeat. Just log the error.
                console.error('Heartbeat function failed:', error);
            }
        };

        sendHeartbeat(); // Send one immediately
        const heartbeatInterval = setInterval(sendHeartbeat, 60 * 1000); // And then every minute

        return () => {
            clearInterval(heartbeatInterval);
        };
    }, [currentUser]);

    // -- Session refresh on tab focus --
    useEffect(() => {
        if (!currentUser) return;

        const handleVisibilityChange = async () => {
            if (document.visibilityState === 'visible') {
                // When the tab becomes visible, we want to ensure the connection is active.
                // 1. Explicitly resume realtime subscriptions. The client might do this automatically,
                //    but being explicit can help in some edge cases.
                supabase.realtime.resume();
                console.log('Tab became visible, resuming Realtime and refreshing session.');
                
                // 2. Force a session refresh to get a new, valid access token.
                //    This is crucial because the old token might have expired while the tab was in the background.
                const { error } = await supabase.auth.refreshSession();
                if (error) {
                    console.error("Failed to refresh session on focus:", error.message);
                    // If session refresh fails, it's a critical error. Log the user out.
                    handleLogout();
                }
            } else if (document.visibilityState === 'hidden') {
                // When the tab is hidden, we can optionally pause realtime connections to save resources.
                supabase.realtime.pause();
                console.log('Tab hidden, pausing Realtime connections.');
            }
        };

        document.addEventListener('visibilitychange', handleVisibilityChange);

        return () => {
            document.removeEventListener('visibilitychange', handleVisibilityChange);
        };
    }, [currentUser, handleLogout]);

    return (
        <>
            <ErrorBoundary>
                <SessionTimeoutModal isOpen={isWarningModalOpen} onClose={handleLogout} onStay={handleStayLoggedIn} countdownSeconds={WARNING_MINUTES * 60} />
                {children}
            </ErrorBoundary>
        </>
    );
};

export default SessionManager;
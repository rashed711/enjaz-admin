import React, { useState, useEffect, useCallback, useRef } from 'react';
import { useAuth } from '../hooks/useAuth';
import { useNavigate } from 'react-router-dom';
import { supabase } from '../services/supabaseClient'; // إضافة جديدة
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

    // -- إضافة جديدة: Heartbeat لتحديث آخر ظهور للمستخدم --
    useEffect(() => {
        let heartbeatInterval: NodeJS.Timeout;
 
        const sendHeartbeat = async () => {
            // Only send heartbeat if the user is logged in and the tab is visible
            if (!currentUser || document.visibilityState !== 'visible') return;
 
            try {
                // لا حاجة للتعامل مع الاستجابة، فقط أرسل الطلب
                await supabase.functions.invoke('heartbeat');
            } catch (error) {
                console.error('Heartbeat failed:', error);
            }
        };
 
        if (currentUser) {
            // Send a heartbeat immediately when the component mounts or user logs in
            sendHeartbeat();
            // And then every minute
            heartbeatInterval = setInterval(sendHeartbeat, 60 * 1000); // كل دقيقة
 
            // Also send a heartbeat immediately when the tab becomes visible again
            document.addEventListener('visibilitychange', sendHeartbeat);
        }
 
        return () => {
            clearInterval(heartbeatInterval);
            document.removeEventListener('visibilitychange', sendHeartbeat);
        };
    }, [currentUser]);

    return (
        <>
            <SessionTimeoutModal isOpen={isWarningModalOpen} onClose={handleLogout} onStay={handleStayLoggedIn} countdownSeconds={WARNING_MINUTES * 60} />
            {children}
        </>
    );
};

export default SessionManager;
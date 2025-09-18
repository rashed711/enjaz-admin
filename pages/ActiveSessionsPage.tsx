import React, { useState, useEffect, useCallback } from 'react';
import { supabase } from '../services/supabaseClient';
import { useAuth } from '../hooks/useAuth';
import Spinner from '../components/Spinner';
import EmptyState from '../components/EmptyState';
import WifiSlashIcon from '../components/icons/WifiSlashIcon';
import UsersIcon from '../components/icons/UsersIcon';
import { formatDistanceToNow } from 'date-fns';
import { ar } from 'date-fns/locale';
import UserCircleIcon from '../components/icons/UserCircleIcon';

interface ActiveSession {
    user_id: string;
    last_seen_at: string;
    user_agent: string;
    name: string;
    email: string;
    role: string;
}

const ActiveSessionsPage: React.FC = () => {
    const { currentUser, loading: isAuthLoading } = useAuth();
    const [sessions, setSessions] = useState<ActiveSession[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [terminatingId, setTerminatingId] = useState<string | null>(null);

    const ACTIVE_THRESHOLD_MINUTES = parseInt(import.meta.env.VITE_ACTIVE_SESSION_THRESHOLD_MINUTES || '5', 10);

    const fetchActiveSessions = useCallback(async (signal: AbortSignal) => {
        setError(null);
        try {
            // We will call the RPC function instead of querying the view directly
            const { data, error: fetchError } = await supabase
                .rpc('get_active_users', {
                    minutes_inactive: ACTIVE_THRESHOLD_MINUTES
                }, { signal })
                // The RPC function doesn't automatically order, so we do it here.
                .order('last_seen_at', { ascending: false });

            if (signal.aborted) return;

            if (fetchError) throw fetchError;

            setSessions(data || []);
        } catch (e: any) {
            if (e.name !== 'AbortError') {
                console.error("Failed to load active sessions:", e);
                setError(`فشل تحميل الجلسات النشطة. تأكد من أن لديك صلاحيات الوصول.\nالخطأ: ${e.message}`);
            }
        } finally {
            if (!signal.aborted) {
                setLoading(false);
            }
        }
    }, [ACTIVE_THRESHOLD_MINUTES]);

    useEffect(() => {
        if (isAuthLoading) return; // Don't start polling until authentication is resolved.

        // This effect handles polling for active sessions.
        // It's designed to only poll when the page is visible to the user
        // to prevent background network requests that slow down the app.
        const controller = new AbortController();

        const handlePolling = () => {
            // Only fetch if the document is visible
            if (document.visibilityState === 'visible') {
                fetchActiveSessions(controller.signal);
            }
        };

        // Fetch immediately when the component mounts.
        handlePolling();
        
        // Set up the interval
        const intervalId = setInterval(handlePolling, 30000);

        // Add a listener to re-fetch immediately when the tab becomes visible again
        document.addEventListener('visibilitychange', handlePolling);

        // The cleanup function is crucial. It runs when the component unmounts.
        return () => {
            controller.abort();
            clearInterval(intervalId);
            document.removeEventListener('visibilitychange', handlePolling);
        };
    }, [fetchActiveSessions, isAuthLoading]);

    const handleTerminateSession = async (userIdToTerminate: string) => {
        if (userIdToTerminate === currentUser?.id) {
            alert("لا يمكنك إنهاء جلستك الخاصة من هنا.");
            return;
        }
        if (!window.confirm("هل أنت متأكد أنك تريد إنهاء جلسة هذا المستخدم؟ سيتم تسجيل خروجه فوراً.")) {
            return;
        }

        setTerminatingId(userIdToTerminate);
        try {
            const { error: functionError } = await supabase.functions.invoke('terminate-session', {
                body: { userIdToTerminate },
            });

            if (functionError) throw new Error(functionError.message);

            setSessions(prev => prev.filter(s => s.user_id !== userIdToTerminate));
        } catch (e: any) {
            console.error("Failed to terminate session:", e);
            alert(`فشل إنهاء الجلسة: ${e.message}`);
        } finally {
            setTerminatingId(null);
        }
    };

    return (
        <>
            <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6" dir="rtl">
                <div>
                    <h2 className="text-2xl font-bold text-text-primary">الجلسات النشطة حالياً</h2>
                    <p className="text-sm text-text-secondary">
                        يتم عرض المستخدمين الذين كانوا نشطين في آخر {ACTIVE_THRESHOLD_MINUTES} دقائق
                    </p>
                </div>
                <button onClick={() => fetchActiveSessions(new AbortController().signal)} className="px-4 py-2 border border-border rounded-lg hover:bg-slate-100" disabled={loading}>تحديث</button>
            </div>

            {isAuthLoading || (loading && sessions.length === 0) ? (
                <div className="flex justify-center items-center p-10"><Spinner /></div>
            ) : error ? (
                <p className="p-4 text-left text-red-500 bg-red-50/10 border border-red-500/30 rounded-lg whitespace-pre-wrap font-mono">{error}</p>
            ) : sessions.length === 0 ? (
                <EmptyState Icon={UsersIcon} title="لا توجد جلسات نشطة" message="لا يوجد مستخدمون نشطون على النظام في الوقت الحالي." />
            ) : (
                <div className="hidden md:block bg-card rounded-lg shadow-sm border border-border overflow-x-auto" dir="rtl">
                    <table className="w-full text-right min-w-[700px] text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-3 py-3 font-bold text-text-secondary">المستخدم</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الدور</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">آخر ظهور</th>
                                <th className="px-3 py-3 font-bold text-text-secondary text-left">إجراء</th>
                            </tr>
                        </thead>
                        <tbody className="text-text-primary divide-y divide-border">
                            {sessions.map((session) => (
                                <tr key={session.user_id} className="hover:bg-slate-100 even:bg-slate-50/50">
                                    <td className="px-3 py-2"><div className="font-semibold">{session.name}</div><div className="text-xs text-text-secondary">{session.email}</div></td>
                                    <td className="px-3 py-2">{session.role}</td>
                                    <td className="px-3 py-2">{formatDistanceToNow(new Date(session.last_seen_at), { addSuffix: true, locale: ar })}</td>
                                    <td className="px-3 py-2 text-left">
                                        <button onClick={() => handleTerminateSession(session.user_id)} disabled={terminatingId === session.user_id || session.user_id === currentUser?.id} className="flex items-center gap-2 px-3 py-1 bg-red-100 text-red-700 rounded-md hover:bg-red-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                            {terminatingId === session.user_id ? <Spinner size="sm" /> : <WifiSlashIcon className="w-4 h-4" />}
                                            إنهاء الجلسة
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {/* --- Mobile Card View --- */}
            {!loading && !error && sessions.length > 0 && (
                <div className="md:hidden space-y-4" dir="rtl">
                    {sessions.map((session) => (
                        <div key={session.user_id} className="bg-card border border-border rounded-lg p-4 shadow-sm even:bg-slate-50/50">
                            <div className="flex justify-between items-start mb-3">
                                <div className="flex items-center gap-3">
                                    <UserCircleIcon className="w-10 h-10 text-text-secondary" />
                                    <div>
                                        <p className="font-bold text-lg text-text-primary">{session.name}</p>
                                        <p className="text-sm text-text-secondary">{session.email}</p>
                                    </div>
                                </div>
                                <span className="text-sm font-semibold text-primary bg-primary/10 px-2 py-1 rounded">{session.role}</span>
                            </div>
                            <div className="text-sm text-text-secondary mb-4">آخر ظهور: {formatDistanceToNow(new Date(session.last_seen_at), { addSuffix: true, locale: ar })}</div>
                            <div className="flex items-center justify-end gap-2 mt-4 border-t border-border pt-3">
                                <button onClick={() => handleTerminateSession(session.user_id)} disabled={terminatingId === session.user_id || session.user_id === currentUser?.id} className="flex items-center gap-2 w-full justify-center px-3 py-2 bg-red-100 text-red-700 rounded-md hover:bg-red-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                    {terminatingId === session.user_id ? <Spinner size="sm" /> : <WifiSlashIcon className="w-4 h-4" />}
                                    إنهاء الجلسة
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </>
    );
};

export default ActiveSessionsPage;
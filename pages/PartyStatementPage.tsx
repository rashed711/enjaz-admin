import React, { useState, useEffect, useMemo } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { supabase } from '../services/supabaseClient';
import { useAuth } from '../hooks/useAuth';
import { useAccounts } from '../contexts/AccountContext';
import { JournalEntry } from '../types';
import Spinner from '../components/Spinner';
import EmptyState from '../components/EmptyState';
import DocumentTextIcon from '../components/icons/DocumentTextIcon';
import WhatsappIcon from '../components/icons/WhatsappIcon';
import { generatePdfBlob } from '../utils/pdfUtils';
import StatementComponent from '../components/StatementComponent';
import { format } from 'date-fns';

interface PartyStatementPageProps {
  partyType: 'Customer' | 'Supplier';
}

const PartyStatementPage: React.FC<PartyStatementPageProps> = ({ partyType }) => {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const { loading: isAuthLoading } = useAuth();
    const { accountsFlat, loading: accountsLoading } = useAccounts();
    
    const [entries, setEntries] = useState<JournalEntry[]>([]);
    const [entriesLoading, setEntriesLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [isProcessing, setIsProcessing] = useState(false);

    const partyId = parseInt(id || '0', 10);
    const partyTypeLabel = partyType === 'Customer' ? 'العميل' : 'المورد';

    const party = useMemo(() => {
        if (accountsLoading || !partyId) return null;
        return accountsFlat.find(acc => acc.id === partyId);
    }, [accountsFlat, accountsLoading, partyId]);

    useEffect(() => {
        if (isAuthLoading || accountsLoading) return; // WAIT for auth and accounts to be ready

        if (!partyId) {
            setError(`لم يتم تحديد ${partyTypeLabel}.`);
            setEntriesLoading(false);
            return;
        }

        const controller = new AbortController();
        const { signal } = controller;

        const fetchStatement = async (signal: AbortSignal) => {
            setEntriesLoading(true);
            setError(null);
            try {
                const { data, error: entriesError } = await supabase.from('journal_entries').select('*').eq('account_id', partyId).order('date', { ascending: false }).order('id', { ascending: false }).abortSignal(signal);
                
                if (signal.aborted) return;

                if (entriesError) throw entriesError;
                setEntries(data || []);
            } catch (e: any) {
                if (e.name !== 'AbortError') {
                    setError("فشل تحميل كشف الحساب. يرجى المحاولة مرة أخرى.");
                }
            } finally {
                if (!signal.aborted) {
                    setEntriesLoading(false);
                }
            }
        };

        fetchStatement(signal);
        return () => controller.abort();
    }, [partyId, partyTypeLabel, isAuthLoading, accountsLoading]);

    const finalBalance = useMemo(() => {
        // بما أن القائمة الآن من الأحدث إلى الأقدم، يتم حساب الرصيد النهائي بجمع كل الحركات.
        return entries.reduce((balance, entry) => balance + (entry.debit || 0) - (entry.credit || 0), 0);
    }, [entries]);

    const statementWithBalance = useMemo(() => {
        // مع ترتيب الإدخالات من الأحدث إلى الأقدم، نحسب الرصيد المتحرك بشكل عكسي بدءًا من الرصيد النهائي.
        let runningBalance = finalBalance;
        return entries.map(entry => {
            const currentEntryBalance = runningBalance;
            // للحصول على رصيد الإدخال التالي (الأقدم)، نعكس تأثير الحركة الحالية.
            runningBalance -= ((entry.debit || 0) - (entry.credit || 0));
            return { ...entry, balance: currentEntryBalance };
        });
    }, [entries, finalBalance]);

    const handleShare = async () => {
        if (!party) return;
        setIsProcessing(true);
        try {
            const blob = await generatePdfBlob('statement-pdf');
            if (!blob) {
                alert("فشل إنشاء ملف PDF للمشاركة.");
                return;
            }
            const fileName = `كشف حساب - ${party.name}.pdf`;
            const file = new File([blob], fileName, { type: 'application/pdf' });
            const shareData = { files: [file], title: `كشف حساب ${partyTypeLabel} ${party.name}`, text: `مرفق كشف حساب لـ ${partyTypeLabel} ${party.name}.` };
            if (navigator.share && navigator.canShare?.({ files: [file] })) {
                await navigator.share(shareData).catch(console.error);
            } else {
                window.open(`https://wa.me/?text=${encodeURIComponent(shareData.text)}`, '_blank');
            }
        } catch (e) {
            console.error("Error during PDF generation or sharing:", e);
            alert("حدث خطأ أثناء إنشاء ملف PDF. يرجى مراجعة الـ console لمزيد من التفاصيل.");
        } finally {
            setIsProcessing(false);
        }
    };

    if (isAuthLoading || accountsLoading || entriesLoading) return <div className="flex justify-center items-center p-10"><Spinner /></div>;
    if (error) return <div className="p-4 text-center text-red-500">{error}</div>;
    if (!party) return <div className="p-4 text-center text-red-500">لم يتم العثور على {partyTypeLabel}.</div>;

    const buttonClasses = "w-full sm:w-auto px-5 py-2 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2";

    return (
        <>
            <div className="flex flex-col-reverse sm:flex-row sm:justify-between items-center gap-3 mb-6">
                <button onClick={() => navigate(-1)} className={`bg-gray-200 hover:bg-gray-300 text-gray-800 focus:ring-gray-500 ${buttonClasses}`} disabled={isProcessing}>&larr; العودة</button>
                <div className="flex justify-end items-center gap-2 bg-slate-100 p-2 rounded-lg border border-border w-full sm:w-auto">
                    <button onClick={handleShare} className="p-2 bg-green-100 text-green-600 hover:bg-green-200 rounded-full transition-colors" disabled={isProcessing} title="مشاركة عبر واتساب">
                        {isProcessing ? <Spinner /> : <WhatsappIcon className="w-6 h-6" />}
                    </button>
                </div>
            </div>
            {statementWithBalance.length === 0 ? (
                <EmptyState Icon={DocumentTextIcon} title="لا توجد حركات" message={`لا توجد أي قيود محاسبية مسجلة لهذا ${partyTypeLabel} حتى الآن.`} />
            ) : (
                <>
                    {/* Desktop View: The original component, likely a table */}
                    <div className="hidden md:block">
                        <StatementComponent party={party} entries={statementWithBalance} finalBalance={finalBalance} />
                    </div>

                    {/* Mobile View: A list of cards */}
                    <div className="md:hidden space-y-3" dir="rtl">
                        {statementWithBalance.map((entry) => (
                            <div key={entry.id} className="bg-card border border-border rounded-lg p-4 shadow-sm">
                                <div className="flex justify-between items-baseline mb-2">
                                    <p className="font-bold text-text-primary truncate pr-2">{entry.description}</p>
                                    <p className="text-sm text-text-secondary whitespace-nowrap">{format(new Date(entry.date), 'yyyy/MM/dd')}</p>
                                </div>
                                <div className="grid grid-cols-3 gap-2 text-center border-t border-border pt-3 mt-3">
                                    <div>
                                        <p className="text-xs text-text-secondary">مدين</p>
                                        <p className="font-mono font-semibold text-green-600">{entry.debit?.toLocaleString() || '0.00'}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-text-secondary">دائن</p>
                                        <p className="font-mono font-semibold text-red-600">{entry.credit?.toLocaleString() || '0.00'}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-text-secondary">الرصيد</p>
                                        <p className={`font-mono font-bold ${entry.balance >= 0 ? 'text-text-primary' : 'text-red-600'}`}>{Math.abs(entry.balance).toLocaleString()}</p>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </>
            )}
        </>
    );
};

export default PartyStatementPage;
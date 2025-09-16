import React, { useState, useEffect, useMemo } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { supabase } from '../services/supabaseClient';
import { useAccounts } from '../contexts/AccountContext';
import { JournalEntry } from '../types';
import Spinner from '../components/Spinner';
import EmptyState from '../components/EmptyState';
import DocumentTextIcon from '../components/icons/DocumentTextIcon';
import WhatsappIcon from '../components/icons/WhatsappIcon';
import { generatePdfBlob } from '../utils/pdfUtils';
import StatementComponent from '../components/StatementComponent';

const SupplierStatementPage: React.FC = () => {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const { accountsFlat, loading: accountsLoading } = useAccounts();
    
    const [entries, setEntries] = useState<JournalEntry[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [isProcessing, setIsProcessing] = useState(false);

    const supplierId = parseInt(id || '0', 10);

    const supplier = useMemo(() => {
        if (accountsLoading || !supplierId) return null;
        return accountsFlat.find(acc => acc.id === supplierId);
    }, [accountsFlat, accountsLoading, supplierId]);

    useEffect(() => {
        if (!supplierId) {
            setError("لم يتم تحديد المورد.");
            setLoading(false);
            return;
        }

        const fetchStatement = async () => {
            setLoading(true);
            setError(null);
            try {
                const { data, error: entriesError } = await supabase
                    .from('journal_entries')
                    .select('*')
                    .eq('account_id', supplierId)
                    .order('date', { ascending: true })
                    .order('id', { ascending: true }); // for same-day entries

                if (entriesError) throw entriesError;

                setEntries(data || []);
            } catch (e: any) {
                console.error("Failed to fetch supplier statement:", e);
                setError("فشل تحميل كشف الحساب. يرجى المحاولة مرة أخرى.");
            } finally {
                setLoading(false);
            }
        };

        fetchStatement();
    }, [supplierId]);

    const statementWithBalance = useMemo(() => {
        let runningBalance = 0;
        return entries.map(entry => {
            runningBalance += (entry.debit || 0) - (entry.credit || 0);
            return { ...entry, balance: runningBalance };
        });
    }, [entries]);

    const finalBalance = statementWithBalance.length > 0 ? statementWithBalance[statementWithBalance.length - 1].balance : 0;

    const handleShare = async () => {
        if (!supplier) return;
        setIsProcessing(true);
        const blob = await generatePdfBlob('statement-pdf');
        setIsProcessing(false);

        if (!blob) {
            alert("لا يمكن إنشاء ملف PDF للمشاركة.");
            return;
        }

        const fileName = `كشف حساب - ${supplier.name}.pdf`;
        const file = new File([blob], fileName, { type: 'application/pdf' });
        const shareData = {
            files: [file],
            title: `كشف حساب المورد ${supplier.name}`,
            text: `مرفق كشف حساب للمورد ${supplier.name}.`,
        };

        if (navigator.share && navigator.canShare?.({ files: [file] })) {
            try {
                await navigator.share(shareData);
            } catch (error) {
                console.error('Error sharing:', error);
            }
        } else {
            const message = encodeURIComponent(shareData.text);
            window.open(`https://wa.me/?text=${message}`, '_blank');
        }
    };

    if (accountsLoading || loading) {
        return <div className="flex justify-center items-center p-10"><Spinner /></div>;
    }

    if (error) {
        return <div className="p-4 text-center text-red-500">{error}</div>;
    }

    if (!supplier) {
        return <div className="p-4 text-center text-red-500">لم يتم العثور على المورد.</div>;
    }

    const buttonClasses = "w-full sm:w-auto px-5 py-2 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2";

    return (
        <>
            <div className="flex flex-col-reverse sm:flex-row sm:justify-between items-center gap-3 mb-6">
                <button onClick={() => navigate(-1)} className={`bg-gray-200 hover:bg-gray-300 text-gray-800 focus:ring-gray-500 ${buttonClasses}`} disabled={isProcessing}>
                    &larr; العودة
                </button>
                
                <div className="flex justify-end items-center gap-2 bg-slate-100 p-2 rounded-lg border border-border w-full sm:w-auto">
                    <button onClick={handleShare} className="p-2 bg-green-100 text-green-600 hover:bg-green-200 rounded-full transition-colors" disabled={isProcessing} title="مشاركة عبر واتساب">
                        {isProcessing ? <Spinner size="sm" /> : <WhatsappIcon className="w-6 h-6" />}
                    </button>
                </div>
            </div>

            {statementWithBalance.length === 0 ? (
                <EmptyState Icon={DocumentTextIcon} title="لا توجد حركات" message="لا توجد أي قيود محاسبية مسجلة لهذا المورد حتى الآن." />
            ) : (
                <StatementComponent 
                    party={supplier}
                    partyTypeLabel="المورد"
                    entries={statementWithBalance}
                    finalBalance={finalBalance}
                />
            )}
        </>
    );
};

export default SupplierStatementPage;
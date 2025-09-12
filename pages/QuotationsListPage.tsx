import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Quotation, Currency } from '../types';
import { useAuth } from '../hooks/useAuth';
import { canViewAllQuotations } from '../utils/permissions';
import { supabase } from '../services/supabaseClient';
import DeleteConfirmationModal from '../components/DeleteConfirmationModal';

const QuotationsListPage: React.FC = () => {
    const [quotations, setQuotations] = useState<Quotation[]>([]);
    const [loading, setLoading] = useState(true);
    const { currentUser } = useAuth();
    const navigate = useNavigate();
    const [quotationToDelete, setQuotationToDelete] = useState<Quotation | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [deleteError, setDeleteError] = useState<string | null>(null);

    useEffect(() => {
        const fetchQuotations = async () => {
            if (!currentUser) return;
            
            setLoading(true);
            try {
                let query = supabase.from('quotations').select('*');

                if (!canViewAllQuotations(currentUser)) {
                    query = query.eq('created_by', currentUser.id);
                }
                
                const { data, error } = await query.order('date', { ascending: false });

                if (error) {
                    console.error('Error fetching quotations:', error);
                    setQuotations([]); // Clear quotations on error
                } else if (data) {
                    const formattedQuotations: Quotation[] = data.map(q => ({
                        id: q.id,
                        quotationNumber: q.quotation_number,
                        clientName: q.client_name,
                        company: q.company,
                        project: q.project,
                        quotationType: q.quotation_type,
                        date: q.date,
                        currency: q.currency as Currency,
                        items: [], // Items are not fetched in the list view.
                        totalAmount: q.total_amount,
                        createdBy: q.created_by,
                    }));
                    setQuotations(formattedQuotations);
                }
            } catch (e) {
                console.error("An unexpected error occurred while fetching quotations:", e);
                setQuotations([]); // Ensure data is cleared on unexpected errors
            } finally {
                setLoading(false);
            }
        };

        if (currentUser) {
            fetchQuotations();
        } else {
            // If user logs out or session expires, stop loading and clear data.
            setLoading(false);
            setQuotations([]);
        }
    }, [currentUser]);

    const handleConfirmDelete = async () => {
        if (!quotationToDelete || !quotationToDelete.id) return;
    
        setIsDeleting(true);
        setDeleteError(null);
    
        try {
            // First, delete all items associated with the quotation.
            const { error: itemsError } = await supabase
                .from('quotation_items')
                .delete()
                .eq('quotation_id', quotationToDelete.id);
    
            if (itemsError) throw itemsError;
    
            // Then, delete the quotation itself.
            const { error: quotationError } = await supabase
                .from('quotations')
                .delete()
                .eq('id', quotationToDelete.id);
    
            if (quotationError) throw quotationError;
    
            setQuotations(prev => prev.filter(q => q.id !== quotationToDelete.id));
            setQuotationToDelete(null); 
    
        } catch (error: any) {
            console.error("Error deleting quotation:", error);
            setDeleteError(error.message || "فشل حذف عرض السعر. يرجى المحاولة مرة أخرى.");
        } finally {
            setIsDeleting(false);
        }
    };

    return (
        <>
            <DeleteConfirmationModal
                isOpen={!!quotationToDelete}
                onClose={() => {
                    setQuotationToDelete(null);
                    setDeleteError(null);
                }}
                onConfirm={handleConfirmDelete}
                title="تأكيد الحذف"
                message={
                    <>
                        هل أنت متأكد أنك تريد حذف عرض السعر رقم <span className="font-bold text-dark-text">{quotationToDelete?.quotationNumber}</span>؟ سيتم حذف جميع البنود المرتبطة به.
                    </>
                }
                isProcessing={isDeleting}
                error={deleteError}
            />

            <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
                <h2 className="text-2xl font-bold text-dark-text">قائمة عروض الأسعار</h2>
                <button 
                    onClick={() => navigate('/quotations/new')}
                    className="w-full sm:w-auto bg-[#10B981] text-white font-semibold px-5 py-2 rounded-lg hover:bg-[#059669] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-light-bg focus:ring-[#10B981] transition-all duration-200"
                >
                    + إنشاء عرض سعر جديد
                </button>
            </div>

            {loading ? (
                <p className="p-4 text-center text-muted-text">جاري تحميل عروض الأسعار...</p>
            ) : quotations.length === 0 ? (
                <p className="p-4 text-center text-muted-text">لا توجد عروض أسعار لعرضها.</p>
            ) : (
                <>
                    {/* Desktop Table View */}
                    <div className="bg-white rounded-lg shadow-md overflow-x-auto hidden md:block">
                        <table className="w-full text-right min-w-[640px]">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="p-4 font-bold text-muted-text">رقم العرض</th>
                                    <th className="p-4 font-bold text-muted-text">اسم العميل</th>
                                    <th className="p-4 font-bold text-muted-text">المشروع</th>
                                    <th className="p-4 font-bold text-muted-text">التاريخ</th>
                                    <th className="p-4 font-bold text-muted-text">الإجمالي</th>
                                    <th className="p-4 font-bold text-muted-text"></th>
                                </tr>
                            </thead>
                            <tbody className="text-dark-text">
                                {quotations.map((q) => (
                                    <tr key={q.id} className="border-b border-border hover:bg-gray-50 transition-colors duration-200">
                                        <td className="p-4">{q.quotationNumber}</td>
                                        <td className="p-4">{q.clientName}</td>
                                        <td className="p-4">{q.project}</td>
                                        <td className="p-4">{q.date}</td>
                                        <td className="p-4">{q.totalAmount?.toLocaleString()} {q.currency}</td>
                                        <td className="p-4 text-center whitespace-nowrap">
                                            <Link to={`/quotations/${q.id}`} className="text-primary hover:underline font-semibold">
                                                عرض / تعديل
                                            </Link>
                                            <button onClick={() => setQuotationToDelete(q)} className="text-red-500 hover:underline font-semibold mr-4">
                                                حذف
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Mobile Card View */}
                    <div className="md:hidden space-y-4">
                        {quotations.map(q => (
                             <div key={q.id} className="bg-white rounded-lg shadow p-4">
                                <div className="flex justify-between items-start">
                                    <div>
                                        <p className="font-bold text-dark-text">{q.quotationNumber}</p>
                                        <p className="text-sm text-muted-text">{q.clientName}</p>
                                    </div>
                                    <p className="text-xs text-muted-text shrink-0 ml-2">{q.date}</p>
                                </div>
                                <p className="my-2 text-sm text-dark-text">{q.project}</p>
                                <div className="flex justify-between items-center mt-4 pt-4 border-t border-border">
                                    <p className="font-bold text-lg text-primary">{q.totalAmount?.toLocaleString()} {q.currency}</p>
                                    <div className="flex gap-2">
                                        <Link to={`/quotations/${q.id}`} className="bg-primary/10 text-primary px-4 py-1.5 rounded-md font-semibold text-sm hover:bg-primary/20 transition-colors">عرض</Link>
                                        <button onClick={() => setQuotationToDelete(q)} className="bg-red-500/10 text-red-500 px-4 py-1.5 rounded-md font-semibold text-sm hover:bg-red-500/20 transition-colors">حذف</button>
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

export default QuotationsListPage;
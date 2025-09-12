import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Quotation, Currency } from '../types';
import { useAuth } from '../hooks/useAuth';
import { canViewAllQuotations } from '../utils/permissions';
import { supabase } from '../services/supabaseClient';
import DeleteConfirmationModal from '../components/DeleteConfirmationModal';
import Spinner from '../components/Spinner';
import EyeIcon from '../components/icons/EyeIcon';
import PencilIcon from '../components/icons/PencilIcon';
import TrashIcon from '../components/icons/TrashIcon';

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
                        هل أنت متأكد أنك تريد حذف عرض السعر رقم <span className="font-bold text-text-primary">{quotationToDelete?.quotationNumber}</span>؟ سيتم حذف جميع البنود المرتبطة به.
                    </>
                }
                isProcessing={isDeleting}
                error={deleteError}
            />

            <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
                <h2 className="text-2xl font-bold text-text-primary">قائمة عروض الأسعار</h2>
                <button 
                    onClick={() => navigate('/quotations/new')}
                    className="w-full sm:w-auto bg-[#4F46E5] text-white font-semibold px-5 py-2 rounded-lg hover:bg-[#4338CA] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background focus:ring-[#4F46E5] transition-all duration-200"
                >
                    + إنشاء عرض سعر جديد
                </button>
            </div>

            {loading ? (
                <div className="flex justify-center items-center p-10">
                    <Spinner />
                </div>
            ) : quotations.length === 0 ? (
                 <div className="bg-card rounded-lg shadow-sm border border-border p-8 text-center">
                    <p className="text-text-secondary">لا توجد عروض أسعار لعرضها.</p>
                </div>
            ) : (
                <>
                    {/* Desktop Table View */}
                    <div className="bg-card rounded-lg shadow-sm overflow-x-auto hidden md:block border border-border">
                        <table className="w-full text-right min-w-[640px]">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="p-4 font-bold text-text-secondary">التاريخ</th>
                                    <th className="p-4 font-bold text-text-secondary">رقم العرض</th>
                                    <th className="p-4 font-bold text-text-secondary">الشركة</th>
                                    <th className="p-4 font-bold text-text-secondary">المسئول</th>
                                    <th className="p-4 font-bold text-text-secondary">المشروع</th>
                                    <th className="p-4 font-bold text-text-secondary">الإجمالي</th>
                                    <th className="p-4 font-bold text-text-secondary text-left">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody className="text-text-primary">
                                {quotations.map((q) => (
                                    <tr key={q.id} className="border-b border-border hover:bg-slate-50 transition-colors duration-200">
                                        <td className="p-4">{q.date}</td>
                                        <td className="p-4">{q.quotationNumber}</td>
                                        <td className="p-4">{q.company}</td>
                                        <td className="p-4">{q.clientName}</td>
                                        <td className="p-4">{q.project}</td>
                                        <td className="p-4">{q.totalAmount?.toLocaleString()}</td>
                                        <td className="p-4 text-left">
                                            <div className="flex items-center justify-end gap-2">
                                                <Link to={`/quotations/${q.id}/view`} title="عرض" className="p-2 text-blue-600 rounded-full hover:bg-blue-100 transition-colors">
                                                    <EyeIcon className="w-5 h-5" />
                                                </Link>
                                                <Link to={`/quotations/${q.id}/edit`} title="تعديل" className="p-2 text-primary rounded-full hover:bg-primary/10 transition-colors">
                                                    <PencilIcon className="w-5 h-5" />
                                                </Link>
                                                <button onClick={() => setQuotationToDelete(q)} title="حذف" className="p-2 text-red-500 rounded-full hover:bg-red-100 transition-colors">
                                                    <TrashIcon className="w-5 h-5" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Mobile Card View */}
                    <div className="md:hidden space-y-4">
                        {quotations.map(q => (
                             <div key={q.id} className="bg-card rounded-lg shadow-sm p-4 border border-border">
                                <div className="flex justify-between items-start">
                                    <div>
                                        <p className="font-bold text-text-primary">{q.quotationNumber}</p>
                                        <p className="text-sm text-text-secondary">{q.company} ({q.clientName})</p>
                                    </div>
                                    <p className="text-xs text-text-secondary shrink-0 ml-2">{q.date}</p>
                                </div>
                                <p className="my-2 text-sm text-text-primary">{q.project}</p>
                                <div className="flex justify-between items-center mt-4 pt-4 border-t border-border">
                                    <p className="font-bold text-lg text-primary">{q.totalAmount?.toLocaleString()}</p>
                                    <div className="flex gap-1">
                                        <Link to={`/quotations/${q.id}/view`} title="عرض" className="p-2 text-blue-600 rounded-full hover:bg-blue-100 active:bg-blue-200 transition-colors">
                                            <EyeIcon className="w-6 h-6" />
                                        </Link>
                                        <Link to={`/quotations/${q.id}/edit`} title="تعديل" className="p-2 text-primary rounded-full hover:bg-primary/10 active:bg-primary/20 transition-colors">
                                            <PencilIcon className="w-6 h-6" />
                                        </Link>
                                        <button onClick={() => setQuotationToDelete(q)} title="حذف" className="p-2 text-red-500 rounded-full hover:bg-red-100 active:bg-red-200 transition-colors">
                                            <TrashIcon className="w-6 h-6" />
                                        </button>
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
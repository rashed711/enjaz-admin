import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { PurchaseInvoice, Currency } from '../types';
import { useAuth } from '../hooks/useAuth';
import { supabase } from '../services/supabaseClient';
import DeleteConfirmationModal from '../components/DeleteConfirmationModal';
import Spinner from '../components/Spinner';
import EyeIcon from '../components/icons/EyeIcon';
import PencilIcon from '../components/icons/PencilIcon';
import TrashIcon from '../components/icons/TrashIcon';

const PurchaseInvoicesListPage: React.FC = () => {
    const [invoices, setInvoices] = useState<PurchaseInvoice[]>([]);
    const [loading, setLoading] = useState(true);
    const { currentUser } = useAuth();
    const navigate = useNavigate();
    const [invoiceToDelete, setInvoiceToDelete] = useState<PurchaseInvoice | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [deleteError, setDeleteError] = useState<string | null>(null);
    const [pageError, setPageError] = useState<string | null>(null);

    useEffect(() => {
        const fetchInvoices = async () => {
            if (!currentUser) return;
            
            setLoading(true);
            setPageError(null);
            try {
                let { data, error } = await supabase.from('purchase_invoices').select('id, invoice_number, supplier_name, date, currency, status, total_amount, created_by').order('date', { ascending: false });

                if (error) {
                    throw error;
                }
                
                const formattedInvoices: PurchaseInvoice[] = data.map(i => ({
                    id: i.id,
                    invoiceNumber: i.invoice_number,
                    supplierName: i.supplier_name,
                    date: i.date,
                    currency: i.currency as Currency,
                    status: i.status,
                    items: [],
                    totalAmount: i.total_amount,
                    createdBy: i.created_by,
                }));
                setInvoices(formattedInvoices);
                
            } catch (err: any) {
                console.error("Failed to load invoice list:", err); // Log full error object for devs
                let errorMessage = 'An unknown error occurred. Check the console for more details.';
                if (err) {
                    errorMessage = typeof err.message === 'string' ? err.message : JSON.stringify(err);
                }
                setPageError(`فشل تحميل قائمة الفواتير. قد تكون هناك مشكلة في صلاحيات الوصول (RLS).\n\nالخطأ الفني: ${errorMessage}`);
                setInvoices([]);
            } finally {
                setLoading(false);
            }
        };

        fetchInvoices();
    }, [currentUser]);

    const handleConfirmDelete = async () => {
        if (!invoiceToDelete || !invoiceToDelete.id) return;
    
        setIsDeleting(true);
        setDeleteError(null);
    
        try {
            const { error: itemsError } = await supabase
                .from('purchase_invoice_items')
                .delete()
                .eq('invoice_id', invoiceToDelete.id);
    
            if (itemsError) throw itemsError;
    
            const { error: invoiceError } = await supabase
                .from('purchase_invoices')
                .delete()
                .eq('id', invoiceToDelete.id);
    
            if (invoiceError) throw invoiceError;
    
            setInvoices(prev => prev.filter(i => i.id !== invoiceToDelete.id));
            setInvoiceToDelete(null); 
    
        } catch (error: any) {
            console.error("Error deleting invoice:", error.message);
            setDeleteError(error.message || "فشل حذف الفاتورة. يرجى المحاولة مرة أخرى.");
        } finally {
            setIsDeleting(false);
        }
    };

    const getStatusChip = (status: string) => {
        switch (status) {
            case 'Paid': return 'bg-green-100 text-green-800';
            case 'Draft': return 'bg-yellow-100 text-yellow-800';
            case 'Cancelled': return 'bg-red-100 text-red-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    }

    return (
        <>
            <DeleteConfirmationModal
                isOpen={!!invoiceToDelete}
                onClose={() => { setInvoiceToDelete(null); setDeleteError(null); }}
                onConfirm={handleConfirmDelete}
                title="تأكيد الحذف"
                message={<>هل أنت متأكد أنك تريد حذف الفاتورة رقم <span className="font-bold text-text-primary">{invoiceToDelete?.invoiceNumber}</span>؟</>}
                isProcessing={isDeleting}
                error={deleteError}
            />

            <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
                <h2 className="text-2xl font-bold text-text-primary">قائمة فواتير المشتريات</h2>
                <button 
                    onClick={() => navigate('/invoices/new')}
                    className="w-full sm:w-auto bg-[#4F46E5] text-white font-semibold px-5 py-2 rounded-lg hover:bg-[#4338CA] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background focus:ring-[#4F46E5] transition-all duration-200"
                >
                    + إنشاء فاتورة جديدة
                </button>
            </div>

            {loading ? (
                <div className="flex justify-center items-center p-10"><Spinner /></div>
            ) : pageError ? (
                <p className="p-4 text-left text-red-500 bg-red-50/10 border border-red-500/30 rounded-lg whitespace-pre-wrap font-mono">{pageError}</p>
            ) : invoices.length === 0 ? (
                 <div className="bg-card rounded-lg shadow-sm border border-border p-8 text-center">
                    <p className="text-text-secondary">لا توجد فواتير لعرضها.</p>
                </div>
            ) : (
                <>
                    {/* Desktop Table View */}
                    <div className="bg-card rounded-lg shadow-sm overflow-x-auto hidden md:block border border-border">
                        <table className="w-full text-right min-w-[640px]">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="p-4 font-bold text-text-secondary">التاريخ</th>
                                    <th className="p-4 font-bold text-text-secondary">رقم الفاتورة</th>
                                    <th className="p-4 font-bold text-text-secondary">المورد</th>
                                    <th className="p-4 font-bold text-text-secondary">الحالة</th>
                                    <th className="p-4 font-bold text-text-secondary">الإجمالي</th>
                                    <th className="p-4 font-bold text-text-secondary text-left">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody className="text-text-primary">
                                {invoices.map((i) => (
                                    <tr key={i.id} className="border-b border-border hover:bg-slate-50 transition-colors duration-200">
                                        <td className="p-4">{i.date}</td>
                                        <td className="p-4">{i.invoiceNumber}</td>
                                        <td className="p-4">{i.supplierName}</td>
                                        <td className="p-4"><span className={`px-2 py-1 text-xs font-semibold rounded-full ${getStatusChip(i.status)}`}>{i.status}</span></td>
                                        <td className="p-4">{i.totalAmount?.toLocaleString()}</td>
                                        <td className="p-4 text-left">
                                            <div className="flex items-center justify-end gap-2">
                                                <Link to={`/invoices/${i.id}/view`} title="عرض" className="p-2 text-blue-600 rounded-full hover:bg-blue-100 transition-colors"><EyeIcon className="w-5 h-5" /></Link>
                                                <Link to={`/invoices/${i.id}/edit`} title="تعديل" className="p-2 text-primary rounded-full hover:bg-primary/10 transition-colors"><PencilIcon className="w-5 h-5" /></Link>
                                                <button onClick={() => setInvoiceToDelete(i)} title="حذف" className="p-2 text-red-500 rounded-full hover:bg-red-100 transition-colors"><TrashIcon className="w-5 h-5" /></button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Mobile Card View */}
                    <div className="md:hidden space-y-4">
                        {invoices.map(i => (
                             <div key={i.id} className="bg-card rounded-lg shadow-sm p-4 border border-border">
                                <div className="flex justify-between items-start">
                                    <div>
                                        <p className="font-bold text-text-primary">{i.invoiceNumber}</p>
                                        <p className="text-sm text-text-secondary">{i.supplierName}</p>
                                    </div>
                                    <p className="text-xs text-text-secondary shrink-0 ml-2">{i.date}</p>
                                </div>
                                <div className="flex justify-between items-center mt-4 pt-4 border-t border-border">
                                    <div className="flex items-center gap-2">
                                        <p className="font-bold text-lg text-primary">{i.totalAmount?.toLocaleString()}</p>
                                        <span className={`px-2 py-1 text-xs font-semibold rounded-full ${getStatusChip(i.status)}`}>{i.status}</span>
                                    </div>
                                    <div className="flex gap-1">
                                        <Link to={`/invoices/${i.id}/view`} title="عرض" className="p-2 text-blue-600 rounded-full hover:bg-blue-100"><EyeIcon className="w-6 h-6" /></Link>
                                        <Link to={`/invoices/${i.id}/edit`} title="تعديل" className="p-2 text-primary rounded-full hover:bg-primary/10"><PencilIcon className="w-6 h-6" /></Link>
                                        <button onClick={() => setInvoiceToDelete(i)} title="حذف" className="p-2 text-red-500 rounded-full hover:bg-red-100"><TrashIcon className="w-6 h-6" /></button>
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

export default PurchaseInvoicesListPage;
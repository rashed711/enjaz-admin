
import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { PurchaseInvoice, Currency } from '../types';
import { useAuth } from '../hooks/useAuth';
import { useProducts } from '../contexts/ProductContext';
import { supabase } from '../services/supabaseClient';
import DeleteConfirmationModal from '../components/DeleteConfirmationModal';
import EmptyState from '../components/EmptyState';
import Spinner from '../components/Spinner';
import EyeIcon from '../components/icons/EyeIcon';
import PencilIcon from '../components/icons/PencilIcon';
import TrashIcon from '../components/icons/TrashIcon';
import ReceiptIcon from '../components/icons/ReceiptIcon';

const PurchaseInvoicesListPage: React.FC = () => {
    const [invoices, setInvoices] = useState<PurchaseInvoice[]>([]);
    const [loading, setLoading] = useState(true);
    const { currentUser } = useAuth();
    const { fetchProducts } = useProducts();
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
                console.error("Failed to load invoice list:", err.message); 
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
            await fetchProducts();
    
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
                    className="w-full sm:w-auto bg-green-600 text-white font-semibold px-5 py-2 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background focus:ring-green-600 shadow-md hover:shadow-lg"
                >
                    + إنشاء فاتورة جديدة
                </button>
            </div>

            {loading ? (
                <div className="flex justify-center items-center p-10"><Spinner /></div>
            ) : pageError ? (
                <p className="p-4 text-left text-red-500 bg-red-50/10 border border-red-500/30 rounded-lg whitespace-pre-wrap font-mono">{pageError}</p>
            ) : invoices.length === 0 ? (
                <EmptyState
                    Icon={ReceiptIcon}
                    title="لا توجد فواتير مشتريات"
                    message="لم تقم بإضافة أي فواتير مشتريات بعد. ابدأ الآن لتتبع نفقاتك."
                    action={{
                        label: '+ إنشاء فاتورة جديدة',
                        onClick: () => navigate('/invoices/new')
                    }}
                />
            ) : (
                <div className="block bg-card rounded-lg shadow-sm border border-border overflow-x-auto">
                    <table className="w-full text-right min-w-[800px] text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-3 py-2 font-bold text-text-secondary sticky right-0 bg-slate-50 border-l border-border">رقم الفاتورة</th>
                                <th className="px-3 py-2 font-bold text-text-secondary">التاريخ</th>
                                <th className="px-3 py-2 font-bold text-text-secondary">المورد</th>
                                <th className="px-3 py-2 font-bold text-text-secondary">الحالة</th>
                                <th className="px-3 py-2 font-bold text-text-secondary">الإجمالي</th>
                                <th className="px-3 py-2 font-bold text-text-secondary text-left sticky left-0 bg-slate-50 border-r border-border">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody className="text-text-primary divide-y divide-border">
                            {invoices.map((i) => (
                                <tr key={i.id} className="hover:bg-slate-50">
                                    <td className="px-3 py-2 whitespace-nowrap font-semibold sticky right-0 bg-white hover:bg-slate-50 border-l border-border">{i.invoiceNumber}</td>
                                    <td className="px-3 py-2 whitespace-nowrap">{i.date}</td>
                                    <td className="px-3 py-2">{i.supplierName}</td>
                                    <td className="px-3 py-2 whitespace-nowrap"><span className={`px-2 py-1 text-xs font-semibold rounded-full ${getStatusChip(i.status)}`}>{i.status}</span></td>
                                    <td className="px-3 py-2 whitespace-nowrap">{i.totalAmount?.toLocaleString()} {i.currency}</td>
                                    <td className="px-3 py-2 text-left sticky left-0 bg-white hover:bg-slate-50 border-r border-border">
                                        <div className="flex items-center justify-end gap-2">
                                            <Link to={`/invoices/${i.id}/view`} title="عرض" className="p-2 bg-blue-100 text-blue-700 rounded-full hover:bg-blue-200"><EyeIcon className="w-5 h-5" /></Link>
                                            <Link to={`/invoices/${i.id}/edit`} title="تعديل" className="p-2 bg-indigo-100 text-indigo-700 rounded-full hover:bg-indigo-200"><PencilIcon className="w-5 h-5" /></Link>
                                            <button onClick={() => setInvoiceToDelete(i)} title="حذف" className="p-2 bg-red-100 text-red-700 rounded-full hover:bg-red-200"><TrashIcon className="w-5 h-5" /></button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </>
    );
};

export default PurchaseInvoicesListPage;

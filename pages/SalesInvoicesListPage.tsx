
import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { SalesInvoice, Currency, PermissionModule, PermissionAction } from '../types';
import { useAuth } from '../hooks/useAuth';
import { usePermissions } from '../hooks/usePermissions';
import { supabase } from '../services/supabaseClient';
import DeleteConfirmationModal from '../components/DeleteConfirmationModal';
import EmptyState from '../components/EmptyState';
import Spinner from '../components/Spinner';
import EyeIcon from '../components/icons/EyeIcon';
import PencilIcon from '../components/icons/PencilIcon';
import TrashIcon from '../components/icons/TrashIcon';
import DocumentTextIcon from '../components/icons/DocumentTextIcon';
import { getStatusChipClassName } from '../utils/uiHelpers';

const SalesInvoicesListPage: React.FC = () => {
    const [invoices, setInvoices] = useState<SalesInvoice[]>([]);
    const [loading, setLoading] = useState(true);
    const { currentUser } = useAuth();
    const permissions = usePermissions();
    const navigate = useNavigate();
    const [invoiceToDelete, setInvoiceToDelete] = useState<SalesInvoice | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [deleteError, setDeleteError] = useState<string | null>(null);

    const canCreate = permissions.can(PermissionModule.SALES_INVOICES, PermissionAction.CREATE);
    const canViewAll = permissions.can(PermissionModule.SALES_INVOICES, PermissionAction.VIEW_ALL);
    const canViewOwn = permissions.can(PermissionModule.SALES_INVOICES, PermissionAction.VIEW_OWN);

    useEffect(() => {
        const fetchInvoices = async () => {
            if (!currentUser) return;
            
            setLoading(true);
            try {
                if (!canViewAll && !canViewOwn) {
                    setInvoices([]);
                    setLoading(false);
                    return;
                }

                let query = supabase
                    .from('sales_invoices')
                    .select('*');

                if (!canViewAll && canViewOwn) {
                    query = query.eq('created_by', currentUser.id);
                }

                let { data, error } = await query.order('date', { ascending: false });

                if (error) {
                    // Gracefully handle the error if the table doesn't exist yet
                    if (error.message.includes('does not exist') || error.message.includes('in the schema cache')) {
                        console.warn("`sales_invoices` table not found. Displaying an empty list. Please run the database migration script to enable this feature.");
                        setInvoices([]); // Set to empty array to show EmptyState component
                    } else {
                        // For other, unexpected errors, log them
                        console.error('Error fetching sales invoices:', error.message);
                        setInvoices([]);
                    }
                } else if (data) {
                    const formattedInvoices: SalesInvoice[] = data.map(i => ({
                        id: i.id,
                        invoiceNumber: i.invoice_number,
                        clientName: i.client_name,
                        company: i.company,
                        project: i.project,
                        date: i.date,
                        currency: i.currency as Currency,
                        status: i.status,
                        items: [],
                        totalAmount: i.total_amount,
                        createdBy: i.created_by,
                        quotationId: i.quotation_id,
                    }));
                    setInvoices(formattedInvoices);
                }
            } catch (e: any) {
                console.error("An unexpected error occurred while fetching sales invoices:", e.message);
                setInvoices([]);
            } finally {
                setLoading(false);
            }
        };

        if (currentUser) {
            fetchInvoices();
        }
    }, [currentUser, permissions, canViewAll, canViewOwn]);

    const handleConfirmDelete = async () => {
        if (!invoiceToDelete || !invoiceToDelete.id) return;
    
        setIsDeleting(true);
        setDeleteError(null);
    
        try {
            await supabase
                .from('sales_invoice_items')
                .delete()
                .eq('invoice_id', invoiceToDelete.id);
    
            await supabase
                .from('sales_invoices')
                .delete()
                .eq('id', invoiceToDelete.id);
    
            setInvoices(prev => prev.filter(i => i.id !== invoiceToDelete.id));
            setInvoiceToDelete(null);
    
        } catch (error: any) {
            console.error("Error deleting sales invoice:", error.message);
            setDeleteError(error.message || "فشل حذف فاتورة المبيعات.");
        } finally {
            setIsDeleting(false);
        }
    };

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
                <h2 className="text-2xl font-bold text-text-primary">قائمة فواتير المبيعات</h2>
                {canCreate && (
                    <button 
                        onClick={() => navigate('/sales-invoices/new')}
                        className="w-full sm:w-auto bg-green-600 text-white font-semibold px-5 py-2 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background focus:ring-green-600 shadow-md hover:shadow-lg"
                    >
                        + إنشاء فاتورة جديدة
                    </button>
                )}
            </div>

            {loading ? (
                <div className="flex justify-center items-center p-10"><Spinner /></div>
            ) : invoices.length === 0 ? (
                <EmptyState
                    Icon={DocumentTextIcon}
                    title="لا توجد فواتير مبيعات"
                    message={canCreate ? "ابدأ بإنشاء فاتورة جديدة أو قم بتحويل عرض سعر إلى فاتورة." : "ليس لديك صلاحية لعرض فواتير المبيعات."}
                    action={canCreate ? {
                        label: '+ إنشاء فاتورة جديدة',
                        onClick: () => navigate('/sales-invoices/new')
                    } : undefined}
                />
            ) : (
                <div className="block bg-card rounded-lg shadow-sm border border-border overflow-x-auto">
                    <table className="w-full text-right min-w-[800px] text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-3 py-3 font-bold text-text-secondary sticky right-0 bg-slate-50 border-l border-border">رقم الفاتورة</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">التاريخ</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الشركة</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">المسئول</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الحالة</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الإجمالي</th>
                                <th className="px-3 py-3 font-bold text-text-secondary text-left sticky left-0 bg-slate-50 border-r border-border">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody className="text-text-primary divide-y divide-border">
                            {invoices.map((i) => {
                                const canEdit = permissions.can(PermissionModule.SALES_INVOICES, PermissionAction.EDIT_OWN, i.createdBy);
                                const canDelete = permissions.can(PermissionModule.SALES_INVOICES, PermissionAction.DELETE_OWN, i.createdBy);

                                return (
                                <tr key={i.id} className="hover:bg-slate-50">
                                    <td className="px-3 py-3 whitespace-nowrap font-semibold sticky right-0 bg-card hover:bg-slate-50 border-l border-border">{i.invoiceNumber}</td>
                                    <td className="px-3 py-3 whitespace-nowrap">{i.date}</td>
                                    <td className="px-3 py-3">{i.company}</td>
                                    <td className="px-3 py-3">{i.clientName}</td>
                                    <td className="px-3 py-3 whitespace-nowrap"><span className={`px-2 py-1 text-xs font-semibold rounded-full ${getStatusChipClassName(i.status)}`}>{i.status}</span></td>
                                    <td className="px-3 py-3 whitespace-nowrap">{i.totalAmount?.toLocaleString()} {i.currency}</td>
                                    <td className="px-3 py-3 text-left sticky left-0 bg-card hover:bg-slate-50 border-r border-border">
                                        <div className="flex items-center justify-end gap-2">
                                            <Link to={`/sales-invoices/${i.id}/view`} title="عرض" className="p-2 bg-blue-100 text-blue-700 rounded-full hover:bg-blue-200"><EyeIcon className="w-4 h-4" /></Link>
                                            {canEdit && (
                                                <Link to={`/sales-invoices/${i.id}/edit`} title="تعديل" className="p-2 bg-indigo-100 text-indigo-700 rounded-full hover:bg-indigo-200"><PencilIcon className="w-4 h-4" /></Link>
                                            )}
                                            {canDelete && (
                                                <button onClick={() => setInvoiceToDelete(i)} title="حذف" className="p-2 bg-red-100 text-red-700 rounded-full hover:bg-red-200"><TrashIcon className="w-4 h-4" /></button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            )})}
                        </tbody>
                    </table>
                </div>
            )}
        </>
    );
};

export default SalesInvoicesListPage;
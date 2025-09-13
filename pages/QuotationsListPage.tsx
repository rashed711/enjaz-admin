

import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Quotation, Currency, PermissionModule, PermissionAction } from '../types';
import { useAuth } from '../hooks/useAuth';
import { useProducts } from '../contexts/ProductContext';
import { usePermissions } from '../hooks/usePermissions';
import { supabase } from '../services/supabaseClient';
import DeleteConfirmationModal from '../components/DeleteConfirmationModal';
import EmptyState from '../components/EmptyState';
import Spinner from '../components/Spinner';
import EyeIcon from '../components/icons/EyeIcon';
import PencilIcon from '../components/icons/PencilIcon';
import TrashIcon from '../components/icons/TrashIcon';
import DocumentTextIcon from '../components/icons/DocumentTextIcon';

const QuotationsListPage: React.FC = () => {
    const [quotations, setQuotations] = useState<Quotation[]>([]);
    const [loading, setLoading] = useState(true);
    const { currentUser } = useAuth();
    const { fetchProducts } = useProducts();
    const navigate = useNavigate();
    const permissions = usePermissions();
    const [quotationToDelete, setQuotationToDelete] = useState<Quotation | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [deleteError, setDeleteError] = useState<string | null>(null);

    const canCreate = permissions.can(PermissionModule.QUOTATIONS, PermissionAction.CREATE);
    const canViewAll = permissions.can(PermissionModule.QUOTATIONS, PermissionAction.VIEW_ALL);
    const canViewOwn = permissions.can(PermissionModule.QUOTATIONS, PermissionAction.VIEW_OWN);

    useEffect(() => {
        const fetchQuotations = async () => {
            if (!currentUser) return;
            
            setLoading(true);
            try {
                if (!canViewAll && !canViewOwn) {
                    setQuotations([]);
                    setLoading(false);
                    return;
                }

                let query = supabase.from('quotations').select('*');
                
                if (!canViewAll && canViewOwn) {
                    query = query.eq('created_by', currentUser.id);
                }
                
                const { data, error } = await query.order('date', { ascending: false });

                if (error) {
                    console.error('Error fetching quotations:', error.message);
                    setQuotations([]);
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
                        items: [],
                        totalAmount: q.total_amount,
                        createdBy: q.created_by,
                        taxIncluded: q.tax_included ?? false, // Default to false for older records
                        discount: 0, // Not available in list view
                    }));
                    setQuotations(formattedQuotations);
                }
            } catch (e: any) {
                console.error("An unexpected error occurred while fetching quotations:", e.message);
                setQuotations([]);
            } finally {
                setLoading(false);
            }
        };

        if (currentUser) {
            fetchQuotations();
        } else {
            setLoading(false);
            setQuotations([]);
        }
    }, [currentUser, permissions, canViewAll, canViewOwn]);

    const handleConfirmDelete = async () => {
        if (!quotationToDelete || !quotationToDelete.id) return;
    
        setIsDeleting(true);
        setDeleteError(null);
    
        try {
            const { error: itemsError } = await supabase
                .from('quotation_items')
                .delete()
                .eq('quotation_id', quotationToDelete.id);
    
            if (itemsError) throw itemsError;
    
            const { error: quotationError } = await supabase
                .from('quotations')
                .delete()
                .eq('id', quotationToDelete.id);
    
            if (quotationError) throw quotationError;
    
            setQuotations(prev => prev.filter(q => q.id !== quotationToDelete.id));
            setQuotationToDelete(null);
            await fetchProducts();
    
        } catch (error: any) {
            console.error("Error deleting quotation:", error.message);
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
                {canCreate && (
                    <button 
                        onClick={() => navigate('/quotations/new')}
                        className="w-full sm:w-auto bg-green-600 text-white font-semibold px-5 py-2 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background focus:ring-green-600 shadow-md hover:shadow-lg"
                    >
                        + إنشاء عرض سعر جديد
                    </button>
                )}
            </div>

            {loading ? (
                <div className="flex justify-center items-center p-10">
                    <Spinner />
                </div>
            ) : quotations.length === 0 ? (
                 <EmptyState
                    Icon={DocumentTextIcon}
                    title="لا توجد عروض أسعار"
                    message="ليس لديك أي عروض أسعار حتى الآن. ابدأ بإنشاء عرض سعر جديد لعملائك."
                    action={canCreate ? {
                        label: '+ إنشاء عرض سعر جديد',
                        onClick: () => navigate('/quotations/new')
                    } : undefined}
                 />
            ) : (
                <div className="block bg-card rounded-lg shadow-sm border border-border overflow-x-auto">
                    <table className="w-full text-right min-w-[700px] text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-3 py-3 font-bold text-text-secondary sticky right-0 bg-slate-50 border-l border-border">رقم العرض</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">التاريخ</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الشركة</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">المسئول</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">المشروع</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الإجمالي</th>
                                <th className="px-3 py-3 font-bold text-text-secondary text-left sticky left-0 bg-slate-50 border-r border-border">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody className="text-text-primary divide-y divide-border">
                            {quotations.map((q) => {
                                const canEdit = permissions.can(PermissionModule.QUOTATIONS, PermissionAction.EDIT_OWN, q.createdBy);
                                const canDelete = permissions.can(PermissionModule.QUOTATIONS, PermissionAction.DELETE_OWN, q.createdBy);

                                return (
                                    <tr key={q.id} className="hover:bg-slate-50">
                                        <td className={`px-3 py-3 whitespace-nowrap font-semibold sticky right-0 bg-white hover:bg-slate-50 border-l border-border ${!q.taxIncluded ? 'text-orange-600' : ''}`}>{q.quotationNumber}</td>
                                        <td className="px-3 py-3 whitespace-nowrap">{q.date}</td>
                                        <td className="px-3 py-3">{q.company}</td>
                                        <td className="px-3 py-3">{q.clientName}</td>
                                        <td className="px-3 py-3">{q.project}</td>
                                        <td className="px-3 py-3 whitespace-nowrap">{q.totalAmount?.toLocaleString()} {q.currency}</td>
                                        <td className="px-3 py-3 text-left sticky left-0 bg-white hover:bg-slate-50 border-r border-border">
                                            <div className="flex items-center justify-end gap-2">
                                                <Link to={`/quotations/${q.id}/view`} title="عرض" className="p-2 bg-blue-100 text-blue-700 rounded-full hover:bg-blue-200">
                                                    <EyeIcon className="w-4 h-4" />
                                                </Link>
                                                {canEdit && (
                                                    <Link to={`/quotations/${q.id}/edit`} title="تعديل" className="p-2 bg-indigo-100 text-indigo-700 rounded-full hover:bg-indigo-200">
                                                        <PencilIcon className="w-4 h-4" />
                                                    </Link>
                                                )}
                                                {canDelete && (
                                                    <button onClick={() => setQuotationToDelete(q)} title="حذف" className="p-2 bg-red-100 text-red-700 rounded-full hover:bg-red-200">
                                                        <TrashIcon className="w-4 h-4" />
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            )}
        </>
    );
};

export default QuotationsListPage;
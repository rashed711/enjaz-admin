

import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Quotation, Currency, PermissionModule, PermissionAction } from '../types';
import { useAuth } from '../hooks/useAuth';
import { useProducts } from '../contexts/ProductContext';
import { usePermissions } from '../hooks/usePermissions';
import { supabase } from '../services/supabaseClient';
import EmptyState from '../components/EmptyState';
import Spinner from '../components/Spinner';
import DocumentTextIcon from '../components/icons/DocumentTextIcon';
import Pagination from '../components/Pagination';

const QuotationsListPage: React.FC = () => {
    const [quotations, setQuotations] = useState<Quotation[]>([]);
    const [loading, setLoading] = useState(true);
    const { currentUser, loading: isAuthLoading } = useAuth();
    const { fetchProducts } = useProducts();
    const navigate = useNavigate();
    const permissions = usePermissions();

    // Pagination state
    const [currentPage, setCurrentPage] = useState(1);
    const [itemsPerPage] = useState(15);
    const [totalCount, setTotalCount] = useState(0);

    const canCreate = permissions.can(PermissionModule.QUOTATIONS, PermissionAction.CREATE);
    const canViewAll = permissions.can(PermissionModule.QUOTATIONS, PermissionAction.VIEW_ALL);
    const canViewOwn = permissions.can(PermissionModule.QUOTATIONS, PermissionAction.VIEW_OWN);

    useEffect(() => {
        const fetchQuotations = async () => {
            // Don't fetch until the auth state is confirmed
            if (isAuthLoading) {
                return;
            }
            
            // If auth is resolved and there's no user, stop loading and show empty state.
            if (!currentUser) { setLoading(false); setQuotations([]); return; }

            setLoading(true);
            try {
                if (!canViewAll && !canViewOwn) {
                    setQuotations([]);
                    setLoading(false);
                    return;
                }

                // Calculate the range for the current page
                const from = (currentPage - 1) * itemsPerPage;
                const to = from + itemsPerPage - 1;

                let query = supabase.from('quotations').select('*', { count: 'exact' });
                
                if (!canViewAll && canViewOwn) {
                    query = query.eq('created_by', currentUser.id);
                }
                
                const { data, error, count } = await query
                    .order('date', { ascending: false })
                    .range(from, to);

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
                        taxIncluded: q.tax_included ?? false,
                        discount: q.discount || 0,
                        creatorName: 'غير معروف', // This data is no longer fetched
                    }));
                    setQuotations(formattedQuotations);
                    setTotalCount(count ?? 0);
                }
            } catch (e: any) {
                console.error("An unexpected error occurred while fetching quotations:", e.message);
                setQuotations([]);
            } finally {
                setLoading(false);
            }
        };

        fetchQuotations();
    }, [currentUser, isAuthLoading, permissions, canViewAll, canViewOwn, currentPage, itemsPerPage]);

    return (
        <>
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
                <div className="hidden md:block bg-card rounded-lg shadow-sm border border-border overflow-x-auto">
                    <table className="w-full text-right min-w-[700px] text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-3 py-3 font-bold text-text-secondary sticky right-0 bg-slate-50 border-l border-border">رقم العرض</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">التاريخ</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الشركة</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">المسئول</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">المشروع</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody className="text-text-primary divide-y divide-border">
                            {quotations.map((q) => {
                                return (
                                    <tr 
                                        key={q.id} 
                                        className="hover:bg-slate-100 even:bg-slate-50/50 cursor-pointer"
                                        onClick={() => navigate(`/quotations/${q.id}/view`)}
                                    >
                                        <td className={`px-3 py-3 whitespace-nowrap font-semibold sticky right-0 bg-inherit border-l border-border ${!q.taxIncluded ? 'text-orange-600' : ''}`}>{q.quotationNumber}</td>
                                        <td className="px-3 py-3 whitespace-nowrap">{q.date}</td>
                                        <td className="px-3 py-3">{q.company}</td>
                                        <td className="px-3 py-3">{q.clientName}</td>
                                        <td className="px-3 py-3">{q.project}</td>
                                        <td className="px-3 py-3 whitespace-nowrap">{q.totalAmount?.toLocaleString()} {q.currency}</td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                    <Pagination
                        currentPage={currentPage}
                        totalCount={totalCount}
                        itemsPerPage={itemsPerPage}
                        onPageChange={page => setCurrentPage(page)}
                    />
                </div>
            )}

            {/* --- Mobile Card View --- */}
            {!loading && quotations.length > 0 && (
                <div className="md:hidden space-y-4">
                    {quotations.map((q) => (
                        <div 
                            key={q.id} 
                            className="bg-card border border-border rounded-lg p-4 shadow-sm active:bg-slate-50 even:bg-slate-50/50"
                            onClick={() => navigate(`/quotations/${q.id}/view`)}
                        >
                            <div className="flex justify-between items-start mb-3">
                                <p className="font-bold text-lg text-primary">{q.quotationNumber}</p>
                                <span className={`px-2 py-1 text-xs font-semibold rounded-full ${!q.taxIncluded ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700'}`}>
                                    {!q.taxIncluded ? 'غير شامل ضريبة' : 'شامل ضريبة'}
                                </span>
                            </div>
                            <div className="space-y-2 text-sm">
                                <div className="flex justify-between"><span className="text-text-secondary">الشركة:</span> <span className="font-medium text-right">{q.company}</span></div>
                                <div className="flex justify-between"><span className="text-text-secondary">المشروع:</span> <span className="font-medium text-right">{q.project}</span></div>
                                <div className="flex justify-between"><span className="text-text-secondary">التاريخ:</span> <span className="font-medium">{q.date}</span></div>
                                <div className="flex justify-between pt-2 border-t border-border mt-2"><span className="text-text-secondary">الإجمالي:</span> <span className="font-bold text-lg">{q.totalAmount?.toLocaleString()} {q.currency}</span></div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </>
    );
};

export default QuotationsListPage;
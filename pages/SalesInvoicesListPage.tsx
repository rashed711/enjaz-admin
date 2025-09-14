
import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { SalesInvoice, Currency, PermissionModule, PermissionAction } from '../types';
import { useAuth } from '../hooks/useAuth';
import { usePermissions } from '../hooks/usePermissions';
import { supabase } from '../services/supabaseClient';
import EmptyState from '../components/EmptyState';
import Spinner from '../components/Spinner';
import DocumentTextIcon from '../components/icons/DocumentTextIcon';
import { getStatusChipClassName } from '../utils/uiHelpers';
import Pagination from '../components/Pagination';

const SalesInvoicesListPage: React.FC = () => {
    const [invoices, setInvoices] = useState<SalesInvoice[]>([]);
    const [loading, setLoading] = useState(true);
    const { currentUser, loading: isAuthLoading } = useAuth();
    const permissions = usePermissions();
    const navigate = useNavigate();

    // Pagination state
    const [currentPage, setCurrentPage] = useState(1);
    const [itemsPerPage] = useState(15);
    const [totalCount, setTotalCount] = useState(0);

    const canCreate = permissions.can(PermissionModule.SALES_INVOICES, PermissionAction.CREATE);
    const canViewAll = permissions.can(PermissionModule.SALES_INVOICES, PermissionAction.VIEW_ALL);
    const canViewOwn = permissions.can(PermissionModule.SALES_INVOICES, PermissionAction.VIEW_OWN);

    useEffect(() => {
        const fetchInvoices = async () => {
            // Don't fetch until the auth state is confirmed
            if (isAuthLoading) {
                return;
            }
            
            // If auth is resolved and there's no user, stop loading and show empty state.
            if (!currentUser) { setLoading(false); setInvoices([]); return; }

            setLoading(true);
            try {
                if (!canViewAll && !canViewOwn) {
                    setInvoices([]);
                    setLoading(false);
                    return;
                }

                // Calculate the range for the current page
                const from = (currentPage - 1) * itemsPerPage;
                const to = from + itemsPerPage - 1;

                let query = supabase
                    .from('sales_invoices')
                    .select('*', { count: 'exact' });

                if (!canViewAll && canViewOwn) {
                    query = query.eq('created_by', currentUser.id);
                }

                let { data, error, count } = await query
                    .order('date', { ascending: false })
                    .range(from, to);

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
                        creatorName: 'غير معروف', // This data is no longer fetched
                    }));
                    setInvoices(formattedInvoices);
                    setTotalCount(count ?? 0);
                }
            } catch (e: any) {
                console.error("An unexpected error occurred while fetching sales invoices:", e.message);
                setInvoices([]);
            } finally {
                setLoading(false);
            }
        };

        fetchInvoices();
    }, [currentUser, isAuthLoading, permissions, canViewAll, canViewOwn, currentPage, itemsPerPage]);

    return (
        <>
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
                <>
                    <div className="hidden lg:block bg-card rounded-lg shadow-sm border border-border overflow-x-auto">
                    <table className="w-full text-right min-w-[800px] text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-3 py-3 font-bold text-text-secondary sticky right-0 bg-slate-50 border-l border-border">رقم الفاتورة</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">التاريخ</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الشركة</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">المسئول</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الحالة</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody className="text-text-primary divide-y divide-border">
                            {invoices.map((i) => (
                                <tr 
                                    key={i.id} 
                                    className="hover:bg-slate-100 even:bg-slate-50/50 cursor-pointer"
                                    onClick={() => navigate(`/sales-invoices/${i.id}/view`)}
                                >
                                    <td className="px-3 py-3 whitespace-nowrap font-semibold sticky right-0 bg-inherit border-l border-border">{i.invoiceNumber}</td>
                                    <td className="px-3 py-3 whitespace-nowrap">{i.date}</td>
                                    <td className="px-3 py-3">{i.company}</td>
                                    <td className="px-3 py-3">{i.clientName}</td>
                                    <td className="px-3 py-3 whitespace-nowrap"><span className={`px-2 py-1 text-xs font-semibold rounded-full ${getStatusChipClassName(i.status)}`}>{i.status}</span></td>
                                    <td className="px-3 py-3 whitespace-nowrap">{i.totalAmount?.toLocaleString()} {i.currency}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    </div>
                    <Pagination
                        currentPage={currentPage}
                        totalCount={totalCount}
                        itemsPerPage={itemsPerPage}
                        onPageChange={page => setCurrentPage(page)}
                    />
                </>
            )}

            {/* --- Mobile Card View --- */}
            {!loading && invoices.length > 0 && (
                <div className="lg:hidden space-y-4">
                    {invoices.map((i) => (
                        <div 
                            key={i.id} 
                            className="bg-card border border-border rounded-lg p-4 shadow-sm active:bg-slate-50 even:bg-slate-50/50"
                            onClick={() => navigate(`/sales-invoices/${i.id}/view`)}
                        >
                            <div className="flex justify-between items-start mb-3">
                                <p className="font-bold text-lg text-primary">{i.invoiceNumber}</p>
                                <span className={`px-2 py-1 text-xs font-semibold rounded-full ${getStatusChipClassName(i.status)}`}>{i.status}</span>
                            </div>
                            <div className="space-y-2 text-sm">
                                <div className="flex justify-between"><span className="text-text-secondary">الشركة:</span> <span className="font-medium text-right">{i.company}</span></div>
                                <div className="flex justify-between"><span className="text-text-secondary">المسئول:</span> <span className="font-medium text-right">{i.clientName}</span></div>
                                <div className="flex justify-between"><span className="text-text-secondary">التاريخ:</span> <span className="font-medium">{i.date}</span></div>
                                <div className="flex justify-between pt-2 border-t border-border mt-2"><span className="text-text-secondary">الإجمالي:</span> <span className="font-bold text-lg">{i.totalAmount?.toLocaleString()} {i.currency}</span></div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </>
    );
};

export default SalesInvoicesListPage;
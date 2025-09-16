
import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { SalesInvoice, Currency, PermissionModule, PermissionAction } from '../types';
import { usePermissions } from '../hooks/usePermissions';
import EmptyState from '../components/EmptyState';
import Spinner from '../components/Spinner';
import DocumentTextIcon from '../components/icons/DocumentTextIcon';
import SearchIcon from '../components/icons/SearchIcon';
import { getStatusChipClassName } from '../utils/uiHelpers';
import Pagination from '../components/Pagination';
import { usePaginatedList } from '../hooks/usePaginatedList';
import { useDebounce } from '../hooks/useDebounce';
import { supabase } from '../services/supabaseClient';

const formatSalesInvoice = (i: any): SalesInvoice => ({
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
    createdBy: i.created_by || null,
    quotationId: i.quotation_id,
    creatorName: i.creator_name || 'غير معروف',
    createdAt: i.created_at,
});

const salesInvoiceSearchColumns = ['invoice_number', 'client_name', 'company', 'project'];

const SalesInvoicesListPage: React.FC = () => {
    const permissions = usePermissions();
    const navigate = useNavigate();
    const [searchQuery, setSearchQuery] = useState('');
    const debouncedSearchQuery = useDebounce(searchQuery, 500);
    const [quotationsMap, setQuotationsMap] = useState<Map<number, string>>(new Map());

    const { items: invoices, loading, totalCount, currentPage, setCurrentPage, itemsPerPage } = usePaginatedList({
        tableName: 'sales_invoices',
        permissionModule: PermissionModule.SALES_INVOICES,
        formatter: formatSalesInvoice,
        searchQuery: debouncedSearchQuery,
        searchColumns: salesInvoiceSearchColumns,
    });

    useEffect(() => {
        const fetchQuotationNumbers = async () => {
            const quotationIds = invoices.map(inv => inv.quotationId).filter((id): id is number => id != null);
            if (quotationIds.length === 0) return;

            const idsToFetch = quotationIds.filter(id => !quotationsMap.has(id));
            if (idsToFetch.length === 0) return;

            const { data, error } = await supabase.from('quotations').select('id, quotation_number').in('id', idsToFetch);

            if (error) {
                console.error('Error fetching quotation numbers:', error);
                return;
            }

            if (data) {
                const newMap = new Map(data.map(q => [q.id, q.quotation_number]));
                setQuotationsMap(prevMap => new Map([...prevMap, ...newMap]));
            }
        };
        if (invoices.length > 0) fetchQuotationNumbers();
    }, [invoices, quotationsMap]);

    const canCreate = permissions.can(PermissionModule.SALES_INVOICES, PermissionAction.CREATE);
    return (
        <>
            <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
                <h2 className="text-2xl font-bold text-text-primary">قائمة فواتير المبيعات</h2>
                <div className="flex flex-col sm:flex-row w-full sm:w-auto gap-4">
                    <div className="relative w-full sm:w-64">
                        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <SearchIcon className="h-5 w-5 text-gray-400" />
                        </div>
                        <input
                            type="text"
                            placeholder="ابحث بالرقم، العميل، الشركة..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="w-full border border-border bg-card text-text-primary p-2 pl-3 pr-10 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary transition-colors"
                            aria-label="Search sales invoices"
                        />
                    </div>
                    {canCreate && (
                        <button 
                            onClick={() => navigate('/sales-invoices/new')}
                            className="w-full sm:w-auto bg-green-600 text-white font-semibold px-5 py-2 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background focus:ring-green-600 shadow-md hover:shadow-lg"
                        >
                            + إنشاء فاتورة جديدة
                        </button>
                    )}
                </div>
            </div>

            {loading ? (
                <div className="flex justify-center items-center p-10"><Spinner /></div>
            ) : invoices.length === 0 && !loading ? (
                <EmptyState
                    Icon={DocumentTextIcon}
                    title={searchQuery ? 'لا توجد نتائج' : 'لا توجد فواتير مبيعات'}
                    message={searchQuery ? 'لم نجد أي فواتير تطابق بحثك.' : (canCreate ? "ابدأ بإنشاء فاتورة جديدة أو قم بتحويل عرض سعر إلى فاتورة." : "ليس لديك صلاحية لعرض فواتير المبيعات.")}
                    action={!searchQuery && canCreate ? {
                        label: '+ إنشاء فاتورة جديدة',
                        onClick: () => navigate('/sales-invoices/new')
                    } : undefined}
                />
            ) : (
                <>
                    {/* Desktop Table View */}
                    <div className="hidden lg:block bg-card rounded-lg shadow-sm border border-border overflow-x-auto">
                        <table className="w-full text-right min-w-[800px] text-sm">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-3 py-3 font-bold text-text-secondary sticky right-0 bg-slate-50 border-l border-border">رقم الفاتورة</th>
                                    <th className="px-3 py-3 font-bold text-text-secondary">الوقت</th>
                                    <th className="px-3 py-3 font-bold text-text-secondary">التاريخ</th>
                                    <th className="px-3 py-3 font-bold text-text-secondary">الشركة</th>
                                    <th className="px-3 py-3 font-bold text-text-secondary">المسئول</th>
                                    <th className="px-3 py-3 font-bold text-text-secondary">الحالة</th>
                                    <th className="px-3 py-3 font-bold text-text-secondary">أنشئ بواسطة</th>
                                    <th className="px-3 py-3 font-bold text-text-secondary">الإجمالي</th>
                                    <th className="px-3 py-3 font-bold text-text-secondary">عرض السعر</th>
                                </tr>
                            </thead>
                            <tbody className="text-text-primary divide-y divide-border">
                                {invoices.map((i) => {
                                    const quotationNumber = i.quotationId ? quotationsMap.get(i.quotationId) : null;
                                    return (
                                        <tr 
                                            key={i.id} 
                                            className="hover:bg-slate-100 even:bg-slate-50/50 cursor-pointer"
                                            onClick={() => navigate(`/sales-invoices/${i.id}/view`)}
                                        >
                                            <td className="px-3 py-3 whitespace-nowrap font-semibold sticky right-0 bg-inherit border-l border-border">{i.invoiceNumber}</td>
                                            <td className="px-3 py-3 whitespace-nowrap">{i.createdAt ? new Date(i.createdAt).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '-'}</td>
                                            <td className="px-3 py-3 whitespace-nowrap">{i.date}</td>
                                            <td className="px-3 py-3">{i.company}</td>
                                            <td className="px-3 py-3">{i.clientName}</td>
                                            <td className="px-3 py-3 whitespace-nowrap"><span className={`px-2 py-1 text-xs font-semibold rounded-full ${getStatusChipClassName(i.status)}`}>{i.status}</span></td>
                                            <td className="px-3 py-3 whitespace-nowrap">{i.creatorName}</td>
                                            <td className="px-3 py-3 whitespace-nowrap">{i.totalAmount?.toLocaleString('en-US')} {i.currency}</td>
                                            <td className="px-3 py-3 whitespace-nowrap">
                                                {quotationNumber ? (
                                                    <a href={`/quotations/${i.quotationId}/view`} onClick={(e) => { e.preventDefault(); e.stopPropagation(); navigate(`/quotations/${i.quotationId}/view`); }} className="text-blue-600 hover:underline font-semibold">{quotationNumber}</a>
                                                ) : '-'}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>

                    {/* Mobile Card View */}
                    <div className="lg:hidden space-y-4">
                        {invoices.map((i) => {
                            const quotationNumber = i.quotationId ? quotationsMap.get(i.quotationId) : null;
                            return (
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
                                        <div className="flex justify-between"><span className="text-text-secondary">الوقت:</span> <span className="font-medium">{i.createdAt ? new Date(i.createdAt).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '-'}</span></div>
                                        <div className="flex justify-between"><span className="text-text-secondary">التاريخ:</span> <span className="font-medium">{i.date}</span></div>
                                        <div className="flex justify-between"><span className="text-text-secondary">بواسطة:</span> <span className="font-medium">{i.creatorName}</span></div>
                                        <div className="flex justify-between pt-2 border-t border-border mt-2"><span className="text-text-secondary">الإجمالي:</span> <span className="font-bold text-lg">{i.totalAmount?.toLocaleString('en-US')} {i.currency}</span></div>
                                        {quotationNumber && (
                                            <div className="flex justify-between pt-2 border-t border-border mt-2">
                                                <span className="text-text-secondary">عرض السعر:</span>
                                                <a href={`/quotations/${i.quotationId}/view`} onClick={(e) => { e.preventDefault(); e.stopPropagation(); navigate(`/quotations/${i.quotationId}/view`); }} className="text-blue-600 hover:underline font-bold">{quotationNumber}</a>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    <Pagination
                        currentPage={currentPage}
                        totalCount={totalCount}
                        itemsPerPage={itemsPerPage}
                        onPageChange={page => setCurrentPage(page)}
                    />
                </>
            )}
        </>
    );
};

export default SalesInvoicesListPage;
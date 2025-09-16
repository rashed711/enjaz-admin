

import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Quotation, Currency, PermissionModule, PermissionAction } from '../types';
import { usePermissions } from '../hooks/usePermissions';
import EmptyState from '../components/EmptyState';
import Spinner from '../components/Spinner';
import DocumentTextIcon from '../components/icons/DocumentTextIcon';
import SearchIcon from '../components/icons/SearchIcon';
import Pagination from '../components/Pagination';
import { usePaginatedList } from '../hooks/usePaginatedList';
import { useDebounce } from '../hooks/useDebounce';
import { supabase } from '../services/supabaseClient';

const formatQuotation = (q: any): Quotation => ({
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
    createdBy: q.created_by || null,
    taxIncluded: q.tax_included ?? false,
    discount: q.discount || 0,
    creatorName: q.creator_name || 'غير معروف',
    createdAt: q.created_at,
});

const quotationSearchColumns = ['quotation_number', 'client_name', 'company', 'project'];

const QuotationsListPage: React.FC = () => {
    const navigate = useNavigate();
    const permissions = usePermissions();
    const [searchQuery, setSearchQuery] = useState('');
    const debouncedSearchQuery = useDebounce(searchQuery, 500);
    const [convertedInvoicesMap, setConvertedInvoicesMap] = useState<Map<number, { id: number; invoiceNumber: string }>>(new Map());

    useEffect(() => {
        const fetchConvertedInvoices = async () => {
            const { data, error } = await supabase
                .from('sales_invoices')
                .select('id, invoice_number, quotation_id')
                .not('quotation_id', 'is', null);

            if (error) {
                console.error('Error fetching converted invoices:', error);
                return;
            }

            if (data) {
                const invoiceMap = new Map<number, { id: number; invoiceNumber: string }>();
                data.forEach(inv => {
                    if (inv.quotation_id) {
                        invoiceMap.set(inv.quotation_id, { id: inv.id, invoiceNumber: inv.invoice_number });
                    }
                });
                setConvertedInvoicesMap(invoiceMap);
            }
        };
        fetchConvertedInvoices();
    }, []);

    const canCreate = permissions.can(PermissionModule.QUOTATIONS, PermissionAction.CREATE);
    const { items: quotations, loading, totalCount, currentPage, setCurrentPage, itemsPerPage } = usePaginatedList({
        tableName: 'quotations',
        permissionModule: PermissionModule.QUOTATIONS,
        formatter: formatQuotation,
        searchQuery: debouncedSearchQuery,
        searchColumns: quotationSearchColumns,
    });

    return (
        <>
            <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
                <h2 className="text-2xl font-bold text-text-primary">قائمة عروض الأسعار</h2>
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
                            aria-label="Search quotations"
                        />
                    </div>
                    {canCreate && (
                        <button 
                            onClick={() => navigate('/quotations/new')}
                            className="w-full sm:w-auto bg-green-600 text-white font-semibold px-5 py-2 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background focus:ring-green-600 shadow-md hover:shadow-lg"
                        >
                            + إنشاء عرض سعر جديد
                        </button>
                    )}
                </div>
            </div>

            {loading ? (
                <div className="flex justify-center items-center p-10">
                    <Spinner />
                </div>
            ) : quotations.length === 0 && !loading ? (
                 <EmptyState
                    Icon={DocumentTextIcon}
                    title={searchQuery ? 'لا توجد نتائج' : 'لا توجد عروض أسعار'}
                    message={searchQuery ? 'لم نجد أي عروض أسعار تطابق بحثك. حاول استخدام كلمات أخرى.' : 'ابدأ بإنشاء عرض سعر جديد لعملائك.'}
                    action={!searchQuery && canCreate ? {
                        label: '+ إنشاء عرض سعر جديد',
                        onClick: () => navigate('/quotations/new')
                    } : undefined}
                 />
            ) : (
                <>
                    {/* Desktop Table View */}
                    <div className="hidden lg:block bg-card rounded-lg shadow-sm border border-border overflow-x-auto">
                        <table className="w-full text-right min-w-[800px] text-sm">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-3 py-3 font-bold text-text-secondary sticky right-0 bg-slate-50 border-l border-border">رقم العرض</th>
                                    <th className="px-3 py-3 font-bold text-text-secondary">الوقت</th>
                                    <th className="px-3 py-3 font-bold text-text-secondary">التاريخ</th>
                                    <th className="px-3 py-3 font-bold text-text-secondary">الشركة</th>
                                    <th className="px-3 py-3 font-bold text-text-secondary">المسئول</th>
                                    <th className="px-3 py-3 font-bold text-text-secondary">المشروع</th>
                                    <th className="px-3 py-3 font-bold text-text-secondary">أنشئ بواسطة</th>
                                    <th className="px-3 py-3 font-bold text-text-secondary">الإجمالي</th>
                                    <th className="px-3 py-3 font-bold text-text-secondary">الفاتورة المرتبطة</th>
                                </tr>
                            </thead>
                            <tbody className="text-text-primary divide-y divide-border">
                                {quotations.map((q) => {
                                    const convertedInvoice = convertedInvoicesMap.get(q.id);
                                    return (
                                        <tr
                                            key={q.id}
                                            className={`hover:bg-slate-100 even:bg-slate-50/50 cursor-pointer ${convertedInvoice ? 'text-slate-400' : ''}`}
                                            onClick={() => navigate(`/quotations/${q.id}/view`)}
                                        >
                                            <td className={`px-3 py-3 whitespace-nowrap font-semibold sticky right-0 bg-inherit border-l border-border ${
                                                convertedInvoice
                                                ? 'text-slate-400'
                                                : !q.taxIncluded ? 'text-orange-600' : ''
                                            }`}>{q.quotationNumber}</td>
                                            <td className="px-3 py-3 whitespace-nowrap">{q.createdAt ? new Date(q.createdAt).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '-'}</td>
                                            <td className="px-3 py-3 whitespace-nowrap">{q.date}</td>
                                            <td className="px-3 py-3">{q.company}</td>
                                            <td className="px-3 py-3">{q.clientName}</td>
                                            <td className="px-3 py-3">{q.project}</td>
                                            <td className="px-3 py-3 whitespace-nowrap">{q.creatorName}</td>
                                            <td className="px-3 py-3 whitespace-nowrap">{q.totalAmount?.toLocaleString('en-US')} {q.currency}</td>
                                            <td className="px-3 py-3 whitespace-nowrap">
                                                {convertedInvoice ? (
                                                    <a 
                                                        href={`/sales-invoices/${convertedInvoice.id}/view`}
                                                        onClick={(e) => { e.preventDefault(); e.stopPropagation(); navigate(`/sales-invoices/${convertedInvoice.id}/view`); }}
                                                        className="text-blue-600 hover:underline font-semibold"
                                                    >
                                                        {convertedInvoice.invoiceNumber}
                                                    </a>
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
                        {quotations.map((q) => {
                            const convertedInvoice = convertedInvoicesMap.get(q.id);
                            return (
                                <div
                                    key={q.id}
                                    className={`bg-card border border-border rounded-lg p-4 shadow-sm active:bg-slate-50 even:bg-slate-50/50 ${convertedInvoice ? 'text-slate-400' : ''}`}
                                    onClick={() => navigate(`/quotations/${q.id}/view`)}
                                >
                                    <div className="flex justify-between items-start mb-3">
                                        <p className={`font-bold text-lg ${convertedInvoice ? 'text-slate-400' : 'text-primary'}`}>{q.quotationNumber}</p>
                                        <span className={`px-2 py-1 text-xs font-semibold rounded-full ${!q.taxIncluded ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700'}`}>
                                            {!q.taxIncluded ? 'غير شامل ضريبة' : 'شامل ضريبة'}
                                        </span>
                                    </div>
                                    <div className="space-y-2 text-sm">
                                        <div className="flex justify-between"><span className="text-text-secondary">الشركة:</span> <span className="font-medium text-right">{q.company}</span></div>
                                        <div className="flex justify-between"><span className="text-text-secondary">المشروع:</span> <span className="font-medium text-right">{q.project}</span></div>
                                        <div className="flex justify-between"><span className="text-text-secondary">الوقت:</span> <span className="font-medium">{q.createdAt ? new Date(q.createdAt).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '-'}</span></div>
                                        <div className="flex justify-between"><span className="text-text-secondary">التاريخ:</span> <span className="font-medium">{q.date}</span></div>
                                        <div className="flex justify-between"><span className="text-text-secondary">بواسطة:</span> <span className="font-medium">{q.creatorName}</span></div>
                                        <div className="flex justify-between pt-2 border-t border-border mt-2"><span className="text-text-secondary">الإجمالي:</span> <span className="font-bold text-lg">{q.totalAmount?.toLocaleString('en-US')} {q.currency}</span></div>
                                        {convertedInvoice && (
                                            <div className="flex justify-between pt-2 border-t border-border mt-2">
                                                <span className="text-text-secondary">الفاتورة:</span> 
                                                <a
                                                    href={`/sales-invoices/${convertedInvoice.id}/view`}
                                                    onClick={(e) => { e.preventDefault(); e.stopPropagation(); navigate(`/sales-invoices/${convertedInvoice.id}/view`); }}
                                                    className="text-blue-600 hover:underline font-bold"
                                                >
                                                    {convertedInvoice.invoiceNumber}
                                                </a>
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

export default QuotationsListPage;
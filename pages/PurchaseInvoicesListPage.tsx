import React, { useState } from 'react';
import { PurchaseInvoice, Currency, PermissionModule, PermissionAction } from '../types';
import { usePermissions } from '../hooks/usePermissions';
import EmptyState from '../components/EmptyState';
import Spinner from '../components/Spinner';
import DocumentTextIcon from '../components/icons/DocumentTextIcon';
import SearchIcon from '../components/icons/SearchIcon';
import { getStatusChipClassName } from '../utils/uiHelpers';
import Pagination from '../components/Pagination';
import { useNavigate } from 'react-router-dom';
import { usePaginatedList } from '../hooks/usePaginatedList';
import { useDebounce } from '../hooks/useDebounce';

const formatPurchaseInvoice = (i: any): PurchaseInvoice => ({
    id: i.id,
    invoiceNumber: i.invoice_number,
    supplierName: i.supplier_name,
    date: i.date,
    currency: i.currency as Currency,
    status: i.status,
    items: [],
    totalAmount: i.total_amount,
    createdBy: i.created_by,
    creatorName: i.creator_name || 'غير معروف',
});

const purchaseInvoiceSearchColumns = ['invoice_number', 'supplier_name'];
const PurchaseInvoicesListPage: React.FC = () => {
    const permissions = usePermissions();
    const navigate = useNavigate();
    const [searchQuery, setSearchQuery] = useState('');
    const debouncedSearchQuery = useDebounce(searchQuery, 500);

    const canCreate = permissions.can(PermissionModule.PURCHASE_INVOICES, PermissionAction.CREATE);
    const canView = permissions.can(PermissionModule.PURCHASE_INVOICES, PermissionAction.VIEW_ALL) || permissions.can(PermissionModule.PURCHASE_INVOICES, PermissionAction.VIEW_OWN);
    const { items: invoices, loading, totalCount, currentPage, setCurrentPage, itemsPerPage } = usePaginatedList({
        tableName: 'purchase_invoices',
        permissionModule: PermissionModule.PURCHASE_INVOICES,
        formatter: formatPurchaseInvoice,
        searchQuery: debouncedSearchQuery,
        searchColumns: purchaseInvoiceSearchColumns,
    });

    return (
        <>
            <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
                <h2 className="text-2xl font-bold text-text-primary">قائمة فواتير المشتريات</h2>
                <div className="flex flex-col sm:flex-row w-full sm:w-auto gap-4">
                    <div className="relative w-full sm:w-64">
                        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <SearchIcon className="h-5 w-5 text-gray-400" />
                        </div>
                        <input
                            type="text"
                            placeholder="ابحث بالرقم أو اسم المورد..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="w-full border border-border bg-card text-text-primary p-2 pl-3 pr-10 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary transition-colors"
                            aria-label="Search purchase invoices"
                        />
                    </div>
                    {canCreate && (
                        <button 
                            onClick={() => navigate('/purchase-invoices/new')}
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
                    title={searchQuery ? 'لا توجد نتائج' : (canView ? 'لا توجد فواتير مشتريات' : 'الوصول مرفوض')}
                    message={
                        searchQuery 
                        ? 'لم نجد أي فواتير تطابق بحثك.' 
                        : (canView ? "ابدأ بتسجيل أول فاتورة مشتريات." : "ليس لديك الصلاحية اللازمة لعرض هذه البيانات.")
                    }
                    action={!searchQuery && canCreate && canView ? {
                        label: '+ إنشاء فاتورة جديدة',
                        onClick: () => navigate('/purchase-invoices/new')
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
                                    <th className="px-3 py-3 font-bold text-text-secondary">التاريخ</th>
                                    <th className="px-3 py-3 font-bold text-text-secondary">المورد</th>
                                    <th className="px-3 py-3 font-bold text-text-secondary">الحالة</th>
                                    <th className="px-3 py-3 font-bold text-text-secondary">أنشئ بواسطة</th>
                                    <th className="px-3 py-3 font-bold text-text-secondary">الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody className="text-text-primary divide-y divide-border">
                                {invoices.map((i) => (
                                    <tr 
                                        key={i.id} 
                                        className="hover:bg-slate-100 even:bg-slate-50/50 cursor-pointer"
                                        onClick={() => navigate(`/purchase-invoices/${i.id}/view`)}
                                    >
                                        <td className="px-3 py-3 whitespace-nowrap font-semibold sticky right-0 bg-inherit border-l border-border">{i.invoiceNumber || '-'}</td>
                                        <td className="px-3 py-3 whitespace-nowrap">{i.date}</td>
                                        <td className="px-3 py-3">{i.supplierName}</td>
                                        <td className="px-3 py-3 whitespace-nowrap"><span className={`px-2 py-1 text-xs font-semibold rounded-full ${getStatusChipClassName(i.status)}`}>{i.status}</span></td>
                                        <td className="px-3 py-3 whitespace-nowrap">{i.creatorName}</td>
                                        <td className="px-3 py-3 whitespace-nowrap">{i.totalAmount?.toLocaleString()} {i.currency}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Mobile Card View */}
                    <div className="lg:hidden space-y-4">
                        {invoices.map((i) => (
                            <div 
                                key={i.id} 
                                className="bg-card border border-border rounded-lg p-4 shadow-sm active:bg-slate-50 even:bg-slate-50/50"
                                onClick={() => navigate(`/purchase-invoices/${i.id}/view`)}
                            >
                                <div className="flex justify-between items-start mb-3">
                                    <p className="font-bold text-lg text-primary">{i.invoiceNumber || `فاتورة من ${i.supplierName}`}</p>
                                    <span className={`px-2 py-1 text-xs font-semibold rounded-full ${getStatusChipClassName(i.status)}`}>{i.status}</span>
                                </div>
                                <div className="space-y-2 text-sm">
                                    <div className="flex justify-between"><span className="text-text-secondary">المورد:</span> <span className="font-medium text-right">{i.supplierName}</span></div>
                                    <div className="flex justify-between"><span className="text-text-secondary">التاريخ:</span> <span className="font-medium">{i.date}</span></div>
                                    <div className="flex justify-between"><span className="text-text-secondary">بواسطة:</span> <span className="font-medium">{i.creatorName}</span></div>
                                    <div className="flex justify-between pt-2 border-t border-border mt-2"><span className="text-text-secondary">الإجمالي:</span> <span className="font-bold text-lg">{i.totalAmount?.toLocaleString()} {i.currency}</span></div>
                                </div>
                            </div>
                        ))}
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

export default PurchaseInvoicesListPage;
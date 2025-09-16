import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Receipt, PermissionModule, PermissionAction } from '../types';
import { usePermissions } from '../hooks/usePermissions';
import EmptyState from '../components/EmptyState';
import Spinner from '../components/Spinner';
import ArchiveBoxIcon from '../components/icons/ArchiveBoxIcon';
import { usePaginatedList } from '../hooks/usePaginatedList';
import { useDebounce } from '../hooks/useDebounce';
import Pagination from '../components/Pagination';
import SearchIcon from '../components/icons/SearchIcon';

const formatReceipt = (receipt: any): Receipt => ({
    id: receipt.id,
    date: receipt.date,
    amount: receipt.amount,
    payment_method: receipt.payment_method,
    description: receipt.description,
    account_id: receipt.account_id,
    cash_account_id: receipt.cash_account_id,
    createdBy: receipt.created_by || null,
    creatorName: receipt.creator_name || 'غير معروف',
    account_name: receipt.account_name,
    cash_account_name: receipt.cash_account_name,
    createdAt: receipt.created_at,
});

const receiptSearchColumns = ['description', 'payment_method', 'account_name', 'cash_account_name'];

const formatCurrency = (amount: number) => amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const ReceiptsListPage: React.FC = () => {
    const navigate = useNavigate();
    const permissions = usePermissions();
    const [searchQuery, setSearchQuery] = useState('');
    const debouncedSearchQuery = useDebounce(searchQuery, 500);

    const canCreate = permissions.can(PermissionModule.RECEIPTS, PermissionAction.CREATE);

    const { items: receipts, loading, totalCount, currentPage, setCurrentPage, itemsPerPage } = usePaginatedList({
        // تم الإرجاع لاستخدام الـ view الذي يحتوي على أسماء الحسابات بعد التأكد من إنشائه في قاعدة البيانات
        tableName: 'receipts_with_names',
        permissionModule: PermissionModule.RECEIPTS,
        formatter: formatReceipt,
        searchQuery: debouncedSearchQuery,
        searchColumns: receiptSearchColumns,
    });

    const handleAddClick = () => {
        navigate('/accounts/receipts/new');
    };

    return (
        <>
            <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
                <h2 className="text-2xl font-bold text-text-primary">سندات القبض</h2>
                <div className="flex flex-col sm:flex-row w-full sm:w-auto gap-4">
                    <div className="relative w-full sm:w-64">
                        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <SearchIcon className="h-5 w-5 text-gray-400" />
                        </div>
                        <input
                            type="text"
                            placeholder="ابحث..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="w-full border border-border bg-card text-text-primary p-2 pl-3 pr-10 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary transition-colors"
                            aria-label="Search receipts"
                        />
                    </div>
                    {canCreate && (
                        <button onClick={handleAddClick} className="w-full sm:w-auto bg-green-600 text-white font-semibold px-5 py-2 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background focus:ring-green-600 shadow-md hover:shadow-lg">
                            + إضافة سند قبض جديد
                        </button>
                    )}
                </div>
            </div>

            {loading ? (
                <div className="flex justify-center items-center p-10"><Spinner /></div>
            ) : receipts.length === 0 ? (
                <EmptyState
                    Icon={ArchiveBoxIcon}
                    title="لا توجد سندات قبض"
                    message={searchQuery ? 'لم نجد أي سندات تطابق بحثك.' : 'ابدأ بتسجيل أول سند قبض في النظام.'}
                    action={!searchQuery && canCreate ? { label: '+ إضافة سند قبض جديد', onClick: handleAddClick } : undefined}
                />
            ) : (
                <div className="bg-card rounded-lg shadow-sm border border-border overflow-x-auto">
                    <table className="w-full text-right min-w-[800px] text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-3 py-3 font-bold text-text-secondary">#</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الوقت</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">التاريخ</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الحساب الدائن</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الحساب المدين</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">المبلغ</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">طريقة الدفع</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الوصف</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">أنشئ بواسطة</th>
                            </tr>
                        </thead>
                        <tbody className="text-text-primary divide-y divide-border">
                            {receipts.map((receipt) => (
                                <tr key={receipt.id} className="hover:bg-slate-100 even:bg-slate-50/50 cursor-pointer" onClick={() => navigate(`/accounts/receipts/${receipt.id}/view`)}>
                                    <td className="px-3 py-2 whitespace-nowrap font-mono">{receipt.id}</td>
                                    <td className="px-3 py-2 whitespace-nowrap">{receipt.createdAt ? new Date(receipt.createdAt).toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' }) : '-'}</td>
                                    <td className="px-3 py-2 whitespace-nowrap">{new Date(receipt.date).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' })}</td>
                                    <td className="px-3 py-2 font-semibold">{receipt.account_name}</td>
                                    <td className="px-3 py-2">{receipt.cash_account_name}</td>
                                    <td className="px-3 py-2 font-mono">{formatCurrency(receipt.amount)}</td>
                                    <td className="px-3 py-2">{receipt.payment_method}</td>
                                    <td className="px-3 py-2">{receipt.description}</td>
                                    <td className="px-3 py-2">{receipt.creatorName}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    <Pagination currentPage={currentPage} totalCount={totalCount} itemsPerPage={itemsPerPage} onPageChange={setCurrentPage} />
                </div>
            )}
        </>
    );
};

export default ReceiptsListPage;
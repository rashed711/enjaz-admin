import React, { useState } from 'react';
import { JournalEntry, PermissionModule, PermissionAction } from '../types';
import { usePermissions } from '../hooks/usePermissions';
import EmptyState from '../components/EmptyState';
import Spinner from '../components/Spinner';
import BanknotesIcon from '../components/icons/BanknotesIcon';
import { usePaginatedList } from '../hooks/usePaginatedList';
import { useDebounce } from '../hooks/useDebounce';
import Pagination from '../components/Pagination';

const formatJournalEntry = (entry: any): JournalEntry => ({
    id: entry.id,
    date: entry.date,
    description: entry.description,
    debit: entry.debit,
    credit: entry.credit,
    account_id: entry.account_id,
    account_name: entry.account_name,
    account_code: entry.account_code,
    createdBy: entry.created_by || null,
    creatorName: 'غير معروف', // Initialize with a default value
});

const formatCurrency = (amount: number) => amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const journalEntrySearchColumns = ['description', 'account_name', 'account_code'];

const JournalEntriesListPage: React.FC = () => {
    const permissions = usePermissions();
    const [searchQuery, setSearchQuery] = useState('');
    const debouncedSearchQuery = useDebounce(searchQuery, 500);

    const canCreate = permissions.can(PermissionModule.JOURNAL_ENTRIES, PermissionAction.CREATE);

    const { items: entries, loading, totalCount, currentPage, setCurrentPage, itemsPerPage } = usePaginatedList({
        tableName: 'journal_entries_with_account_name',
        permissionModule: PermissionModule.JOURNAL_ENTRIES,
        formatter: formatJournalEntry,
        searchQuery: debouncedSearchQuery,
        searchColumns: journalEntrySearchColumns,
    });

    return (
        <>
            <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
                <h2 className="text-2xl font-bold text-text-primary">القيود اليومية</h2>
                {canCreate && (
                    <button className="w-full sm:w-auto bg-green-600 text-white font-semibold px-5 py-2 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background focus:ring-green-600 shadow-md hover:shadow-lg">
                        + إضافة قيد جديد
                    </button>
                )}
            </div>

            {loading ? (
                <div className="flex justify-center items-center p-10"><Spinner /></div>
            ) : entries.length === 0 ? (
                <EmptyState
                    Icon={BanknotesIcon}
                    title="لا توجد قيود يومية"
                    message="ابدأ بإضافة أول قيد محاسبي في النظام."
                    action={canCreate ? { label: '+ إضافة قيد جديد', onClick: () => { /* TODO */ } } : undefined}
                />
            ) : (
                <div className="bg-card rounded-lg shadow-sm border border-border overflow-x-auto">
                    <table className="w-full text-right min-w-[800px] text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-3 py-3 font-bold text-text-secondary">التاريخ</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الحساب</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الوصف</th>
                                <th className="px-3 py-3 font-bold text-text-secondary text-center">مدين (Debit)</th>
                                <th className="px-3 py-3 font-bold text-text-secondary text-center">دائن (Credit)</th>
                            </tr>
                        </thead>
                        <tbody className="text-text-primary divide-y divide-border">
                            {entries.map((entry) => (
                                <tr key={entry.id} className="hover:bg-slate-100 even:bg-slate-50/50">
                                    <td className="px-3 py-2 whitespace-nowrap">{new Date(entry.date).toLocaleDateString('ar-EG')}</td>
                                    <td className="px-3 py-2 font-semibold">{entry.account_name} ({entry.account_code})</td>
                                    <td className="px-3 py-2">{entry.description}</td>
                                    <td className="px-3 py-2 text-center font-mono">{entry.debit > 0 ? formatCurrency(entry.debit) : '-'}</td>
                                    <td className="px-3 py-2 text-center font-mono">{entry.credit > 0 ? formatCurrency(entry.credit) : '-'}</td>
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

export default JournalEntriesListPage;
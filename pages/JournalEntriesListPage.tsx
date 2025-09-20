import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { JournalEntry, PermissionModule, PermissionAction } from '../types';
import { usePermissions } from '../hooks/usePermissions';
import EmptyState from '../components/EmptyState';
import Spinner from '../components/Spinner';
import BanknotesIcon from '../components/icons/BanknotesIcon';
import { usePaginatedList } from '../hooks/usePaginatedList';
import { useDebounce } from '../hooks/useDebounce';
import Pagination from '../components/Pagination';
import DeleteConfirmationModal from '../components/DeleteConfirmationModal';
import { supabase } from '../services/supabaseClient';
import TrashIcon from '../components/icons/TrashIcon';
import SearchIcon from '../components/icons/SearchIcon';

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
    creatorName: entry.creator_name || 'غير معروف',
    createdAt: entry.created_at,
});

const formatCurrency = (amount: number) => amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const journalEntrySearchColumns = ['description', 'account_name', 'account_code'];

const JournalEntriesListPage: React.FC = () => {
    const navigate = useNavigate();
    const permissions = usePermissions();
    const [searchQuery, setSearchQuery] = useState('');
    const debouncedSearchQuery = useDebounce(searchQuery, 500);

    const [entryToDelete, setEntryToDelete] = useState<JournalEntry | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [deleteError, setDeleteError] = useState<string | null>(null);

    const canCreate = permissions.can(PermissionModule.JOURNAL_ENTRIES, PermissionAction.CREATE);
    const canDelete = permissions.can(PermissionModule.JOURNAL_ENTRIES, PermissionAction.DELETE_ALL);

    const { items: entries, loading, error, totalCount, currentPage, setCurrentPage, itemsPerPage, refetch } = usePaginatedList({
        tableName: 'journal_entries_with_account_name',
        permissionModule: PermissionModule.JOURNAL_ENTRIES,
        formatter: formatJournalEntry,
        searchQuery: debouncedSearchQuery,
        searchColumns: journalEntrySearchColumns,
    });

    const handleAddClick = () => {
        // سيتم تنفيذ هذه الشاشة لاحقًا
        alert('سيتم تنفيذ شاشة إضافة القيد في الخطوة التالية.');
        // navigate('/accounts/journal-entries/new');
    };

    const handleDeleteClick = (entry: JournalEntry) => {
        if (!canDelete) {
            alert('ليس لديك الصلاحية لحذف القيود.');
            return;
        }
        setEntryToDelete(entry);
    };

    const handleConfirmDelete = async () => {
        if (!entryToDelete) return;

        setIsDeleting(true);
        setDeleteError(null);

        const { error } = await supabase
            .from('journal_entries')
            .delete()
            .eq('id', entryToDelete.id);

        if (error) {
            console.error('Error deleting journal entry:', error);
            setDeleteError(error.message || 'فشل حذف القيد.');
        } else {
            setEntryToDelete(null);
            await refetch(); // Refetch the list after successful deletion
        }
        setIsDeleting(false);
    };

    return (
        <>
            <DeleteConfirmationModal
                isOpen={!!entryToDelete}
                onClose={() => { setEntryToDelete(null); setDeleteError(null); }}
                onConfirm={handleConfirmDelete}
                title="تأكيد الحذف"
                message={`هل أنت متأكد أنك تريد حذف هذا القيد؟ لا يمكن التراجع عن هذا الإجراء.`}
                isProcessing={isDeleting}
                error={deleteError}
            />
            <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
                <h2 className="text-2xl font-bold text-text-primary">القيود اليومية</h2>
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
                            aria-label="Search journal entries"
                        />
                    </div>
                    {canCreate && (
                        <button onClick={handleAddClick} className="w-full sm:w-auto bg-green-600 text-white font-semibold px-5 py-2 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background focus:ring-green-600 shadow-md hover:shadow-lg">
                            + إضافة قيد جديد
                        </button>
                    )}
                </div>
            </div>

            {loading ? (
                <div className="flex justify-center items-center p-10"><Spinner /></div>
            ) : error ? (
                <div className="p-4 my-6 text-center text-red-700 bg-red-50 border border-red-200 rounded-lg">
                    <p className="font-bold">حدث خطأ</p>
                    <p className="mt-2 font-mono text-sm">{error}</p>
                </div>
            ) : entries.length === 0 ? (
                <EmptyState
                    Icon={BanknotesIcon}
                    title="لا توجد قيود يومية"
                    message={searchQuery ? 'لم نجد أي قيود تطابق بحثك.' : 'ابدأ بإضافة أول قيد محاسبي في النظام.'}
                    action={!searchQuery && canCreate ? { label: '+ إضافة قيد جديد', onClick: handleAddClick } : undefined}
                />
            ) : (
                <>
                    {/* Desktop Table View */}
                    <div className="hidden lg:block bg-card rounded-lg shadow-sm border border-border overflow-x-auto">
                    <table className="w-full text-right min-w-[800px] text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-3 py-3 font-bold text-text-secondary">#</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الوقت</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">التاريخ</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الحساب</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الوصف</th>
                                <th className="px-3 py-3 font-bold text-text-secondary text-center">مدين</th>
                                <th className="px-3 py-3 font-bold text-text-secondary text-center">دائن</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">أنشئ بواسطة</th>
                                <th className="px-3 py-3 font-bold text-text-secondary text-left">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody className="text-text-primary divide-y divide-border">
                            {entries.map((entry) => (
                                <tr key={entry.id} className="hover:bg-slate-100 even:bg-slate-50/50">
                                    <td className="px-3 py-2 whitespace-nowrap font-mono">{entry.id}</td>
                                    <td className="px-3 py-2 whitespace-nowrap">{entry.createdAt ? new Date(entry.createdAt).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '-'}</td>
                                    <td className="px-3 py-2 whitespace-nowrap">{new Date(entry.date).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' })}</td>
                                    <td className="px-3 py-2 font-semibold">{entry.account_name} ({entry.account_code})</td>
                                    <td className="px-3 py-2">{entry.description}</td>
                                    <td className="px-3 py-2 text-center font-mono">{entry.debit > 0 ? formatCurrency(entry.debit) : '-'}</td>
                                    <td className="px-3 py-2 text-center font-mono">{entry.credit > 0 ? formatCurrency(entry.credit) : '-'}</td>
                                    <td className="px-3 py-2">{entry.creatorName}</td>
                                    <td className="px-3 py-2 text-left">
                                        {canDelete && (
                                            <button onClick={() => handleDeleteClick(entry)} title="حذف" className="p-2 bg-red-100 text-red-700 rounded-full hover:bg-red-200">
                                                <TrashIcon className="w-4 h-4" />
                                            </button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    </div>

                    {/* Mobile Card View */}
                    <div className="lg:hidden space-y-4">
                        {entries.map((entry) => (
                            <div 
                                key={entry.id} 
                                className="bg-card border border-border rounded-lg p-4 shadow-sm even:bg-slate-50/50"
                            >
                                <div className="flex justify-between items-start mb-3">
                                    <p className="font-bold text-lg text-primary">قيد #{entry.id}</p>
                                    {canDelete && (
                                        <button onClick={() => handleDeleteClick(entry)} title="حذف" className="p-2 bg-red-100 text-red-700 rounded-full hover:bg-red-200">
                                            <TrashIcon className="w-4 h-4" />
                                        </button>
                                    )}
                                </div>
                                <div className="space-y-2 text-sm">
                                    <div className="flex justify-between"><span className="text-text-secondary">التاريخ:</span> <span className="font-medium text-right">{new Date(entry.date).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' })} {entry.createdAt ? new Date(entry.createdAt).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : ''}</span></div>
                                    <div className="flex justify-between"><span className="text-text-secondary">الحساب:</span> <span className="font-semibold text-right">{entry.account_name} ({entry.account_code})</span></div>
                                    <div className="flex justify-between"><span className="text-text-secondary">الوصف:</span> <span className="font-medium text-right">{entry.description}</span></div>
                                    <div className="flex justify-between"><span className="text-text-secondary">بواسطة:</span> <span className="font-medium">{entry.creatorName}</span></div>
                                    <div className="grid grid-cols-2 gap-2 pt-2 border-t border-border mt-2 text-center">
                                        <div>
                                            <p className="text-text-secondary text-xs">مدين</p>
                                            <p className="font-bold font-mono text-lg">{entry.debit > 0 ? formatCurrency(entry.debit) : '-'}</p>
                                        </div>
                                        <div>
                                            <p className="text-text-secondary text-xs">دائن</p>
                                            <p className="font-bold font-mono text-lg">{entry.credit > 0 ? formatCurrency(entry.credit) : '-'}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    <Pagination currentPage={currentPage} totalCount={totalCount} itemsPerPage={itemsPerPage} onPageChange={setCurrentPage} />
                </>
            )}
        </>
    );
};

export default JournalEntriesListPage;
import React, { useState } from 'react';
import { Account, PermissionModule, PermissionAction } from '../types';
import { useAccounts } from '../contexts/AccountContext';
import Spinner from '../components/Spinner';
import EmptyState from '../components/EmptyState';
import BanknotesIcon from '../components/icons/BanknotesIcon';
import { usePermissions } from '../hooks/usePermissions';
import PencilIcon from '../components/icons/PencilIcon';
import TrashIcon from '../components/icons/TrashIcon';
import DeleteConfirmationModal from '../components/DeleteConfirmationModal';

interface AccountNodeProps {
    account: Account;
    level: number;
    canManage: boolean;
    onEdit: (account: Account) => void;
    onDelete: (account: Account) => void;
}

const AccountNode: React.FC<AccountNodeProps> = ({ account, level, canManage, onEdit, onDelete }) => {
    const hasChildren = account.children && account.children.length > 0;
    const paddingRight = `${level * 1.5}rem`; // 24px per level

    return (
        <>
            <tr className="hover:bg-slate-100 even:bg-slate-50/50">
                <td className="px-3 py-2 font-semibold sticky right-0 bg-inherit border-l border-border" style={{ paddingRight }}>
                    <div className="flex items-center gap-2">
                        {hasChildren && <span className="text-text-secondary font-mono text-lg">›</span>}
                        <span>{account.name}</span>
                    </div>
                </td>
                <td className="px-3 py-2 text-text-secondary font-mono">{account.code}</td>
                <td className="px-3 py-2">{account.account_type}</td>
                <td className="px-3 py-2 text-left">
                    {canManage && (
                        <div className="flex items-center justify-end gap-2">
                            <button onClick={() => onEdit(account)} title="تعديل" className="p-2 bg-indigo-100 text-indigo-700 rounded-full hover:bg-indigo-200">
                                <PencilIcon className="w-4 h-4" />
                            </button>
                            <button onClick={() => onDelete(account)} title="حذف" className="p-2 bg-red-100 text-red-700 rounded-full hover:bg-red-200">
                                <TrashIcon className="w-4 h-4" />
                            </button>
                        </div>
                    )}
                </td>
            </tr>
            {hasChildren && account.children!.map(child => (
                <AccountNode key={child.id} account={child} level={level + 1} canManage={canManage} onEdit={onEdit} onDelete={onDelete} />
            ))}
        </>
    );
};

const AccountsListPage: React.FC = () => {
    const { accountsTree, loading, deleteAccount } = useAccounts();
    const permissions = usePermissions();
    const canManage = permissions.can(PermissionModule.ACCOUNTS, PermissionAction.MANAGE);

    const [accountToDelete, setAccountToDelete] = useState<Account | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [deleteError, setDeleteError] = useState<string | null>(null);

    const handleDeleteClick = (account: Account) => {
        setAccountToDelete(account);
    };

    const handleEditClick = (account: Account) => {
        // TODO: Implement edit modal/page
        alert(`سيتم تنفيذ شاشة تعديل الحساب "${account.name}" لاحقًا.`);
    };

    const handleConfirmDelete = async () => {
        if (!accountToDelete) return;

        setIsDeleting(true);
        setDeleteError(null);

        const { success, error } = await deleteAccount(accountToDelete.id);

        if (success) {
            setAccountToDelete(null);
        } else {
            setDeleteError(error || "فشل حذف الحساب.");
        }
        setIsDeleting(false);
    };

    return (
        <>
            <DeleteConfirmationModal
                isOpen={!!accountToDelete}
                onClose={() => { setAccountToDelete(null); setDeleteError(null); }}
                onConfirm={handleConfirmDelete}
                title="تأكيد الحذف"
                message={
                    <>
                        هل أنت متأكد أنك تريد حذف الحساب <span className="font-bold text-text-primary">{accountToDelete?.name}</span>؟
                        <br />
                        <span className="text-red-600 text-sm">لا يمكن التراجع عن هذا الإجراء.</span>
                    </>
                }
                isProcessing={isDeleting}
                error={deleteError}
            />
            <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
                <h2 className="text-2xl font-bold text-text-primary">دليل الحسابات</h2>
                <button className="w-full sm:w-auto bg-green-600 text-white font-semibold px-5 py-2 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background focus:ring-green-600 shadow-md hover:shadow-lg">
                    + إضافة حساب جديد
                </button>
            </div>

            {loading ? (
                <div className="flex justify-center items-center p-10"><Spinner /></div>
            ) : accountsTree.length === 0 ? (
                <EmptyState
                    Icon={BanknotesIcon}
                    title="لا توجد حسابات"
                    message="ابدأ ببناء دليل الحسابات الخاص بك عن طريق إضافة حساب جديد."
                    action={{ label: '+ إضافة حساب جديد', onClick: () => { /* TODO */ } }}
                />
            ) : (
                <div className="bg-card rounded-lg shadow-sm border border-border overflow-x-auto">
                    <table className="w-full text-right min-w-[600px] text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-3 py-3 font-bold text-text-secondary sticky right-0 bg-slate-50 border-l border-border">اسم الحساب</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الكود</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">النوع</th>
                                <th className="px-3 py-3 font-bold text-text-secondary text-left">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody className="text-text-primary divide-y divide-border">
                            {accountsTree.map(account => (
                                <AccountNode key={account.id} account={account} level={0} canManage={canManage} onEdit={handleEditClick} onDelete={handleDeleteClick} />
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </>
    );
};

export default AccountsListPage;
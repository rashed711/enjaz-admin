import React, { useState } from 'react';
import { Account, PermissionModule, PermissionAction, PartyType, partyTypeLabels } from '../types';
import { supabase } from '../services/supabaseClient';
import { useAccounts } from '../contexts/AccountContext';
import Spinner from '../components/Spinner';
import EmptyState from '../components/EmptyState';
import BanknotesIcon from '../components/icons/BanknotesIcon';
import { usePermissions } from '../hooks/usePermissions';
import PencilIcon from '../components/icons/PencilIcon';
import TrashIcon from '../components/icons/TrashIcon';
import ChevronLeftIcon from '../components/icons/ChevronLeftIcon';
import DeleteConfirmationModal from '../components/DeleteConfirmationModal';
import AccountModal, { AccountFormData } from '../components/AccountModal';

interface AccountNodeProps {
    account: Account;
    level: number;
    canManage: boolean;
    onEdit: (account: Account) => void;
    onDelete: (account: Account) => void;
    isExpanded: boolean;
    onToggle: (nodeId: number) => void;
    expandedNodes: Set<number>;
}

const AccountNode: React.FC<AccountNodeProps> = ({ account, level, canManage, onEdit, onDelete, isExpanded, onToggle, expandedNodes }) => {
    const hasChildren = account.children && account.children.length > 0;
    const paddingRight = `${level * 1.5 + 0.75}rem`;

    // --- Visual Cues for Hierarchy ---
    // 1. Background color gets slightly darker for the first few levels.
    const levelColors = [
        'bg-card',       // level 0 (white)
        'bg-gray-50',    // level 1
        'bg-gray-100',   // level 2
    ];
    const bgColor = levelColors[Math.min(level, levelColors.length - 1)];

    // 2. A right-side border color that intensifies with depth for a clear visual cue.
    const borderColors = [
        'border-r-transparent', // level 0
        'border-r-indigo-200',  // level 1
        'border-r-indigo-300',  // level 2+
    ];
    const borderClass = level > 0 ? borderColors[Math.min(level, borderColors.length - 1)] : borderColors[0];

    // 3. Text color for the account name to distinguish levels.
    const textLevelColors = [
        'text-text-primary',   // level 0
        'text-indigo-700',     // level 1
        'text-sky-700',        // level 2
        'text-gray-600',       // level 3+
    ];
    const textColor = textLevelColors[Math.min(level, textLevelColors.length - 1)];

    const handleRowClick = () => {
        if (hasChildren) {
            onToggle(account.id);
        }
    };

    return (
        <>
            <tr 
                className={`${bgColor} hover:bg-indigo-50/70 transition-colors duration-150`}
                onClick={handleRowClick}
                style={{ cursor: hasChildren ? 'pointer' : 'default' }}
            >
                <td className={`px-3 py-2 font-semibold sticky right-0 bg-inherit border-l border-border border-r-4 ${borderClass}`} style={{ paddingRight }}>
                    <div className="flex items-center gap-2">
                        {hasChildren ? (
                            <ChevronLeftIcon className={`w-4 h-4 text-text-secondary transition-transform duration-200 ${isExpanded ? '-rotate-90' : 'rotate-0'}`} />
                        ) : (
                            <div className="w-4 h-4" /> // Placeholder for alignment
                        )}
                        <span className={textColor}>{account.name}</span>
                    </div>
                </td>
                <td className="px-3 py-2 text-text-secondary font-mono">{account.code}</td>
                <td className="px-3 py-2">{account.account_type}</td>
                <td className="px-3 py-2 text-text-secondary">{partyTypeLabels[account.party_type] || '-'}</td>
                <td className="px-3 py-2 text-left">
                    {canManage && (
                        <div className="flex items-center justify-end gap-2" onClick={(e) => e.stopPropagation()}>
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
            {hasChildren && isExpanded && account.children!.map(child => (
                <AccountNode key={child.id} account={child} level={level + 1} canManage={canManage} onEdit={onEdit} onDelete={onDelete} isExpanded={expandedNodes.has(child.id)} onToggle={onToggle} expandedNodes={expandedNodes} />
            ))}
        </>
    );
};

const AccountsListPage: React.FC = () => {
    // The `addAccount` and `updateAccount` functions were removed from here as they were causing an error.
    // We now handle the logic in this component and assume the hook provides a `refetch` function to reload data.
    // If the refetch function has a different name, please change it below (e.g., `refetch: refetchAccounts`).
    const { accountsTree, accountsFlat, loading, deleteAccount, refetch: refetchAccounts } = useAccounts();
    const permissions = usePermissions();
    const canManage = permissions.can(PermissionModule.ACCOUNTS, PermissionAction.MANAGE);

    const [expandedNodes, setExpandedNodes] = useState<Set<number>>(new Set());
    // State for Modals
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingAccount, setEditingAccount] = useState<Partial<AccountFormData> | null>(null);
    const [accountToDelete, setAccountToDelete] = useState<Account | null>(null);

    // State for async operations
    const [isDeleting, setIsDeleting] = useState(false);
    const [deleteError, setDeleteError] = useState<string | null>(null);
    const [isSaving, setIsSaving] = useState(false);
    const [saveError, setSaveError] = useState<string | null>(null);

    const handleToggleNode = (nodeId: number) => {
        setExpandedNodes(prev => {
            const newSet = new Set(prev);
            if (newSet.has(nodeId)) {
                newSet.delete(nodeId);
            } else {
                newSet.add(nodeId);
            }
            return newSet;
        });
    };

    const handleAddClick = () => {
        setEditingAccount(null); // Clear previous editing data
        setIsModalOpen(true);
        setSaveError(null);
    };

    const handleDeleteClick = (account: Account) => {
        setAccountToDelete(account);
    };

    const handleEditClick = (account: Account) => {
        setEditingAccount({
            id: account.id,
            name: account.name,
            code: account.code,
            account_type: account.account_type,
            parent_id: account.parent_id,
            party_type: account.party_type,
        });
        setIsModalOpen(true);
        setSaveError(null);
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setEditingAccount(null);
        setSaveError(null);
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

    const handleSaveAccount = async (formData: AccountFormData) => {
        setIsSaving(true);
        setSaveError(null);
        try {
            let result: { success: boolean; error?: string };

            if (formData.id) {
                // Update existing account
                const { error } = await supabase
                    .from('accounts')
                    .update({
                        name: formData.name,
                        code: formData.code,
                        account_type: formData.account_type,
                        parent_id: formData.parent_id,
                        party_type: formData.party_type,
                    })
                    .eq('id', formData.id);
                
                result = { success: !error, error: error?.message };
            } else {
                // Create new account
                const { error } = await supabase
                    .from('accounts')
                    .insert({
                        name: formData.name,
                        code: formData.code,
                        account_type: formData.account_type,
                        parent_id: formData.parent_id,
                        party_type: formData.party_type,
                    });
                
                result = { success: !error, error: error?.message };
            }

            if (result.success) {
                if (refetchAccounts) await refetchAccounts(); // Reload accounts after saving
                handleCloseModal();
            } else {
                setSaveError(result.error || "فشل حفظ الحساب.");
            }
        } catch (e: any) {
            console.error("Failed to save account:", e);
            setSaveError(e.message || "حدث خطأ غير متوقع أثناء حفظ الحساب.");
        } finally {
            setIsSaving(false);
        }
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
            {canManage && (
                <AccountModal
                    isOpen={isModalOpen}
                    onClose={handleCloseModal}
                    onSave={handleSaveAccount}
                    initialData={editingAccount}
                    isSaving={isSaving}
                    error={saveError}
                    accounts={accountsTree}
                    accountsFlat={accountsFlat || []}
                />
            )}
            <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
                <h2 className="text-2xl font-bold text-text-primary">دليل الحسابات</h2>
                <button onClick={handleAddClick} disabled={!canManage} className="w-full sm:w-auto bg-green-600 text-white font-semibold px-5 py-2 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background focus:ring-green-600 shadow-md hover:shadow-lg disabled:opacity-50">
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
                    action={canManage ? { label: '+ إضافة حساب جديد', onClick: handleAddClick } : undefined}
                />
            ) : (
                <div className="bg-card rounded-lg shadow-sm border border-border overflow-x-auto">
                    <table className="w-full text-right min-w-[600px] text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-3 py-3 font-bold text-text-secondary sticky right-0 bg-slate-50 border-l border-border">اسم الحساب</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الكود</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">النوع</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">التصنيف</th>
                                <th className="px-3 py-3 font-bold text-text-secondary text-left">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody className="text-text-primary divide-y divide-border">
                            {accountsTree.map(account => (
                                <AccountNode key={account.id} account={account} level={0} canManage={canManage} onEdit={handleEditClick} onDelete={handleDeleteClick} isExpanded={expandedNodes.has(account.id)} onToggle={handleToggleNode} expandedNodes={expandedNodes} />
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </>
    );
};

export default AccountsListPage;
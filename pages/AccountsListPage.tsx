import React from 'react';
import { Account } from '../types';
import { useAccounts } from '../contexts/AccountContext';
import Spinner from '../components/Spinner';
import EmptyState from '../components/EmptyState';
import BanknotesIcon from '../components/icons/BanknotesIcon';

interface AccountNodeProps {
    account: Account;
    level: number;
}

const AccountNode: React.FC<AccountNodeProps> = ({ account, level }) => {
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
                    {/* Action buttons will go here */}
                </td>
            </tr>
            {hasChildren && account.children!.map(child => (
                <AccountNode key={child.id} account={child} level={level + 1} />
            ))}
        </>
    );
};

const AccountsListPage: React.FC = () => {
    const { accountsTree, loading } = useAccounts();

    return (
        <>
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
                                <AccountNode key={account.id} account={account} level={0} />
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </>
    );
};

export default AccountsListPage;
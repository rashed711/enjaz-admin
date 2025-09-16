import React, { useState, useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAccounts } from '../contexts/AccountContext';
import { PartyType } from '../components/AccountModal';
import Spinner from '../components/Spinner';
import EmptyState from '../components/EmptyState';
import UsersIcon from '../components/icons/UsersIcon';
import SearchIcon from '../components/icons/SearchIcon';
import { useDebounce } from '../hooks/useDebounce';

const CustomersListPage: React.FC = () => {
    const navigate = useNavigate();
    const { accountsFlat, loading: accountsLoading } = useAccounts();
    const [searchQuery, setSearchQuery] = useState('');
    const debouncedSearchQuery = useDebounce(searchQuery, 300);

    const customers = useMemo(() => {
        return accountsFlat.filter(acc => acc.party_type === PartyType.CUSTOMER || acc.party_type === PartyType.CUSTOMER_AND_SUPPLIER);
    }, [accountsFlat]);

    const filteredCustomers = useMemo(() => {
        if (!debouncedSearchQuery) {
            return customers;
        }
        return customers.filter(customer =>
            customer.name.toLowerCase().includes(debouncedSearchQuery.toLowerCase()) ||
            customer.code.toLowerCase().includes(debouncedSearchQuery.toLowerCase())
        );
    }, [customers, debouncedSearchQuery]);

    const handleCustomerClick = (customerId: number) => {
        navigate(`/accounts/customers/${customerId}/statement`);
    };

    return (
        <>
            <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
                <h2 className="text-2xl font-bold text-text-primary">قائمة العملاء</h2>
                <div className="relative w-full sm:w-64">
                    <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                        <SearchIcon className="h-5 w-5 text-gray-400" />
                    </div>
                    <input
                        type="text"
                        placeholder="ابحث بالاسم أو الكود..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        className="w-full border border-border bg-card text-text-primary p-2 pl-3 pr-10 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary transition-colors"
                        aria-label="Search customers"
                    />
                </div>
            </div>

            {accountsLoading ? (
                <div className="flex justify-center items-center p-10"><Spinner /></div>
            ) : filteredCustomers.length === 0 ? (
                <EmptyState
                    Icon={UsersIcon}
                    title={searchQuery ? 'لا توجد نتائج' : 'لا يوجد عملاء'}
                    message={searchQuery ? 'لم نجد أي عملاء يطابقون بحثك.' : 'لم يتم إضافة أي حسابات عملاء بعد. يمكنك إضافتهم من دليل الحسابات.'}
                />
            ) : (
                <div className="bg-card rounded-lg shadow-sm border border-border overflow-x-auto">
                    <table className="w-full text-right min-w-[600px] text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-3 py-3 font-bold text-text-secondary">اسم العميل</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">كود الحساب</th>
                                <th className="px-3 py-3 font-bold text-text-secondary text-left">الإجراء</th>
                            </tr>
                        </thead>
                        <tbody className="text-text-primary divide-y divide-border">
                            {filteredCustomers.map((customer) => (
                                <tr key={customer.id} className="hover:bg-slate-100 even:bg-slate-50/50 cursor-pointer" onClick={() => handleCustomerClick(customer.id)}>
                                    <td className="px-3 py-2 font-semibold">{customer.name}</td>
                                    <td className="px-3 py-2 font-mono">{customer.code}</td>
                                    <td className="px-3 py-2 text-left text-primary hover:underline font-semibold">عرض كشف الحساب</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </>
    );
};

export default CustomersListPage;
import React, { useState, useMemo, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAccounts } from '../contexts/AccountContext';
import { PartyType } from '../types';
import { supabase } from '../services/supabaseClient';
import Spinner from '../components/Spinner';
import EmptyState from '../components/EmptyState';
import UsersIcon from '../components/icons/UsersIcon';
import SearchIcon from '../components/icons/SearchIcon';
import { useDebounce } from '../hooks/useDebounce';

interface PartyListPageProps {
  partyType: 'Customer' | 'Supplier';
}

const formatCurrency = (amount: number) => {
    const absAmount = Math.abs(amount);
    const formatted = absAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    return amount < 0 ? `(${formatted})` : formatted;
};

const PartyListPage: React.FC<PartyListPageProps> = ({ partyType }) => {
    const navigate = useNavigate();
    const { accountsFlat, loading: accountsLoading } = useAccounts();
    const [searchQuery, setSearchQuery] = useState('');
    const debouncedSearchQuery = useDebounce(searchQuery, 300);

    const config = useMemo(() => ({
        Customer: {
            title: 'قائمة العملاء',
            partyTypes: [PartyType.CUSTOMER, PartyType.CUSTOMER_AND_SUPPLIER],
            emptyTitle: 'لا يوجد عملاء',
            emptyMessage: 'لم يتم إضافة أي حسابات عملاء بعد. يمكنك إضافتهم من دليل الحسابات.',
            searchEmptyMessage: 'لم نجد أي عملاء يطابقون بحثك.',
            headerName: 'اسم العميل',
            statementPath: '/accounts/customers'
        },
        Supplier: {
            title: 'قائمة الموردين',
            partyTypes: [PartyType.SUPPLIER, PartyType.CUSTOMER_AND_SUPPLIER],
            emptyTitle: 'لا يوجد موردين',
            emptyMessage: 'لم يتم إضافة أي حسابات موردين بعد.',
            searchEmptyMessage: 'لم نجد أي موردين يطابقون بحثك.',
            headerName: 'اسم المورد',
            statementPath: '/accounts/suppliers'
        }
    }), [partyType]);

    const currentConfig = config[partyType];

    const parties = useMemo(() => {
        return accountsFlat.filter(acc => currentConfig.partyTypes.includes(acc.party_type));
    }, [accountsFlat, currentConfig]);

    const filteredParties = useMemo(() => {
        if (!debouncedSearchQuery) return parties;
        return parties.filter(party =>
            party.name.toLowerCase().includes(debouncedSearchQuery.toLowerCase()) ||
            party.code?.toLowerCase().includes(debouncedSearchQuery.toLowerCase())
        );
    }, [parties, debouncedSearchQuery]);

    const handlePartyClick = (partyId: number) => {
        navigate(`${currentConfig.statementPath}/${partyId}/statement`);
    };

    return (
        <>
            <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
                <h2 className="text-2xl font-bold text-text-primary">{currentConfig.title}</h2>
                <div className="relative w-full sm:w-64">
                    <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                        <SearchIcon className="h-5 w-5 text-gray-400" />
                    </div>
                    <input type="text" placeholder="ابحث بالاسم أو الكود..." value={searchQuery} onChange={(e) => setSearchQuery(e.target.value)} className="w-full border border-border bg-card text-text-primary p-2 pl-3 pr-10 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary transition-colors" aria-label={`Search ${partyType}s`} />
                </div>
            </div>

            {accountsLoading ? (
                <div className="flex justify-center items-center p-10"><Spinner /></div>
            ) : filteredParties.length === 0 ? (
                <EmptyState Icon={UsersIcon} title={searchQuery ? 'لا توجد نتائج' : currentConfig.emptyTitle} message={searchQuery ? currentConfig.searchEmptyMessage : currentConfig.emptyMessage} />
            ) : (
                <div className="bg-card rounded-lg shadow-sm border border-border overflow-x-auto">
                    <table className="w-full text-right min-w-[600px] text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-3 py-3 font-bold text-text-secondary">{currentConfig.headerName}</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">كود الحساب</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الرصيد</th>
                                <th className="px-3 py-3 font-bold text-text-secondary text-left">الإجراء</th>
                            </tr>
                        </thead>
                        <tbody className="text-text-primary divide-y divide-border">
                            {filteredParties.map((party) => {
                                const balance = party.balance || 0;
                                return (
                                    <tr key={party.id} className="hover:bg-slate-100 even:bg-slate-50/50 cursor-pointer" onClick={() => handlePartyClick(party.id)}>
                                        <td className="px-3 py-2 font-semibold">{party.name}</td>
                                        <td className="px-3 py-2 font-mono">{party.code}</td>
                                        <td className={`px-3 py-2 font-mono font-semibold ${balance >= 0 ? 'text-red-600' : 'text-green-600'}`}>
                                            {formatCurrency(balance)}
                                        </td>
                                        <td className="px-3 py-2 text-left text-primary hover:underline font-semibold">عرض كشف الحساب</td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            )}
        </>
    );
};

export default PartyListPage;
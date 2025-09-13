import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { supabase } from '../services/supabaseClient';
import Spinner from '../components/Spinner';
import EmptyState from '../components/EmptyState';
import UsersIcon from '../components/icons/UsersIcon';
import PhoneIcon from '../components/icons/PhoneIcon';
import EnvelopeIcon from '../components/icons/EnvelopeIcon';

// Define a simple Contact type for this page
interface Contact {
    id: string;
    name: string;
    type: 'customer' | 'supplier' | 'both';
    email?: string;
    phone?: string;
}

const getContactTypeLabel = (type: string) => {
    switch (type) {
        case 'customer': return 'عميل';
        case 'supplier': return 'مورد';
        case 'both': return 'عميل ومورد';
        default: return type;
    }
};

const AccountsListPage: React.FC = () => {
    const [contacts, setContacts] = useState<Contact[]>([]);
    const [loading, setLoading] = useState(true);
    const navigate = useNavigate();

    useEffect(() => {
        const fetchContacts = async () => {
            setLoading(true);
            const { data, error } = await supabase
                .from('contacts')
                .select('*')
                .order('name', { ascending: true });

            if (error) {
                console.error('Error fetching contacts:', error);
                setContacts([]);
            } else if (data) {
                setContacts(data);
            }
            setLoading(false);
        };

        fetchContacts();
    }, []);

    return (
        <>
            <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
                <h2 className="text-2xl font-bold text-text-primary">دليل الحسابات</h2>
                {/* The "Add Contact" button will be implemented in a future phase */}
                <button
                    // onClick={() => navigate('/accounts/new')}
                    disabled // Temporarily disabled
                    className="w-full sm:w-auto bg-green-600 text-white font-semibold px-5 py-2 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background focus:ring-green-600 shadow-md hover:shadow-lg disabled:bg-gray-400 disabled:cursor-not-allowed"
                >
                    + إضافة جهة اتصال
                </button>
            </div>

            {loading ? (
                <div className="flex justify-center items-center p-10"><Spinner /></div>
            ) : contacts.length === 0 ? (
                <EmptyState
                    Icon={UsersIcon}
                    title="لا توجد جهات اتصال"
                    message="ابدأ بإضافة أول عميل أو مورد."
                    action={{
                        label: '+ إضافة جهة اتصال',
                        onClick: () => { /* Navigate to new contact page */ },
                        disabled: true, // Temporarily disabled
                    }}
                />
            ) : (
                <>
                    <div className="hidden lg:block bg-card rounded-lg shadow-sm border border-border overflow-x-auto">
                    <table className="w-full text-right min-w-[600px] text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-3 py-3 font-bold text-text-secondary">الاسم</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">النوع</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">البريد الإلكتروني</th>
                                <th className="px-3 py-3 font-bold text-text-secondary">الهاتف</th>
                                <th className="px-3 py-3 font-bold text-text-secondary text-left">الرصيد</th>
                            </tr>
                        </thead>
                        <tbody className="text-text-primary divide-y divide-border">
                            {contacts.map((contact) => (
                                <tr key={contact.id} className="hover:bg-slate-50">
                                    <td className="px-3 py-3 font-semibold">{contact.name}</td>
                                    <td className="px-3 py-3">
                                        <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                                            contact.type === 'customer' ? 'bg-blue-100 text-blue-800' : 
                                            contact.type === 'supplier' ? 'bg-yellow-100 text-yellow-800' : 'bg-purple-100 text-purple-800'
                                        }`}>
                                            {getContactTypeLabel(contact.type)}
                                        </span>
                                    </td>
                                    <td className="px-3 py-3">{contact.email || '-'}</td>
                                    <td className="px-3 py-3">{contact.phone || '-'}</td>
                                    <td className="px-3 py-3 text-left font-mono">
                                        {/* Balance calculation will be implemented in a future phase */}
                                        0.00
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    </div>

                    {/* --- Mobile Card View --- */}
                    <div className="lg:hidden space-y-4">
                        {contacts.map((contact) => (
                            <div key={contact.id} className="bg-card border border-border rounded-lg p-4 shadow-sm">
                                <div className="flex justify-between items-start mb-3">
                                    <p className="font-bold text-lg text-primary">{contact.name}</p>
                                    <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                                        contact.type === 'customer' ? 'bg-blue-100 text-blue-800' : 
                                        contact.type === 'supplier' ? 'bg-yellow-100 text-yellow-800' : 'bg-purple-100 text-purple-800'
                                    }`}>
                                        {getContactTypeLabel(contact.type)}
                                    </span>
                                </div>
                                <div className="space-y-2 text-sm text-text-secondary">
                                    {contact.email && (
                                        <div className="flex items-center gap-2">
                                            <EnvelopeIcon className="w-4 h-4" />
                                            <span>{contact.email}</span>
                                        </div>
                                    )}
                                    {contact.phone && (
                                        <div className="flex items-center gap-2">
                                            <PhoneIcon className="w-4 h-4" />
                                            <span>{contact.phone}</span>
                                        </div>
                                    )}
                                </div>
                                <div className="flex justify-between items-center pt-3 border-t border-border mt-3"><span className="text-text-secondary">الرصيد:</span><span className="font-bold text-lg font-mono">0.00</span></div>
                            </div>
                        ))}
                    </div>
                </>
            )}
        </>
    );
};

export default AccountsListPage;
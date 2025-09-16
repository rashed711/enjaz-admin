import React from 'react';
import { JournalEntry, Account } from '../types';

interface StatementComponentProps {
    party: Account;
    entries: (JournalEntry & { balance: number })[];
    finalBalance: number;
}

const formatCurrency = (amount: number) => {
    const absAmount = Math.abs(amount);
    const formatted = absAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    return amount < 0 ? `(${formatted})` : formatted;
};

const StatementComponent: React.FC<StatementComponentProps> = ({ party, entries, finalBalance }) => {
    const totalDebit = entries.reduce((sum, e) => sum + (e.debit || 0), 0);
    const totalCredit = entries.reduce((sum, e) => sum + (e.credit || 0), 0);

    return (
        /* ملاحظة: تم توحيد الخط ليعتمد على الخط الأساسي في النظام (Cairo) */
        <div id="statement-pdf" dir="rtl" className="bg-white p-8 rounded-lg shadow-lg max-w-4xl mx-auto border border-gray-200">
            {/* Header */}
            <header className="flex justify-between items-center pb-6 mb-8 border-b-2 border-primary">
                <div>
                    <h2 className="text-3xl font-bold text-gray-800">انجاز للتكنولوجيا والمقاولات</h2>
                    <p className="text-sm text-gray-500">www.EnjazTec.com</p>
                </div>
                <div className="text-left">
                    <h1 className="text-4xl font-extrabold text-primary">كشف حساب</h1>
                    <p className="text-gray-500 mt-1 text-xl">Account Statement</p>
                </div>
            </header>

            {/* Info Section */}
            <section className="grid grid-cols-2 gap-x-8 mb-10 text-sm">
                <div className="bg-slate-50 p-4 rounded-lg border border-slate-200">
                    <div className="space-y-2">
                        <p><span className="font-semibold text-gray-700 w-24 inline-block">الاسم:</span> {party.name}</p>
                        <p><span className="font-semibold text-gray-700 w-24 inline-block">الكود:</span> {party.code}</p>
                    </div>
                </div>
                <div className="bg-slate-50 p-4 rounded-lg border border-slate-200">
                    <div className="space-y-2">
                        <p><span className="font-semibold text-gray-700 w-24 inline-block">تاريخ البيان:</span> {new Date().toLocaleDateString('en-GB')}</p>
                        <div className="border-t border-slate-200 pt-2 mt-2">
                            <p className="flex justify-between items-center">
                                <span className="font-semibold text-gray-700">الرصيد الحالي:</span>
                                <span className={`text-lg font-bold ${finalBalance >= 0 ? 'text-red-600' : 'text-green-600'}`}>{formatCurrency(finalBalance)}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <main>
                <div className="overflow-x-auto">
                    <table className="w-full text-right min-w-[800px] text-sm">
                        <thead className="bg-[#10B981] text-white">
                            <tr>
                                <th className="px-3 py-3 font-bold">الوقت</th>
                                <th className="px-3 py-3 font-bold">التاريخ</th>
                                <th className="px-3 py-3 font-bold">الوصف</th>
                                <th className="px-3 py-3 font-bold text-center">مدين</th>
                                <th className="px-3 py-3 font-bold text-center">دائن</th>
                                <th className="px-3 py-3 font-bold text-center">الرصيد</th>
                            </tr>
                        </thead>
                        <tbody className="text-text-primary divide-y divide-border">
                            {entries.map((entry) => (
                                <tr key={entry.id} className="hover:bg-slate-100 even:bg-slate-50/50">
                                    <td className="px-3 py-2 whitespace-nowrap">{entry.createdAt ? new Date(entry.createdAt).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '-'}</td>
                                    <td className="px-3 py-2 whitespace-nowrap">{new Date(entry.date).toLocaleDateString('en-GB')}</td>
                                    <td className="px-3 py-2">{entry.description}</td>
                                    <td className="px-3 py-2 text-center text-red-700">{entry.debit > 0 ? formatCurrency(entry.debit) : '-'}</td>
                                    <td className="px-3 py-2 text-center text-green-700">{entry.credit > 0 ? formatCurrency(entry.credit) : '-'}</td>
                                    <td className={`px-3 py-2 text-center font-semibold ${entry.balance >= 0 ? 'text-red-600' : 'text-green-600'}`}>{formatCurrency(entry.balance)}</td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot className="bg-[#10B981] text-white font-bold">
                            <tr>
                                <td colSpan={3} className="px-3 py-3 text-left">الإجمالي</td>
                                <td className="px-3 py-3 text-center">{formatCurrency(totalDebit)}</td>
                                <td className="px-3 py-3 text-center">{formatCurrency(totalCredit)}</td>
                                <td className="px-3 py-3 text-center">{formatCurrency(finalBalance)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </main>

            <footer className="mt-16 pt-6 border-t border-gray-200 text-center text-gray-500 text-sm">
                <p>إذا كان لديكم أي استفسار بخصوص كشف الحساب هذا، يرجى التواصل معنا.</p>
                <p>شكراً لتعاملكم معنا.</p>
            </footer>
        </div>
    );
};

export default StatementComponent;
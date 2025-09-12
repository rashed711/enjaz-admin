import React, { useState, useEffect } from 'react';
import { SalesInvoice as SalesInvoiceType, Currency, Unit } from '../types';
import { supabase } from '../services/supabaseClient';

interface SalesInvoiceProps {
    invoice: SalesInvoiceType;
}

const getTaxInfo = (currency: Currency): { rate: number; label: string } => {
    switch (currency) {
        case Currency.EGP:
            return { rate: 0.14, label: 'الضريبة (14%)' };
        case Currency.SAR:
            return { rate: 0.15, label: 'الضريبة (15%)' };
        case Currency.USD:
            return { rate: 0, label: 'الضريبة (0%)' };
        default:
            return { rate: 0, label: 'الضريبة' };
    }
};

const SalesInvoice: React.FC<SalesInvoiceProps> = ({ invoice }) => {
    const [creatorName, setCreatorName] = useState<string>('');
    
    useEffect(() => {
        const fetchCreatorName = async () => {
            if (invoice.createdBy) {
                try {
                    const { data, error } = await supabase
                        .from('profiles')
                        .select('name')
                        .eq('id', invoice.createdBy)
                        .single();
                    if (error) throw error;
                    if (data) setCreatorName(data.name);
                } catch (error) {
                    console.error("Failed to fetch creator name:", error);
                    setCreatorName('غير معروف');
                }
            }
        };
        fetchCreatorName();
    }, [invoice.createdBy]);

    const subTotal = invoice.items.reduce((acc, item) => acc + item.total, 0);
    const taxInfo = getTaxInfo(invoice.currency);
    const tax = subTotal * taxInfo.rate;
    const grandTotal = subTotal + tax;

    const formatNumber = (amount: number) => {
        return amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    return (
        <div id="sales-invoice-pdf" dir="ltr" className="bg-white text-gray-800 p-8 sm:p-12 rounded-lg shadow-lg max-w-4xl mx-auto my-8 border border-gray-100">
            {/* Header Section */}
            <header className="flex justify-between items-center pb-6 mb-8 border-b-2 border-primary">
                <div className="text-left">
                    <h1 className="text-4xl md:text-5xl font-extrabold text-primary tracking-tight">INVOICE</h1>
                    <p className="text-gray-500 mt-1 text-xl md:text-2xl">فاتورة مبيعات</p>
                </div>
                <div className="text-right">
                    <h2 className="text-2xl md:text-3xl font-bold text-gray-800">انجاز للتكنولوجيا والمقاولات</h2>
                    <p className="text-sm text-gray-500">www.EnjazTec.com</p>
                </div>
            </header>

            {/* Client and Invoice Info Section */}
            <section dir="rtl" className="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6 mb-10 text-sm">
                <div className="bg-slate-50 p-4 rounded-lg border border-slate-200">
                    <div className="space-y-2">
                        <p><span className="font-semibold text-gray-700 w-32 inline-block">السادة شركة:</span> {invoice.company}</p>
                        <p><span className="font-semibold text-gray-700 w-32 inline-block">السيد المهندس/ة:</span> {invoice.clientName}</p>
                        <p><span className="font-semibold text-gray-700 w-32 inline-block">المشروع:</span> {invoice.project}</p>
                    </div>
                </div>
                <div className="bg-slate-50 p-4 rounded-lg border border-slate-200">
                    <div className="space-y-2">
                        <p><span className="font-semibold text-gray-700 w-32 inline-block">رقم الفاتورة:</span> {invoice.invoiceNumber}</p>
                        <p><span className="font-semibold text-gray-700 w-32 inline-block">التاريخ:</span> {invoice.date}</p>
                        <p><span className="font-semibold text-gray-700 w-32 inline-block">الحالة:</span> {invoice.status}</p>
                        <p><span className="font-semibold text-gray-700 w-32 inline-block">تم اصدارها بواسطة:</span> {creatorName || 'جاري التحميل...'}</p>
                    </div>
                </div>
            </section>

            {/* Items Table for Desktop */}
            <section className="mt-12 hidden md:block">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="bg-[#10B981] text-white text-left">
                            <th className="p-4 font-semibold uppercase tracking-wider">البند / الوصف</th>
                            <th className="p-4 font-semibold uppercase tracking-wider text-center">الكمية</th>
                            <th className="p-4 font-semibold uppercase tracking-wider text-center">الوحدة</th>
                            <th className="p-4 font-semibold uppercase tracking-wider text-center">سعر الوحدة</th>
                            <th className="p-4 font-semibold uppercase tracking-wider text-center">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody className="text-gray-800">
                        {invoice.items.map((item, index) => (
                            <tr key={item.id || index} className={`border-b border-gray-100 ${index % 2 !== 0 ? 'bg-[#f8f8f8]' : 'bg-white'}`}>
                                <td className="p-4 align-top text-left">{item.description}</td>
                                <td className="p-4 align-top text-center">{item.quantity}</td>
                                <td className="p-4 align-top text-center">{item.unit || Unit.COUNT}</td>
                                <td className="p-4 align-top text-center">{formatNumber(item.unitPrice)}</td>
                                <td className="p-4 align-top font-semibold text-center">{formatNumber(item.total)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </section>
            
            {/* Items Cards for Mobile */}
            <section className="mt-10 md:hidden">
                <h3 className="font-semibold text-gray-500 uppercase tracking-wider mb-4 text-left">البنود</h3>
                <div className="space-y-4">
                     {invoice.items.map((item, index) => (
                        <div key={item.id || index} className="bg-white rounded-lg p-4 shadow-sm border border-gray-100 text-left">
                           <p className="font-bold text-gray-800 mb-2">{item.description}</p>
                           <p className="text-sm text-gray-600">
                                {item.quantity} {item.unit || Unit.COUNT} × {formatNumber(item.unitPrice)}
                           </p>
                           <div className="border-t border-gray-100 my-3"></div>
                           <div className="flex justify-between items-center">
                               <span className="font-semibold text-gray-600">الإجمالي</span>
                               <span className="font-bold text-lg text-primary">{formatNumber(item.total)}</span>
                           </div>
                        </div>
                    ))}
                </div>
            </section>

            {/* Totals Section */}
            <section className="flex flex-col items-end mt-12 text-right">
                <div className="w-full sm:w-1/2 lg:w-2/5 mt-6 p-6">
                    <div className="space-y-4">
                        <div className="flex justify-between text-gray-600 font-medium">
                            <span>المجموع الفرعي</span>
                            <span>{formatNumber(subTotal)}</span>
                        </div>
                        <div className="flex justify-between text-gray-600 font-medium">
                            <span>{taxInfo.label}</span>
                            <span>{formatNumber(tax)}</span>
                        </div>
                        <div className="border-t-2 border-dashed border-gray-300 my-4"></div>
                        <div className="flex justify-between text-white bg-[#10B981] p-4 rounded-lg">
                            <span className="font-bold text-lg">الإجمالي الكلي</span>
                            <span className="font-bold text-xl">{formatNumber(grandTotal)}</span>
                        </div>
                    </div>
                </div>
            </section>
            
            {/* Footer */}
            <footer className="mt-16 pt-6 border-t border-gray-200 text-center text-gray-500 text-sm">
                <p>إذا كان لديكم أي استفسار بخصوص هذه الفاتورة، يرجى التواصل معنا.</p>
                <p>شكراً لتعاملكم معنا.</p>
            </footer>
        </div>
    );
};

export default SalesInvoice;

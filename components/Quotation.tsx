import React from 'react';
import { Quotation as QuotationType, Currency, Unit } from '../types';

interface QuotationProps {
    quotation: QuotationType;
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

const Quotation: React.FC<QuotationProps> = ({ quotation }) => {
    const subTotal = quotation.items.reduce((acc, item) => acc + item.total, 0);
    const taxInfo = getTaxInfo(quotation.currency);
    const tax = subTotal * taxInfo.rate;
    const grandTotal = subTotal + tax;
    const currency = quotation.currency;

    const formatCurrency = (amount: number) => {
        return `${amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${currency}`;
    };

    const formatNumber = (amount: number) => {
        return amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    return (
        <div id="quotation-pdf" dir="ltr" className="bg-white text-gray-900 p-6 sm:p-10 rounded-lg shadow-lg max-w-5xl mx-auto my-8 border border-gray-200">
            {/* Header Section */}
            <header className="flex justify-between items-start pb-6 mb-8 border-b-2 border-primary">
                <div className="text-left">
                    <h1 className="text-5xl font-extrabold text-primary tracking-tight">QUOTATION</h1>
                    <p className="text-gray-500 mt-1 text-2xl">عرض سعر</p>
                </div>
                <div className="text-right">
                    <h2 className="text-3xl font-bold text-gray-800">انجاز للتكنولوجيا والمقاولات</h2>
                    <p className="text-sm text-gray-500">www.EnjazTec.com</p>
                </div>
            </header>

            {/* Client and Quotation Info Section (Kept RTL) */}
            <section dir="rtl" className="grid grid-cols-1 sm:grid-cols-2 gap-8 mb-10 text-right">
                <div className="bg-slate-50 p-6 rounded-lg border border-slate-200">
                    <h3 className="font-bold text-gray-500 mb-3 border-b pb-2 text-lg">مقدم إلى:</h3>
                    <p><span className="font-semibold text-gray-700 ml-2">العميل:</span> {quotation.clientName}</p>
                    <p><span className="font-semibold text-gray-700 ml-2">الشركة:</span> {quotation.company}</p>
                    <p><span className="font-semibold text-gray-700 ml-2">المشروع:</span> {quotation.project}</p>
                </div>
                <div className="bg-slate-50 p-6 rounded-lg border border-slate-200">
                    <h3 className="font-bold text-gray-500 mb-3 border-b pb-2 text-lg">تفاصيل العرض:</h3>
                    <p><span className="font-semibold text-gray-700 ml-2">رقم العرض:</span> {quotation.quotationNumber}</p>
                    <p><span className="font-semibold text-gray-700 ml-2">التاريخ:</span> {quotation.date}</p>
                    <p><span className="font-semibold text-gray-700 ml-2">نوع العرض:</span> {quotation.quotationType}</p>
                </div>
            </section>

            {/* Items Table for Desktop */}
            <section className="mt-10 hidden md:block">
                <table className="w-full">
                    <thead className="bg-slate-100 text-slate-600">
                        <tr>
                            <th className="p-4 font-semibold uppercase tracking-wider text-sm text-left">البند / الوصف</th>
                            <th className="p-4 font-semibold uppercase tracking-wider text-sm text-center">الكمية</th>
                            <th className="p-4 font-semibold uppercase tracking-wider text-sm text-center">الوحدة</th>
                            <th className="p-4 font-semibold uppercase tracking-wider text-sm text-center">سعر الوحدة</th>
                            <th className="p-4 font-semibold uppercase tracking-wider text-sm text-center">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-200 text-gray-800">
                        {quotation.items.map((item, index) => (
                            <tr key={item.id || index}>
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
                <h3 className="font-bold text-gray-500 mb-4 text-left text-lg border-b pb-2">البنود:</h3>
                <div className="space-y-4">
                     {quotation.items.map((item, index) => (
                        <div key={item.id || index} className="bg-slate-50 rounded-lg p-4 border border-slate-200 text-left">
                            <p className="font-bold text-gray-800 mb-2">{item.description}</p>
                            <p className="text-sm text-gray-600">
                                {item.quantity} {item.unit || Unit.COUNT} × {formatNumber(item.unitPrice)}
                            </p>
                            <hr className="my-2 border-t border-slate-200"/>
                            <div className="flex justify-between items-center">
                                <span className="font-semibold text-gray-800">الإجمالي</span>
                                <span className="font-bold text-lg text-primary">{formatCurrency(item.total)}</span>
                            </div>
                        </div>
                    ))}
                </div>
            </section>

            {/* Totals Section */}
            <section className="flex justify-end mt-10">
                <div className="w-full sm:w-1/2 md:w-2/5">
                    <div className="space-y-2">
                        <div className="flex justify-between p-3">
                            <span className="font-semibold text-gray-600">المجموع الفرعي:</span>
                            <span className="font-medium">{formatCurrency(subTotal)}</span>
                        </div>
                        <div className="flex justify-between p-3 border-t border-b border-slate-200">
                            <span className="font-semibold text-gray-600">{taxInfo.label}:</span>
                            <span className="font-medium">{formatCurrency(tax)}</span>
                        </div>
                        <div className="flex justify-between p-4 bg-primary text-white rounded-lg">
                            <span className="font-bold text-xl">الإجمالي الكلي:</span>
                            <span className="font-bold text-xl">{formatCurrency(grandTotal)}</span>
                        </div>
                    </div>
                </div>
            </section>
            
            {/* Footer */}
            <footer className="mt-16 pt-6 border-t border-slate-200 text-center text-gray-500 text-sm">
                <h4 className="font-bold mb-2">ملاحظات وشروط</h4>
                <p>شكراً لاهتمامكم بخدماتنا. هذا العرض صالح لمدة 30 يوماً من تاريخه.</p>
                <p>إذا كان لديكم أي استفسار، لا تترددوا في التواصل معنا.</p>
            </footer>
        </div>
    );
};

export default Quotation;
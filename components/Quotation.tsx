
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
        <div id="quotation-pdf" className="bg-white p-4 sm:p-8 rounded-lg shadow-lg max-w-4xl mx-auto my-8 border text-gray-800">
            <header className="flex flex-col-reverse text-center sm:text-inherit sm:flex-row justify-between items-start pb-6 border-b-2 border-gray-200 gap-6">
                <div className="w-full sm:w-auto sm:text-right">
                    <h1 className="text-4xl font-bold text-[#10B981]">Quotation</h1>
                    <p className="text-gray-500 mt-2">عرض سعر</p>
                </div>
                <div className="w-full sm:w-auto sm:text-left">
                    <h2 className="text-2xl font-bold">انجاز للتكنولوجيا والمقاولات</h2>
                    <p className="text-sm">حلول إبداعية لنمو أعمالك</p>
                    <p className="text-sm">www.EnjazTec.com</p>
                </div>
            </header>

            <section className="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-8">
                <div className="text-left">
                    <h3 className="font-bold text-gray-600 mb-2">مقدم إلى:</h3>
                    <p><span className="font-semibold">العميل:</span> {quotation.clientName}</p>
                    <p><span className="font-semibold">الشركة:</span> {quotation.company}</p>
                    <p><span className="font-semibold">المشروع:</span> {quotation.project}</p>
                </div>
                <div className="text-left">
                     <div className="flex justify-start">
                        <span className="font-bold mr-2">رقم العرض:</span>
                        <span>{quotation.quotationNumber}</span>
                    </div>
                    <div className="flex justify-start mt-1">
                        <span className="font-bold mr-2">التاريخ:</span>
                        <span>{quotation.date}</span>
                    </div>
                     <div className="flex justify-start mt-1">
                        <span className="font-bold mr-2">نوع العرض:</span>
                        <span>{quotation.quotationType}</span>
                    </div>
                </div>
            </section>

            {/* Items Table for Desktop */}
            <section className="mt-8 hidden md:block">
                <table className="w-full text-left">
                    <thead className="bg-gray-100">
                        <tr>
                            <th className="p-3 font-bold text-gray-600">البند / الوصف</th>
                            <th className="p-3 font-bold text-gray-600">الكمية</th>
                            <th className="p-3 font-bold text-gray-600">الوحدة</th>
                            <th className="p-3 font-bold text-gray-600">سعر الوحدة</th>
                            <th className="p-3 font-bold text-gray-600">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        {quotation.items.map((item, index) => (
                            <tr key={item.id || index} className="border-b">
                                <td className="p-3">{item.description}</td>
                                <td className="p-3">{item.quantity}</td>
                                <td className="p-3">{item.unit || Unit.COUNT}</td>
                                <td className="p-3">{formatNumber(item.unitPrice)}</td>
                                <td className="p-3">{formatNumber(item.total)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </section>
            
            {/* Items Cards for Mobile */}
            <section className="mt-8 md:hidden">
                <h3 className="font-bold text-gray-600 mb-4 text-left">البنود:</h3>
                <div className="space-y-4">
                     {quotation.items.map((item, index) => (
                        <div key={item.id || index} className="bg-gray-50 rounded-lg p-4 border text-left">
                            <p className="font-bold text-gray-800 mb-2">{item.description}</p>
                            <p className="text-sm text-gray-600">
                                {item.quantity} {item.unit || Unit.COUNT} × {formatNumber(item.unitPrice)}
                            </p>
                            <hr className="my-2 border-t"/>
                            <div className="flex justify-between items-center">
                                <span className="font-semibold text-gray-700">Total</span>
                                <span className="font-bold text-lg text-[#10B981]">{formatCurrency(item.total)}</span>
                            </div>
                        </div>
                    ))}
                </div>
            </section>

            <section className="flex justify-end mt-8">
                <div className="w-full sm:w-1/2 md:w-2/5 text-left">
                    <div className="flex justify-between p-3 bg-gray-100 rounded-t-lg">
                        <span className="font-bold">المجموع الفرعي:</span>
                        <span>{formatCurrency(subTotal)}</span>
                    </div>
                    <div className="flex justify-between p-3 bg-gray-200">
                        <span className="font-bold">{taxInfo.label}:</span>
                        <span>{formatCurrency(tax)}</span>
                    </div>
                    <div className="flex justify-between p-4 bg-[#10B981] text-white rounded-b-lg">
                        <span className="font-bold text-lg">الإجمالي الكلي:</span>
                        <span className="font-bold text-lg">{formatCurrency(grandTotal)}</span>
                    </div>
                </div>
            </section>
            
            <footer className="mt-12 pt-6 border-t text-center text-gray-500 text-sm">
                <p>شكراً لاهتمامكم بخدماتنا. هذا العرض صالح لمدة 30 يوماً من تاريخه.</p>
                <p>إذا كان لديكم أي استفسار، لا تترددوا في التواصل معنا.</p>
            </footer>
        </div>
    );
};

export default Quotation;

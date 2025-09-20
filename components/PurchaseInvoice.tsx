import React, { useMemo } from 'react';
import { PurchaseInvoice as PurchaseInvoiceType } from '../types';
import { getStatusChipClassName } from '../utils/uiHelpers';
import { getTaxInfo } from '../hooks/useDocument';

interface PurchaseInvoiceProps {
    invoice: PurchaseInvoiceType;
}

const PurchaseInvoice: React.FC<PurchaseInvoiceProps> = ({ invoice }) => {
    const items = invoice.items || [];
    const taxInfo = getTaxInfo(invoice.currency);

    const { subTotal, taxAmount, grandTotal } = useMemo(() => {
        const subTotal = items.reduce((acc, item) => acc + (item.total || 0), 0);
        const taxAmount = invoice.taxIncluded ? subTotal * taxInfo.rate : 0;
        const grandTotal = subTotal + taxAmount;
        return { subTotal, taxAmount, grandTotal };
    }, [items, invoice.taxIncluded, invoice.currency, taxInfo.rate]);

    return (
    <div id="invoice-pdf" className="bg-white p-8 rounded-lg shadow-lg max-w-4xl mx-auto border border-gray-200">
        <header className="flex justify-between items-center pb-6 border-b-2 border-gray-100">
            <div>
                <h1 className="text-3xl font-bold text-gray-800">فاتورة مشتريات</h1>
                <p className="text-gray-500 mt-1">رقم: <span className="font-semibold text-gray-700">{invoice.invoiceNumber}</span></p>
            </div>
            <div className="text-right">
                <h2 className="text-xl font-semibold text-gray-700">شركة إنجاز</h2>
                <p className="text-gray-500 text-sm">حلول هندسية متكاملة</p>
            </div>
        </header>

        <section className="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
            <div className="bg-gray-50 p-4 rounded-lg">
                <h3 className="font-semibold text-gray-500 border-b pb-2 mb-2">فاتورة من:</h3>
                <p className="font-bold text-gray-800">{invoice.supplierName}</p>
            </div>
            <div className="bg-gray-50 p-4 rounded-lg text-right">
                <h3 className="font-semibold text-gray-500 border-b pb-2 mb-2">التفاصيل:</h3>
                <p className="text-gray-700"><strong>التاريخ:</strong> {new Date(invoice.date).toLocaleDateString('ar-EG')}</p>
                <p className="text-gray-700"><strong>الحالة:</strong> <span className={`px-2 py-1 text-xs font-semibold rounded-full ${getStatusChipClassName(invoice.status)}`}>{invoice.status}</span></p>
            </div>
        </section>

        <section className="mt-8 overflow-x-auto">
            <table className="w-full text-right min-w-[600px]">
                <thead className="bg-gray-100 text-gray-600">
                    <tr>
                        <th className="p-3 font-semibold">#</th>
                        <th className="p-3 font-semibold">الوصف</th>
                        <th className="p-3 font-semibold text-center">الكمية</th>
                        <th className="p-3 font-semibold text-center">سعر الوحدة</th>
                        <th className="p-3 font-semibold text-left">الإجمالي</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                    {(invoice.items || []).map((item, index) => (
                        <tr key={item.id || index}>
                            <td className="p-3">{index + 1}</td>
                            <td className="p-3 font-medium">{item.description}</td>
                            <td className="p-3 text-center font-mono">{item.quantity}</td>
                            <td className="p-3 text-center font-mono">{item.unitPrice?.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                            <td className="p-3 text-left font-mono font-semibold">{item.total?.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </section>

        <footer className="flex justify-end mt-8 pt-6 border-t-2 border-gray-100">
            <div className="w-full max-w-xs text-right">
                <div className="space-y-2">
                    <div className="flex justify-between items-center">
                        <span className="text-gray-600">الإجمالي الفرعي:</span>
                        <span className="font-bold text-gray-800 font-mono">{subTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} {invoice.currency}</span>
                    </div>
                    {invoice.taxIncluded && taxAmount > 0 && (
                        <div className="flex justify-between items-center">
                            <span className="text-gray-600">{taxInfo.label}:</span>
                            <span className="font-bold text-gray-800 font-mono">{taxAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} {invoice.currency}</span>
                        </div>
                    )}
                </div>
                <div className="border-t border-dashed my-3"></div>
                <div className="flex justify-between items-center text-xl font-bold text-primary bg-primary/10 p-3 rounded-lg">
                    <span>المبلغ الإجمالي:</span>
                    <span className="font-mono">{grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} {invoice.currency}</span>
                </div>
            </div>
        </footer>
    </div>
    );
};

export default PurchaseInvoice;

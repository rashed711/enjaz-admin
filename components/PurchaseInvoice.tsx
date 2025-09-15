import React from 'react';
import { PurchaseInvoice as PurchaseInvoiceType, Currency, Unit } from '../types';
import { getTaxInfo } from '../hooks/useDocument';

interface PurchaseInvoiceProps {
    invoice: PurchaseInvoiceType;
}

const formatNumber = (amount: number) => {
    return amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// --- START: Non-Responsive Print View ---
const PrintView: React.FC<{
    invoice: PurchaseInvoiceType;
    subTotal: number;
    taxInfo: { rate: number; label: string };
    tax: number;
    grandTotal: number;
}> = ({ invoice, subTotal, taxInfo, tax, grandTotal }) => (
    <div id="invoice-pdf" dir="ltr" className="bg-white text-gray-800 p-12 w-[1024px]">
        {/* Header */}
        <header className="flex justify-between items-center pb-6 mb-8 border-b-2 border-primary">
            <div className="text-left">
                <h1 className="text-5xl font-extrabold text-primary tracking-tight">INVOICE</h1>
                <p className="text-gray-500 mt-1 text-2xl">فاتورة شراء</p>
            </div>
            <div className="text-right">
                <h2 className="text-3xl font-bold text-gray-800">انجاز للتكنولوجيا والمقاولات</h2>
                <p className="text-sm text-gray-500">www.EnjazTec.com</p>
            </div>
        </header>

        {/* Info Section */}
        <section dir="rtl" className="grid grid-cols-2 gap-x-8 mb-10 text-sm">
            <div className="bg-slate-50 p-4 rounded-lg border border-slate-200">
                <div className="space-y-2">
                    <p><span className="font-semibold text-gray-700 w-32 inline-block">المورد:</span> {invoice.supplierName}</p>
                </div>
            </div>
            <div className="bg-slate-50 p-4 rounded-lg border border-slate-200">
                <div className="space-y-2">
                    <p><span className="font-semibold text-gray-700 w-32 inline-block">رقم الفاتورة:</span> {invoice.invoiceNumber}</p>
                    <p><span className="font-semibold text-gray-700 w-32 inline-block">التاريخ:</span> {invoice.date}</p>
                    <p><span className="font-semibold text-gray-700 w-32 inline-block">الحالة:</span> {invoice.status}</p>
                </div>
            </div>
        </section>
        
        {/* Items Table */}
        <section className="mt-12">
            <table className="w-full text-sm">
                <thead>
                    <tr className="bg-[#10B981] text-white text-left text-xs">
                        <th className="p-3 font-semibold uppercase tracking-wider w-1/5">Product</th>
                        <th className="p-3 font-semibold uppercase tracking-wider w-2/5">Description</th>
                        <th className="p-3 font-semibold uppercase tracking-wider text-center">Unit</th>
                        <th className="p-3 font-semibold uppercase tracking-wider text-center">Qty</th>
                        <th className="p-3 font-semibold uppercase tracking-wider text-center">Unit Price</th>
                        <th className="p-3 font-semibold uppercase tracking-wider text-center">Total</th>
                    </tr>
                </thead>
                <tbody className="text-gray-800">
                    {invoice.items.map((item, index) => (
                        <tr key={item.id || index} className={`border-b border-gray-100 ${index % 2 !== 0 ? 'bg-[#f8f8f8]' : 'bg-white'}`}>
                            <td className="p-3 align-top text-left font-semibold">{item.productName || '-'}</td>
                            <td className="p-3 align-top text-left">{item.description}</td>
                            <td className="p-3 align-top text-center">{item.unit || Unit.COUNT}</td>
                            <td className="p-3 align-top text-center">{item.quantity}</td>
                            <td className="p-3 align-top text-center">{formatNumber(item.unitPrice)}</td>
                            <td className="p-3 align-top font-semibold text-center">{formatNumber(item.total)}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </section>
        
        {/* Totals & Footer */}
        <section className="flex flex-col items-end mt-12 text-right">
            <div className="w-2/5 mt-6 p-6">
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
        <footer className="mt-16 pt-6 border-t border-gray-200 text-center text-gray-500 text-sm">
            <p>إذا كان لديكم أي استفسار بخصوص هذه الفاتورة، يرجى التواصل معنا.</p>
            <p>شكراً لتعاملكم معنا.</p>
        </footer>
    </div>
);
// --- END: Print View ---


// --- START: On-Screen Responsive View ---
const OnScreenView: React.FC<{
    invoice: PurchaseInvoiceType;
    subTotal: number;
    taxInfo: { rate: number; label: string };
    tax: number;
    grandTotal: number;
}> = ({ invoice, subTotal, taxInfo, tax, grandTotal }) => (
    <div dir="ltr" className="bg-white text-gray-800 p-4 sm:p-8 rounded-lg shadow-lg max-w-4xl mx-auto my-8 border border-gray-100">
        {/* Header */}
        <header className="flex justify-between items-center pb-6 mb-8 border-b-2 border-primary">
            <div className="text-left">
                <h1 className="text-4xl md:text-5xl font-extrabold text-primary tracking-tight">INVOICE</h1>
                <p className="text-gray-500 mt-1 text-xl md:text-2xl">فاتورة شراء</p>
            </div>
            <div className="text-right">
                <h2 className="text-2xl md:text-3xl font-bold text-gray-800">انجاز للتكنولوجيا والمقاولات</h2>
                <p className="text-sm text-gray-500">www.EnjazTec.com</p>
            </div>
        </header>

        {/* Info Section */}
        <section dir="rtl" className="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6 mb-10 text-sm">
            <div className="bg-slate-50 p-4 rounded-lg border border-slate-200">
                <div className="space-y-2">
                    <p><span className="font-semibold text-gray-700 w-32 inline-block">المورد:</span> {invoice.supplierName}</p>
                </div>
            </div>
            <div className="bg-slate-50 p-4 rounded-lg border border-slate-200">
                <div className="space-y-2">
                    <p><span className="font-semibold text-gray-700 w-32 inline-block">رقم الفاتورة:</span> {invoice.invoiceNumber}</p>
                    <p><span className="font-semibold text-gray-700 w-32 inline-block">التاريخ:</span> {invoice.date}</p>
                    <p><span className="font-semibold text-gray-700 w-32 inline-block">الحالة:</span> {invoice.status}</p>
                </div>
            </div>
        </section>

        {/* --- Responsive Items Display --- */}
        {/* Mobile View: Cards */}
        <div className="md:hidden mt-8">
            <h3 className="text-lg font-bold mb-4 text-primary border-b pb-2">البنود</h3>
            {invoice.items.map((item, index) => (
                <div key={item.id || index} className="bg-slate-50 rounded-lg p-4 mb-3 border border-slate-200 shadow-sm">
                    {item.productName && <p className="font-bold text-primary mb-1">{item.productName}</p>}
                    <p dir="auto" className={`font-semibold text-text-primary mb-3 ${item.productName ? 'text-sm' : 'text-base'}`}>{item.description}</p>
                    <div className="grid grid-cols-4 gap-x-2 text-sm border-t border-slate-200 pt-3">
                        <div className="text-center">
                            <p className="text-xs text-text-secondary">الوحدة</p>
                            <p className="font-semibold text-text-primary mt-1">{item.unit || Unit.COUNT}</p>
                        </div>
                        <div className="text-center">
                            <p className="text-xs text-text-secondary">الكمية</p>
                            <p className="font-semibold text-text-primary mt-1">{item.quantity}</p>
                        </div>
                        <div className="text-center">
                            <p className="text-xs text-text-secondary">السعر</p>
                            <p className="font-semibold text-text-primary mt-1">{formatNumber(item.unitPrice)}</p>
                        </div>
                        <div className="text-center">
                            <p className="text-xs text-text-secondary">الإجمالي</p>
                            <p className="font-bold text-primary mt-1">{formatNumber(item.total)}</p>
                        </div>
                    </div>
                </div>
            ))}
        </div>

        {/* Desktop View: Table */}
        <div className="hidden md:block overflow-x-auto mt-12">
            <table className="w-full text-sm min-w-[600px]">
                <thead>
                    <tr className="bg-[#10B981] text-white text-left text-xs">
                        <th className="p-3 font-semibold uppercase tracking-wider w-1/5">Product</th>
                        <th className="p-3 font-semibold uppercase tracking-wider w-2/5">Description</th>
                        <th className="p-3 font-semibold uppercase tracking-wider text-center">Unit</th>
                        <th className="p-3 font-semibold uppercase tracking-wider text-center">Qty</th>
                        <th className="p-3 font-semibold uppercase tracking-wider text-center">Unit Price</th>
                        <th className="p-3 font-semibold uppercase tracking-wider text-center">Total</th>
                    </tr>
                </thead>
                <tbody className="text-gray-800">
                    {invoice.items.map((item, index) => (
                        <tr key={item.id || index} className={`border-b border-gray-100 ${index % 2 !== 0 ? 'bg-[#f8f8f8]' : 'bg-white'}`}>
                            <td className="p-3 align-top text-left font-semibold">{item.productName || '-'}</td>
                            <td className="p-3 align-top text-left">{item.description}</td>
                            <td className="p-3 align-top text-center">{item.unit || Unit.COUNT}</td>
                            <td className="p-3 align-top text-center">{item.quantity}</td>
                            <td className="p-3 align-top text-center">{formatNumber(item.unitPrice)}</td>
                            <td className="p-3 align-top font-semibold text-center">{formatNumber(item.total)}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
        
        {/* Totals & Footer */}
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
        <footer className="mt-16 pt-6 border-t border-gray-200 text-center text-gray-500 text-sm">
            <p>إذا كان لديكم أي استفسار بخصوص هذه الفاتورة، يرجى التواصل معنا.</p>
            <p>شكراً لتعاملكم معنا.</p>
        </footer>
    </div>
);
// --- END: On-Screen View ---


// --- Main PurchaseInvoice Component ---
const PurchaseInvoice: React.FC<PurchaseInvoiceProps> = ({ invoice }) => {
    const subTotal = invoice.items.reduce((acc, item) => acc + item.total, 0);
    const taxInfo = getTaxInfo(invoice.currency);
    const tax = subTotal * taxInfo.rate;
    const grandTotal = subTotal + tax;

    const viewProps = { invoice, subTotal, taxInfo, tax, grandTotal };

    return (
       <>
            {/* Off-screen PDF version */}
            <div className="absolute -left-[9999px] top-0 overflow-hidden" aria-hidden="true">
                <PrintView {...viewProps} />
            </div>

            {/* Visible on-screen version */}
            <OnScreenView {...viewProps} />
        </>
    );
};

export default PurchaseInvoice;

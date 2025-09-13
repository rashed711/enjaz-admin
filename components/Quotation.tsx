import React, { useMemo } from 'react';
import { Quotation as QuotationType, Unit } from '../types';
import { getTaxInfo } from '../hooks/useDocument';
import { useUsers } from '../hooks/useUsers';

interface QuotationProps {
    quotation: QuotationType;
}

const formatNumber = (amount: number) => {
    return amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// --- START: Non-Responsive Print View ---
const PrintView: React.FC<{
    quotation: QuotationType;
    creatorName: string;
    subTotal: number;
    discount: number;
    taxInfo: { rate: number; label: string };
    tax: number;
    grandTotal: number;
}> = ({ quotation, creatorName, subTotal, discount, taxInfo, tax, grandTotal }) => (
    <div id="quotation-pdf" dir="ltr" className="bg-white text-gray-800 p-12 w-[1024px]">
        {/* Header */}
        <header className="flex justify-between items-center pb-6 mb-8 border-b-2 border-primary">
            <div className="text-left">
                <h2 className="text-3xl font-bold text-gray-800">Enjaz for Technology & Contracting</h2>
                <p className="text-sm text-gray-500">www.EnjazTec.com</p>
            </div>
            <div className="text-right">
                <h1 className="text-5xl font-extrabold text-primary tracking-tight">QUOTATION</h1>
            </div>
        </header>

        {/* Info Section */}
        <section className="grid grid-cols-2 gap-x-8 mb-10 text-sm">
            <div className="bg-slate-50 p-4 rounded-lg border border-slate-200">
                <div className="space-y-2">
                    <p><span className="font-semibold text-gray-700 w-32 inline-block">Company:</span> {quotation.company}</p>
                    <p><span className="font-semibold text-gray-700 w-32 inline-block">Attention:</span> {quotation.clientName}</p>
                    <p><span className="font-semibold text-gray-700 w-32 inline-block">Project:</span> {quotation.project}</p>
                </div>
            </div>
            <div className="bg-slate-50 p-4 rounded-lg border border-slate-200">
                <div className="space-y-2">
                    <p><span className="font-semibold text-gray-700 w-32 inline-block">Quotation No.:</span> {quotation.quotationNumber}</p>
                    <p><span className="font-semibold text-gray-700 w-32 inline-block">Date:</span> {quotation.date}</p>
                    <p><span className="font-semibold text-gray-700 w-32 inline-block">Quotation Type:</span> {quotation.quotationType}</p>
                    <p><span className="font-semibold text-gray-700 w-32 inline-block">Issued By:</span> {creatorName || 'Loading...'}</p>
                </div>
            </div>
        </section>
        
        {/* Items Table */}
        <section className="mt-12">
            <table className="w-full text-sm">
                <thead>
                    <tr className="bg-[#10B981] text-white text-left">
                        <th className="p-4 font-semibold uppercase tracking-wider">Item / Description</th>
                        <th className="p-4 font-semibold uppercase tracking-wider text-center">Quantity</th>
                        <th className="p-4 font-semibold uppercase tracking-wider text-center">Unit</th>
                        <th className="p-4 font-semibold uppercase tracking-wider text-center">Unit Price</th>
                        <th className="p-4 font-semibold uppercase tracking-wider text-center">Total</th>
                    </tr>
                </thead>
                <tbody className="text-gray-800">
                    {quotation.items.map((item, index) => (
                        <tr key={item.id || index} className={`border-b border-gray-100 ${index % 2 !== 0 ? 'bg-[#f8f8f8]' : 'bg-white'}`}>
                            <td dir="auto" className="p-4 align-top text-left">{item.description}</td>
                            <td className="p-4 align-top text-center">{item.quantity}</td>
                            <td className="p-4 align-top text-center">{item.unit || Unit.COUNT}</td>
                            <td className="p-4 align-top text-center">{formatNumber(item.unitPrice)}</td>
                            <td className="p-4 align-top font-semibold text-center">{formatNumber(item.total)}</td>
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
                        <span>Subtotal</span>
                        <span>{formatNumber(subTotal)}</span>
                    </div>
                    {discount > 0 && (
                         <div className="flex justify-between text-gray-600 font-medium">
                            <span>Discount</span>
                            <span className="text-red-500">-{formatNumber(discount)}</span>
                        </div>
                    )}
                    {quotation.taxIncluded && (
                        <div className="flex justify-between text-gray-600 font-medium">
                            <span>{taxInfo.label}</span>
                            <span>{formatNumber(tax)}</span>
                        </div>
                    )}
                    <div className="border-t-2 border-dashed border-gray-300 my-4"></div>
                    <div className="flex justify-between text-white bg-[#10B981] p-4 rounded-lg">
                        <span className="font-bold text-lg">Grand Total</span>
                        <span className="font-bold text-xl">{formatNumber(grandTotal)}</span>
                    </div>
                </div>
            </div>
        </section>
        <footer className="mt-16 pt-6 border-t border-gray-200 text-center text-gray-500 text-sm">
            <h4 className="font-bold mb-2">Notes & Terms</h4>
            <p>Thank you for your interest in our services. This quotation is valid for 30 days from its date.</p>
            <p>If you have any questions, please do not hesitate to contact us.</p>
        </footer>
    </div>
);
// --- END: Print View ---


// --- START: On-Screen Responsive View ---
const OnScreenView: React.FC<{
    quotation: QuotationType;
    creatorName: string;
    subTotal: number;
    discount: number;
    taxInfo: { rate: number; label: string };
    tax: number;
    grandTotal: number;
}> = ({ quotation, creatorName, subTotal, discount, taxInfo, tax, grandTotal }) => (
    <div dir="ltr" className="bg-white text-gray-800 p-4 sm:p-8 rounded-lg shadow-lg max-w-4xl mx-auto my-8 border border-gray-100">
        {/* Header Section */}
        <header className="flex justify-between items-center pb-6 mb-8 border-b-2 border-primary">
            <div className="text-left">
                <h2 className="text-2xl md:text-3xl font-bold text-gray-800">Enjaz for Technology & Contracting</h2>
                <p className="text-sm text-gray-500">www.EnjazTec.com</p>
            </div>
            <div className="text-right">
                <h1 className="text-4xl md:text-5xl font-extrabold text-primary tracking-tight">QUOTATION</h1>
            </div>
        </header>

        {/* Client and Quotation Info Section */}
        <section className="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6 mb-10 text-sm">
            <div className="bg-slate-50 p-4 rounded-lg border border-slate-200">
                <div className="space-y-2">
                    <p><span className="font-semibold text-gray-700 w-32 inline-block">Company:</span> {quotation.company}</p>
                    <p><span className="font-semibold text-gray-700 w-32 inline-block">Attention:</span> {quotation.clientName}</p>
                    <p><span className="font-semibold text-gray-700 w-32 inline-block">Project:</span> {quotation.project}</p>
                </div>
            </div>
            <div className="bg-slate-50 p-4 rounded-lg border border-slate-200">
                <div className="space-y-2">
                    <p><span className="font-semibold text-gray-700 w-32 inline-block">Quotation No.:</span> {quotation.quotationNumber}</p>
                    <p><span className="font-semibold text-gray-700 w-32 inline-block">Date:</span> {quotation.date}</p>
                    <p><span className="font-semibold text-gray-700 w-32 inline-block">Quotation Type:</span> {quotation.quotationType}</p>
                    <p><span className="font-semibold text-gray-700 w-32 inline-block">Issued By:</span> {creatorName || 'Loading...'}</p>
                </div>
            </div>
        </section>

        {/* --- Responsive Items Display --- */}
        {/* Mobile View: Card-based layout */}
        <div className="md:hidden mt-8">
            <h3 className="text-lg font-bold mb-4 text-primary border-b pb-2">البنود</h3>
            {quotation.items.map((item, index) => (
                <div key={item.id || index} className="bg-slate-50 rounded-lg p-4 mb-4 border border-slate-200">
                    <p dir="auto" className="font-bold text-text-primary mb-3 pb-3 border-b border-slate-200">{item.description}</p>
                    <div className="space-y-3 text-sm">
                        <div className="flex justify-between items-center">
                            <span className="text-text-secondary">الكمية</span>
                            <span className="font-semibold text-text-primary">{item.quantity} {item.unit || Unit.COUNT}</span>
                        </div>
                        <div className="flex justify-between items-center">
                            <span className="text-text-secondary">سعر الوحدة</span>
                            <span className="font-semibold text-text-primary">{formatNumber(item.unitPrice)}</span>
                        </div>
                        <div className="flex justify-between items-center pt-2 border-t border-slate-200">
                            <span className="text-text-secondary font-bold">الإجمالي</span>
                            <span className="font-bold text-primary">{formatNumber(item.total)}</span>
                        </div>
                    </div>
                </div>
            ))}
        </div>

        {/* Desktop View: Classic table */}
        <div className="hidden md:block overflow-x-auto">
            <section className="mt-12">
                <table className="w-full text-sm min-w-[600px]">
                    <thead>
                        <tr className="bg-[#10B981] text-white text-left">
                            <th className="p-4 font-semibold uppercase tracking-wider">Item / Description</th>
                            <th className="p-4 font-semibold uppercase tracking-wider text-center">Quantity</th>
                            <th className="p-4 font-semibold uppercase tracking-wider text-center">Unit</th>
                            <th className="p-4 font-semibold uppercase tracking-wider text-center">Unit Price</th>
                            <th className="p-4 font-semibold uppercase tracking-wider text-center">Total</th>
                        </tr>
                    </thead>
                    <tbody className="text-gray-800">
                        {quotation.items.map((item, index) => (
                            <tr key={item.id || index} className={`border-b border-gray-100 ${index % 2 !== 0 ? 'bg-[#f8f8f8]' : 'bg-white'}`}>
                                <td dir="auto" className="p-4 align-top text-left">{item.description}</td>
                                <td className="p-4 align-top text-center">{item.quantity}</td>
                                <td className="p-4 align-top text-center">{item.unit || Unit.COUNT}</td>
                                <td className="p-4 align-top text-center">{formatNumber(item.unitPrice)}</td>
                                <td className="p-4 align-top font-semibold text-center">{formatNumber(item.total)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </section>
        </div>
        
        {/* Totals Section */}
        <section className="flex flex-col items-end mt-12 text-right">
            <div className="w-full sm:w-1/2 lg:w-2/5 mt-6 p-6">
                <div className="space-y-4">
                    <div className="flex justify-between text-gray-600 font-medium">
                        <span>Subtotal</span>
                        <span>{formatNumber(subTotal)}</span>
                    </div>
                    {discount > 0 && (
                         <div className="flex justify-between text-gray-600 font-medium">
                            <span>Discount</span>
                            <span className="text-red-500">-{formatNumber(discount)}</span>
                        </div>
                    )}
                    {quotation.taxIncluded && (
                        <div className="flex justify-between text-gray-600 font-medium">
                            <span>{taxInfo.label}</span>
                            <span>{formatNumber(tax)}</span>
                        </div>
                    )}
                    <div className="border-t-2 border-dashed border-gray-300 my-4"></div>
                    <div className="flex justify-between text-white bg-[#10B981] p-4 rounded-lg">
                        <span className="font-bold text-lg">Grand Total</span>
                        <span className="font-bold text-xl">{formatNumber(grandTotal)}</span>
                    </div>
                </div>
            </div>
        </section>
        
        {/* Footer */}
        <footer className="mt-16 pt-6 border-t border-gray-200 text-center text-gray-500 text-sm">
            <h4 className="font-bold mb-2">Notes & Terms</h4>
            <p>Thank you for your interest in our services. This quotation is valid for 30 days from its date.</p>
            <p>If you have any questions, please do not hesitate to contact us.</p>
        </footer>
    </div>
);
// --- END: On-Screen View ---


// --- Main Quotation Component ---
const Quotation: React.FC<QuotationProps> = ({ quotation }) => {
    const { users } = useUsers();

    const creatorName = useMemo(() => {
        if (quotation.createdBy) {
            const creator = users.find(u => u.id === quotation.createdBy);
            return creator?.name || 'Unknown';
        }
        return 'Unknown';
    }, [users, quotation.createdBy]);

    const subTotal = quotation.items.reduce((acc, item) => acc + item.total, 0);
    const discount = quotation.discount || 0;
    const taxableAmount = subTotal - discount;
    const taxInfo = getTaxInfo(quotation.currency);
    const tax = quotation.taxIncluded ? taxableAmount * taxInfo.rate : 0;
    const grandTotal = taxableAmount + tax;

    const viewProps = { quotation, creatorName, subTotal, discount, taxInfo, tax, grandTotal };

    return (
        <>
            {/* Off-screen PDF version - always uses the non-responsive desktop layout */}
            <div className="absolute -left-[9999px] top-0 overflow-hidden" aria-hidden="true">
                <PrintView {...viewProps} />
            </div>

            {/* Visible on-screen version - fully responsive */}
            <OnScreenView {...viewProps} />
        </>
    );
};

export default Quotation;
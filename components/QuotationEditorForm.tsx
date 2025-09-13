



import React, { useState, useEffect, useMemo } from 'react';
import { Currency, DocumentItemState } from '../types';
import { useProducts } from '../contexts/ProductContext';
import { QuotationState } from '../pages/QuotationEditorPage';
import Spinner from './Spinner';
import DocumentItemRow from './QuotationItemRow';
import { getTaxInfo } from '../hooks/useDocument';
import { useDocumentItems } from '../hooks/useDocumentItems';

interface QuotationEditorFormProps {
    quotation: QuotationState;
    setQuotation: React.Dispatch<React.SetStateAction<QuotationState | null>>;
    onSave: () => Promise<void>;
    isSaving: boolean;
    onCancel: () => void;
    saveError: string | null;
}

const TotalsDisplay: React.FC<{
    subTotal: number;
    discount: number;
    tax: number;
    taxLabel: string;
    grandTotal: number;
}> = ({ subTotal, discount, tax, taxLabel, grandTotal }) => {
    const format = (num: number) => num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    return (
        <div className="mt-6 flex justify-end">
            <div className="w-full max-w-sm space-y-3 bg-slate-50 p-4 rounded-lg border border-border">
                <div className="flex justify-between font-medium text-text-secondary">
                    <span>المجموع الفرعي</span>
                    <span>{format(subTotal)}</span>
                </div>
                {discount > 0 && (
                    <div className="flex justify-between font-medium text-text-secondary">
                        <span>الخصم</span>
                        <span className="text-red-600">-{format(discount)}</span>
                    </div>
                )}
                {tax > 0 && (
                    <div className="flex justify-between font-medium text-text-secondary">
                        <span>{taxLabel}</span>
                        <span>{format(tax)}</span>
                    </div>
                )}
                <div className="border-t border-dashed border-border pt-3 mt-3">
                    <div className="flex justify-between font-bold text-lg text-text-primary">
                        <span>الإجمالي النهائي</span>
                        <span>{format(grandTotal)}</span>
                    </div>
                </div>
            </div>
        </div>
    );
};

const QuotationEditorForm: React.FC<QuotationEditorFormProps> = ({ quotation, setQuotation, onSave, isSaving, onCancel, saveError }) => {
    const { products } = useProducts();

    const { handleItemChange, handleProductSelection, addItem, removeItem } = useDocumentItems(setQuotation, products);

    const { subTotal, discount, tax, taxInfo, grandTotal } = useMemo(() => {
        if (!quotation) return { subTotal: 0, discount: 0, tax: 0, taxInfo: { rate: 0, label: 'الضريبة' }, grandTotal: 0 };
        const subTotal = quotation.items.reduce((sum, item) => sum + (item.total || 0), 0);
        const discount = Number(quotation.discount) || 0;
        const taxableAmount = subTotal - discount;
        const taxInfo = getTaxInfo(quotation.currency);
        const tax = quotation.taxIncluded ? taxableAmount * taxInfo.rate : 0;
        const grandTotal = taxableAmount + tax;
        return { subTotal, discount, tax, taxInfo, grandTotal };
    }, [quotation]);


    useEffect(() => {
        if (!quotation) return;
        const finalTotal = parseFloat(grandTotal.toFixed(2));
        if (quotation.totalAmount !== finalTotal) {
            setQuotation(prev => prev ? { ...prev, totalAmount: finalTotal } : null);
        }
    }, [quotation, grandTotal, setQuotation]);

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setQuotation(prev => {
            if (!prev) return null;
            if (name === 'discount') {
                return { ...prev, [name]: parseFloat(value) || 0 };
            }
            return { ...prev, [name]: value };
        });
    };

    const inputClasses = "border border-border bg-white text-text-primary p-2 rounded w-full text-right focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors";
    const buttonClasses = "px-5 py-2 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2";

    return (
        <>
            <div className="bg-card p-6 rounded-lg shadow-sm max-w-7xl mx-auto border border-border">
                <h2 className="text-xl font-bold mb-4 border-b border-border pb-2 text-text-secondary">تفاصيل العميل</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <input type="text" name="clientName" placeholder="اسم العميل" value={quotation.clientName} onChange={handleInputChange} className={`${inputClasses} lg:col-span-1`} />
                    <input type="text" name="company" placeholder="الشركة" value={quotation.company} onChange={handleInputChange} className={`${inputClasses} lg:col-span-1`} />
                    <input type="text" name="project" placeholder="المشروع" value={quotation.project} onChange={handleInputChange} className={`${inputClasses} lg:col-span-2`} />
                    <input type="text" name="quotationType" placeholder="نوع العرض" value={quotation.quotationType} onChange={handleInputChange} className={inputClasses} />
                    <input type="date" name="date" value={quotation.date} onChange={handleInputChange} className={`${inputClasses}`} />
                    <select name="currency" value={quotation.currency} onChange={handleInputChange} className={inputClasses}>
                        <option value={Currency.SAR}>ريال سعودي (SAR)</option>
                        <option value={Currency.EGP}>جنيه مصري (EGP)</option>
                        <option value={Currency.USD}>دولار أمريكي (USD)</option>
                    </select>
                    <input type="number" name="discount" placeholder="الخصم" value={quotation.discount || ''} onChange={handleInputChange} className={inputClasses} />
                </div>

                <div className="mt-4 flex items-center gap-2">
                    <label className="text-sm font-medium text-text-secondary">إعدادات الضريبة:</label>
                    <div className="flex items-center gap-2">
                        <button
                            type="button"
                            onClick={() => setQuotation(prev => prev ? { ...prev, taxIncluded: true } : null)}
                            className={`px-4 py-2 rounded-md text-sm font-semibold transition-colors ${quotation.taxIncluded ? 'bg-green-600 text-white shadow' : 'bg-green-100 text-green-800 hover:bg-green-200'}`}
                        >
                            شامل الضريبة
                        </button>
                        <button
                            type="button"
                            onClick={() => setQuotation(prev => prev ? { ...prev, taxIncluded: false } : null)}
                            className={`px-4 py-2 rounded-md text-sm font-semibold transition-colors ${!quotation.taxIncluded ? 'bg-orange-500 text-white shadow' : 'bg-orange-100 text-orange-800 hover:bg-orange-200'}`}
                        >
                            غير شامل الضريبة
                        </button>
                    </div>
                </div>

                <h2 className="text-xl font-bold my-6 border-b border-border pb-2 text-text-secondary">البنود</h2>

                <div className="space-y-3">
                    {quotation.items.map((item, index) => (
                        <DocumentItemRow
                            // Use the item's unique ID as the key. This is crucial for React to correctly handle updates and deletions.
                            key={item.id}
                            item={item}
                            index={index}
                            onItemChange={handleItemChange}
                            onProductSelection={handleProductSelection}
                            onRemoveItem={() => removeItem(item.id!)}
                            products={products}
                            inputClasses={inputClasses}
                        />
                    ))}
                </div>

                <div className="flex items-center gap-4 mt-4">
                    <button onClick={addItem} className="bg-indigo-100 text-indigo-700 hover:bg-indigo-200 px-4 py-2 rounded-lg font-semibold">+ إضافة بند جديد</button>
                </div>

                <TotalsDisplay
                    subTotal={subTotal}
                    discount={discount}
                    tax={tax}
                    taxLabel={taxInfo.label}
                    grandTotal={grandTotal}
                />

                <div className="mt-8">
                    {saveError && (
                        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 text-right" role="alert">
                            <strong className="font-bold">خطأ!</strong>
                            <span className="block sm:inline ml-2">{saveError}</span>
                        </div>
                    )}
                    <div className="flex justify-end gap-4">
                        <button onClick={onCancel} className={`bg-gray-200 hover:bg-gray-300 text-gray-800 ${buttonClasses}`} disabled={isSaving}>إلغاء</button>
                        <button onClick={onSave} className={`bg-green-600 hover:bg-green-700 text-white focus:ring-green-600 ${buttonClasses} w-40`} disabled={isSaving}>
                            {isSaving && <Spinner />}
                            {isSaving ? 'جاري الحفظ...' : 'حفظ التغييرات'}
                        </button>
                    </div>
                </div>
            </div>
        </>
    )
}

export default QuotationEditorForm;
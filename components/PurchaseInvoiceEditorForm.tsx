

import React, { useState, useCallback, useMemo, useEffect } from 'react';
import { Product, ProductType, Unit, Currency, PurchaseInvoiceStatus, DocumentItemState, PermissionModule, PermissionAction } from '../types';
import { useProducts } from '../contexts/ProductContext';
import { useAuth } from '../hooks/useAuth';
import { usePermissions } from '../hooks/usePermissions';
import AddProductModal from './AddProductModal';
import { PurchaseInvoiceState } from '../pages/PurchaseInvoiceEditorPage';
import Spinner from './Spinner';
import DocumentItemRow from './QuotationItemRow';
import { useDocumentItems } from '../hooks/useDocumentItems';

interface PurchaseInvoiceEditorFormProps {
    document: PurchaseInvoiceState;
    setDocument: React.Dispatch<React.SetStateAction<PurchaseInvoiceState | null>>;
    onSave: () => Promise<void>;
    isSaving: boolean;
    onCancel: () => void;
    saveError: string | null;
}

const PurchaseInvoiceEditorForm: React.FC<PurchaseInvoiceEditorFormProps> = ({ document, setDocument, onSave, isSaving, onCancel, saveError }) => {
    const { products, addProduct } = useProducts();
    const { currentUser } = useAuth();
    const permissions = usePermissions();
    const [isModalOpen, setIsModalOpen] = useState(false);

    const canChangeStatus = permissions.can(PermissionModule.PURCHASE_INVOICES, PermissionAction.CHANGE_STATUS);

    const { handleItemChange, handleProductSelection, addItem, removeItem } = useDocumentItems(setDocument, products);

    const subTotal = useMemo(() => {
        return document?.items.reduce((sum, item) => sum + (item.total || 0), 0) ?? 0;
    }, [document?.items]);

    useEffect(() => {
        setDocument(prev => {
            if (!prev) return null;
            const newTotalAmount = parseFloat(subTotal.toFixed(2));
            if (prev.totalAmount === newTotalAmount) return prev; // Avoid re-render if total is the same
            return { ...prev, totalAmount: newTotalAmount };
        });
    }, [subTotal, setDocument]);

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setDocument(prev => prev ? { ...prev, [name]: value } : null);
    };

    const handleAddProduct = async (productData: Omit<Product, 'id' | 'averagePurchasePrice' | 'averageSellingPrice'>) => {
        if (!currentUser) {
            return { product: null, error: "User not authenticated to add a product." };
        }
        return addProduct(productData);
    };

    const inputClasses = "border border-border bg-white text-text-primary p-2 rounded w-full text-right focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors";
    const buttonClasses = "px-5 py-2 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2";

    return (
        <>
            <AddProductModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                onSave={handleAddProduct}
            />
            <div className="bg-card p-6 rounded-lg shadow-sm border border-border">
                <h2 className="text-xl font-bold mb-4 border-b border-border pb-2 text-text-secondary">تفاصيل الفاتورة</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <input type="text" name="supplierName" placeholder="اسم المورد" value={document.supplierName} onChange={handleInputChange} className={inputClasses} />
                    <input type="date" name="date" value={document.date} onChange={handleInputChange} className={`${inputClasses}`} />
                    <select name="status" value={document.status} onChange={handleInputChange} className={inputClasses} disabled={!canChangeStatus}>
                        {Object.values(PurchaseInvoiceStatus).map(s => <option key={s} value={s}>{s}</option>)}
                    </select>
                    <select name="currency" value={document.currency} onChange={handleInputChange} className={inputClasses}>
                        <option value={Currency.EGP}>جنيه مصري (EGP)</option>
                        <option value={Currency.SAR}>ريال سعودي (SAR)</option>
                        <option value={Currency.USD}>دولار أمريكي (USD)</option>
                    </select>
                </div>

                <h2 className="text-xl font-bold my-6 border-b border-border pb-2 text-text-secondary">البنود</h2>

                <div className="space-y-3">
                    {document.items.map((item, index) => (
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

                <div className="flex flex-col sm:flex-row items-center gap-4 mt-4">
                    <button onClick={addItem} className="bg-indigo-100 text-indigo-700 hover:bg-indigo-200 px-4 py-2 rounded-lg font-semibold">+ إضافة بند جديد</button>
                    <button onClick={() => setIsModalOpen(true)} className="bg-green-100 text-green-700 hover:bg-green-200 px-4 py-2 rounded-lg font-semibold">+ إضافة منتج للقائمة</button>
                </div>

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

export default PurchaseInvoiceEditorForm;


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
    invoice: PurchaseInvoiceState;
    setInvoice: React.Dispatch<React.SetStateAction<PurchaseInvoiceState | null>>;
    onSave: () => Promise<void>;
    isSaving: boolean;
    onCancel: () => void;
    saveError: string | null;
}

const PurchaseInvoiceEditorForm: React.FC<PurchaseInvoiceEditorFormProps> = ({ invoice, setInvoice, onSave, isSaving, onCancel, saveError }) => {
    const { products, addProduct } = useProducts();
    const { currentUser } = useAuth();
    const permissions = usePermissions();
    const [isModalOpen, setIsModalOpen] = useState(false);

    const canChangeStatus = permissions.can(PermissionModule.PURCHASE_INVOICES, PermissionAction.CHANGE_STATUS);
    
    const { handleItemChange, handleProductSelection, addItem, removeItem } = useDocumentItems(setInvoice, products);

    const subTotal = useMemo(() => {
        return invoice?.items.reduce((sum, item) => sum + (item.total || 0), 0) ?? 0;
    }, [invoice?.items]);

    useEffect(() => {
        if (invoice) {
            const newTotalAmount = parseFloat(subTotal.toFixed(2));
            if (invoice.totalAmount !== newTotalAmount) {
                setInvoice(prev => prev ? { ...prev, totalAmount: newTotalAmount } : null);
            }
        }
    }, [subTotal, invoice, setInvoice]);

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setInvoice(prev => prev ? { ...prev, [name]: value } : null);
    };

    const handleAddProduct = async (productData: Omit<Product, 'id' | 'averagePurchasePrice' | 'averageSellingPrice'>) => {
        if (!currentUser) {
            return { product: null, error: "User not authenticated to add a product." };
        }
        return addProduct(productData, currentUser.id);
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
            <div className="bg-card p-6 rounded-lg shadow-sm max-w-7xl mx-auto border border-border">
                 <h2 className="text-xl font-bold mb-4 border-b border-border pb-2 text-text-secondary">تفاصيل الفاتورة</h2>
                 <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <input type="text" name="supplierName" placeholder="اسم المورد" value={invoice.supplierName} onChange={handleInputChange} className={inputClasses} />
                    <input type="date" name="date" value={invoice.date} onChange={handleInputChange} className={`${inputClasses}`} />
                    <select name="status" value={invoice.status} onChange={handleInputChange} className={inputClasses} disabled={!canChangeStatus}>
                        {Object.values(PurchaseInvoiceStatus).map(s => <option key={s} value={s}>{s}</option>)}
                    </select>
                    <select name="currency" value={invoice.currency} onChange={handleInputChange} className={inputClasses}>
                        <option value={Currency.EGP}>جنيه مصري (EGP)</option>
                        <option value={Currency.SAR}>ريال سعودي (SAR)</option>
                        <option value={Currency.USD}>دولار أمريكي (USD)</option>
                    </select>
                 </div>
                 
                 <h2 className="text-xl font-bold my-6 border-b border-border pb-2 text-text-secondary">البنود</h2>
                 
                 <div className="hidden md:grid grid-cols-12 gap-x-2 mb-2 text-sm font-bold text-text-secondary text-center">
                     <div className="col-span-4 text-right">المنتج / الوصف</div>
                     <div className="col-span-3">الأبعاد</div>
                     <div className="col-span-1">الكمية</div>
                     <div className="col-span-1">الوحدة</div>
                     <div className="col-span-1">السعر</div>
                     <div className="col-span-1">الإجمالي</div>
                     <div className="col-span-1"></div>
                 </div>

                 {invoice.items.map((item, index) => (
                    <DocumentItemRow
                        key={index}
                        item={item}
                        index={index}
                        onItemChange={handleItemChange}
                        onProductSelection={handleProductSelection}
                        onRemoveItem={removeItem}
                        products={products}
                        inputClasses={inputClasses}
                    />
                 ))}

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
                        <button onClick={onSave} className={`bg-primary hover:bg-primary-hover text-white ${buttonClasses} w-40`} disabled={isSaving}>
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
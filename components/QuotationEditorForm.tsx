
import React, { useState, useCallback } from 'react';
import { Product, ProductType, Unit, Currency, DocumentItemState } from '../types';
import { useProducts } from '../contexts/ProductContext';
import { useAuth } from '../hooks/useAuth';
import AddProductModal from './AddProductModal';
import { QuotationState } from '../pages/QuotationEditorPage';
import Spinner from './Spinner';
import DocumentItemRow from './QuotationItemRow';

interface QuotationEditorFormProps {
    quotation: QuotationState;
    setQuotation: React.Dispatch<React.SetStateAction<QuotationState | null>>;
    onSave: () => Promise<void>;
    isSaving: boolean;
    onCancel: () => void;
    saveError: string | null;
}

const QuotationEditorForm: React.FC<QuotationEditorFormProps> = ({ quotation, setQuotation, onSave, isSaving, onCancel, saveError }) => {
    const { products, addProduct } = useProducts();
    const { currentUser } = useAuth();
    const [isModalOpen, setIsModalOpen] = useState(false);

    const updateQuotationItems = useCallback((newItems: DocumentItemState[]) => {
        const newTotalAmount = newItems.reduce((sum, item) => sum + item.total, 0);
        setQuotation(prev => prev ? { ...prev, items: newItems, totalAmount: parseFloat(newTotalAmount.toFixed(2)) } : null);
    }, [setQuotation]);

    const updateItemDescription = useCallback((item: DocumentItemState, product?: Product): string => {
        const p = product || products.find(prod => prod.id === item.productId);
        if (!p) return item.description;

        let desc = p.name;
        if (p.productType === ProductType.DIFFUSER && item.length && item.width) {
            desc += ` (${item.length} x ${item.width})`;
        } else if (p.productType === ProductType.CABLE_TRAY && item.width && item.height) {
            desc += ` (${item.width} x ${item.height})`;
        }
        return desc;
    }, [products]);

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setQuotation(prev => prev ? { ...prev, [name]: value } : null);
    };

    const handleItemChange = (index: number, e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        
        const newItems = [...quotation.items];
        const itemToUpdate = { ...newItems[index] };
        
        const numValue = parseFloat(value) || 0;

        switch (name) {
            case 'description': itemToUpdate.description = value; break;
            case 'quantity': itemToUpdate.quantity = numValue; break;
            case 'unitPrice': itemToUpdate.unitPrice = numValue; break;
            case 'length': itemToUpdate.length = numValue; break;
            case 'width': itemToUpdate.width = numValue; break;
            case 'height': itemToUpdate.height = numValue; break;
            case 'unit': itemToUpdate.unit = value as Unit; break;
        }

        if (name === 'length' || name === 'width' || name === 'height') {
            itemToUpdate.description = updateItemDescription(itemToUpdate);
        }
        
        itemToUpdate.total = parseFloat((itemToUpdate.quantity * itemToUpdate.unitPrice).toFixed(2));
        newItems[index] = itemToUpdate;
        updateQuotationItems(newItems);
    };

    const handleProductSelection = (itemIndex: number, selectedProductId: string) => {
        const newItems = [...quotation.items];
        let itemToUpdate = { ...newItems[itemIndex] };

        if (selectedProductId === 'custom' || !selectedProductId) {
            itemToUpdate = {
                ...itemToUpdate,
                productId: undefined, description: '', unitPrice: 0, unit: Unit.COUNT,
                productType: ProductType.SIMPLE, length: undefined, width: undefined, height: undefined,
            };
        } else {
            const product = products.find(p => p.id === parseInt(selectedProductId, 10));
            if (product) {
                itemToUpdate = {
                    ...itemToUpdate,
                    productId: product.id,
                    description: product.name,
                    unitPrice: product.sellingPrice,
                    unit: product.unit,
                    productType: product.productType,
                    length: undefined,
                    width: undefined,
                    height: undefined,
                };
                itemToUpdate.description = updateItemDescription(itemToUpdate, product);
            }
        }
        
        itemToUpdate.total = parseFloat((itemToUpdate.quantity * itemToUpdate.unitPrice).toFixed(2));
        newItems[itemIndex] = itemToUpdate;
        updateQuotationItems(newItems);
    };

    const addItem = () => {
        const newItem: DocumentItemState = {
            description: '', quantity: 1, unitPrice: 0, total: 0, unit: Unit.COUNT, productType: ProductType.SIMPLE
        };
        updateQuotationItems([...quotation.items, newItem]);
    };
    
    const removeItem = (index: number) => {
        const newItems = quotation.items.filter((_, i) => i !== index);
        updateQuotationItems(newItems);
    };

    const handleAddProduct = async (productData: Omit<Product, 'id' | 'averagePurchasePrice' | 'averageSellingPrice'> & { id?: number }) => {
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
                 <h2 className="text-xl font-bold mb-4 border-b border-border pb-2 text-text-secondary">تفاصيل العميل</h2>
                 <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <input type="text" name="clientName" placeholder="اسم العميل" value={quotation.clientName} onChange={handleInputChange} className={inputClasses} />
                    <input type="text" name="company" placeholder="الشركة" value={quotation.company} onChange={handleInputChange} className={inputClasses} />
                    <input type="text" name="project" placeholder="المشروع" value={quotation.project} onChange={handleInputChange} className={inputClasses} />
                    <input type="text" name="quotationType" placeholder="نوع العرض" value={quotation.quotationType} onChange={handleInputChange} className={inputClasses} />
                    <input type="date" name="date" value={quotation.date} onChange={handleInputChange} className={`${inputClasses}`} />
                    <select name="currency" value={quotation.currency} onChange={handleInputChange} className={inputClasses}>
                        <option value={Currency.SAR}>ريال سعودي (SAR)</option>
                        <option value={Currency.EGP}>جنيه مصري (EGP)</option>
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

                 {quotation.items.map((item, index) => (
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

export default QuotationEditorForm;

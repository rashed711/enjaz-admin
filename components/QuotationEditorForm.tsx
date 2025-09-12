import React, { useState, useCallback } from 'react';
import { Product, ProductType, Unit, Currency } from '../types';
import { useProducts } from '../contexts/ProductContext';
import AddProductModal from './AddProductModal';
import { QuotationState, QuotationItemState } from '../pages/QuotationEditorPage';
import Spinner from './Spinner';

interface QuotationEditorFormProps {
    quotation: QuotationState;
    setQuotation: React.Dispatch<React.SetStateAction<QuotationState | null>>;
    onSave: () => Promise<void>;
    isSaving: boolean;
    onCancel: () => void;
}

const QuotationEditorForm: React.FC<QuotationEditorFormProps> = ({ quotation, setQuotation, onSave, isSaving, onCancel }) => {
    const { products, addProduct } = useProducts();
    const [isModalOpen, setIsModalOpen] = useState(false);

    const updateItemDescription = useCallback((item: QuotationItemState, product?: Product): string => {
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
        const newTotalAmount = newItems.reduce((sum, item) => sum + item.total, 0);

        setQuotation({ ...quotation, items: newItems, totalAmount: parseFloat(newTotalAmount.toFixed(2)) });
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
                    productId: product.id, description: product.name, unitPrice: product.unitPrice,
                    unit: product.unit, productType: product.productType,
                    length: undefined, width: undefined, height: undefined,
                };
                itemToUpdate.description = updateItemDescription(itemToUpdate, product);
            }
        }
        
        itemToUpdate.total = parseFloat((itemToUpdate.quantity * itemToUpdate.unitPrice).toFixed(2));
        newItems[itemIndex] = itemToUpdate;
        const newTotalAmount = newItems.reduce((sum, item) => sum + item.total, 0);
        setQuotation({ ...quotation, items: newItems, totalAmount: parseFloat(newTotalAmount.toFixed(2)) });
    };

    const addItem = () => {
        const newItem: QuotationItemState = {
            description: '', quantity: 1, unitPrice: 0, total: 0, unit: Unit.COUNT, productType: ProductType.SIMPLE
        };
        setQuotation({ ...quotation, items: [...quotation.items, newItem] });
    };
    
    const removeItem = (index: number) => {
        const newItems = quotation.items.filter((_, i) => i !== index);
        const newTotalAmount = newItems.reduce((sum, item) => sum + item.total, 0);
        setQuotation({ ...quotation, items: newItems, totalAmount: parseFloat(newTotalAmount.toFixed(2)) });
    };

    const inputClasses = "border border-border bg-gray-50 text-dark-text p-2 rounded w-full text-right focus:outline-none focus:ring-2 focus:ring-[#10B981] focus:border-[#10B981] transition-colors";
    const buttonClasses = "px-5 py-2 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2";

    return (
        <>
            <AddProductModal 
                isOpen={isModalOpen} 
                onClose={() => setIsModalOpen(false)} 
                onSave={addProduct} 
            />
            <div className="bg-white p-6 rounded-lg shadow-lg max-w-7xl mx-auto">
                 <h2 className="text-xl font-bold mb-4 border-b border-border pb-2 text-muted-text">تفاصيل العميل</h2>
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
                 
                 <h2 className="text-xl font-bold my-6 border-b border-border pb-2 text-muted-text">البنود</h2>
                 
                 <div className="hidden md:grid grid-cols-12 gap-x-2 mb-2 text-sm font-bold text-muted-text text-center">
                     <div className="col-span-4 text-right">المنتج / الوصف</div>
                     <div className="col-span-3">الأبعاد</div>
                     <div className="col-span-1">الكمية</div>
                     <div className="col-span-1">الوحدة</div>
                     <div className="col-span-1">السعر</div>
                     <div className="col-span-1">الإجمالي</div>
                     <div className="col-span-1"></div>
                 </div>

                 {quotation.items.map((item, index) => (
                    <div key={item.id || index} className="grid grid-cols-1 md:grid-cols-12 gap-y-4 gap-x-2 mb-4 p-4 bg-gray-50 rounded-lg md:p-2 md:bg-transparent md:rounded-none md:items-start">
                        <div className="md:col-span-4 space-y-2">
                            <label className="text-xs text-muted-text md:hidden">المنتج / الوصف</label>
                            <select value={item.productId || 'custom'} onChange={(e) => handleProductSelection(index, e.target.value)} className={inputClasses}>
                                <option value="custom">-- منتج مخصص --</option>
                                {products.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
                            </select>
                            <textarea name="description" placeholder="الوصف" value={item.description} onChange={(e) => handleItemChange(index, e)} className={`${inputClasses} h-20`} />
                        </div>
                        <div className="md:col-span-3">
                            {item.productType !== ProductType.SIMPLE && <label className="text-xs text-muted-text md:hidden">الأبعاد</label>}
                            <div className="flex gap-2">
                                {item.productType === ProductType.DIFFUSER && (
                                    <>
                                        <input type="number" name="length" placeholder="الطول" value={item.length || ''} onChange={(e) => handleItemChange(index, e)} className={inputClasses} />
                                        <input type="number" name="width" placeholder="العرض" value={item.width || ''} onChange={(e) => handleItemChange(index, e)} className={inputClasses} />
                                    </>
                                )}
                                {item.productType === ProductType.CABLE_TRAY && (
                                    <>
                                        <input type="number" name="width" placeholder="العرض" value={item.width || ''} onChange={(e) => handleItemChange(index, e)} className={inputClasses} />
                                        <input type="number" name="height" placeholder="الارتفاع" value={item.height || ''} onChange={(e) => handleItemChange(index, e)} className={inputClasses} />
                                    </>
                                )}
                            </div>
                        </div>
                        <div className="md:col-span-1">
                            <label className="text-xs text-muted-text md:hidden">الكمية</label>
                            <input type="number" name="quantity" placeholder="الكمية" value={item.quantity} onChange={(e) => handleItemChange(index, e)} className={inputClasses} />
                        </div>
                        <div className="md:col-span-1">
                            <label className="text-xs text-muted-text md:hidden">الوحدة</label>
                            <select name="unit" value={item.unit} onChange={(e) => handleItemChange(index, e)} className={inputClasses} disabled={!!item.productId}>
                                {Object.values(Unit).map(u => <option key={u} value={u}>{u}</option>)}
                            </select>
                        </div>
                        <div className="md:col-span-1">
                            <label className="text-xs text-muted-text md:hidden">سعر الوحدة</label>
                            <input type="number" step="any" name="unitPrice" placeholder="السعر" value={item.unitPrice} onChange={(e) => handleItemChange(index, e)} className={inputClasses} />
                        </div>
                        <div className="md:col-span-1">
                            <label className="text-xs text-muted-text md:hidden">الإجمالي</label>
                            <input type="text" readOnly value={item.total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} className={`${inputClasses} bg-gray-200 cursor-not-allowed text-dark-text`} />
                        </div>
                        <div className="md:col-span-1 flex items-center justify-end">
                            <button onClick={() => removeItem(index)} className="w-full md:w-auto bg-red-500/10 text-red-500 hover:bg-red-500/20 hover:text-red-600 font-bold p-2 rounded transition-colors text-xl">×</button>
                        </div>
                    </div>
                 ))}

                <div className="flex flex-col sm:flex-row items-center gap-4 mt-4">
                    <button onClick={addItem} className="text-[#10B981] hover:text-[#059669] font-semibold transition-colors">+ إضافة بند جديد</button>
                    <button onClick={() => setIsModalOpen(true)} className="text-green-500 hover:text-green-400 font-semibold transition-colors">+ إضافة منتج للقائمة</button>
                </div>

                 <div className="mt-8 flex justify-end gap-4">
                    <button onClick={onCancel} className={`bg-gray-500 hover:bg-gray-600 text-white ${buttonClasses}`} disabled={isSaving}>إلغاء</button>
                    <button onClick={onSave} className={`bg-[#10B981] hover:bg-[#059669] text-white ${buttonClasses} w-40`} disabled={isSaving}>
                        {isSaving && <Spinner />}
                        {isSaving ? 'جاري الحفظ...' : 'حفظ التغييرات'}
                    </button>
                 </div>
            </div>
        </>
    )
}

export default QuotationEditorForm;

import React, { useState, useEffect } from 'react';
import { Product, ProductType, Unit } from '../types';
import Spinner from './Spinner';

interface AddProductModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSave: (product: Omit<Product, 'id'> & { id?: number }) => Promise<Product | null>;
    productToEdit?: Product | null;
}

const AddProductModal: React.FC<AddProductModalProps> = ({ isOpen, onClose, onSave, productToEdit }) => {
    const [productData, setProductData] = useState({ 
        name: '', 
        description: '', 
        unitPrice: 0,
        productType: ProductType.SIMPLE,
        unit: Unit.COUNT,
    });
    const [isSaving, setIsSaving] = useState(false);
    const [error, setError] = useState<string | null>(null);
    
    useEffect(() => {
        if (isOpen) {
            if (productToEdit) {
                setProductData({
                    name: productToEdit.name,
                    description: productToEdit.description || '',
                    unitPrice: productToEdit.unitPrice,
                    productType: productToEdit.productType,
                    unit: productToEdit.unit,
                });
            } else {
                setProductData({ 
                    name: '', 
                    description: '', 
                    unitPrice: 0,
                    productType: ProductType.SIMPLE,
                    unit: Unit.COUNT,
                });
            }
            setIsSaving(false);
            setError(null);
        }
    }, [isOpen, productToEdit]);

    const handleSaveClick = async () => {
        setError(null);
        if (!productData.name.trim()) {
            setError("اسم المنتج مطلوب.");
            return;
        }
        if (productData.unitPrice <= 0) {
            setError("سعر الوحدة يجب أن يكون أكبر من صفر.");
            return;
        }

        setIsSaving(true);
        try {
            const productToSave = productToEdit ? { ...productData, id: productToEdit.id } : productData;
            const saved = await onSave(productToSave);
            if (saved) {
                onClose(); 
            } else {
                setError("حدث خطأ أثناء حفظ المنتج. يرجى المحاولة مرة أخرى.");
            }
        } catch (e) {
            console.error("Error saving product:", e);
            setError("حدث خطأ فادح أثناء حفظ المنتج.");
        } finally {
            setIsSaving(false);
        }
    };
    
    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setProductData(prev => ({ ...prev, [name]: value }));
    };

    if (!isOpen) return null;

    const inputClasses = "border border-border bg-background text-text-primary p-3 rounded w-full text-right focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors";
    
    return (
        <div className="fixed inset-0 bg-black bg-opacity-70 flex justify-center items-center z-50 p-4">
            <div className="bg-card p-8 rounded-lg shadow-xl w-full max-w-md text-text-primary border border-border">
                <h2 className="text-2xl font-bold mb-6 text-text-primary text-center">
                    {productToEdit ? 'تعديل المنتج' : 'إضافة منتج جديد'}
                </h2>
                <div className="space-y-4">
                    <input type="text" name="name" placeholder="اسم المنتج" value={productData.name} onChange={handleChange} className={inputClasses}/>
                    <textarea name="description" placeholder="وصف المنتج (اختياري)" value={productData.description} onChange={handleChange} className={`${inputClasses} h-24`}></textarea>
                    
                    <div className="grid grid-cols-2 gap-4">
                        <select name="productType" value={productData.productType} onChange={handleChange} className={inputClasses}>
                            {Object.values(ProductType).map(type => (
                                <option key={type} value={type}>{type}</option>
                            ))}
                        </select>
                         <select name="unit" value={productData.unit} onChange={handleChange} className={inputClasses}>
                            {Object.values(Unit).map(unit => (
                                <option key={unit} value={unit}>{unit}</option>
                            ))}
                        </select>
                    </div>

                    <input type="number" name="unitPrice" placeholder="سعر الوحدة" value={productData.unitPrice || ''} onChange={e => setProductData({...productData, unitPrice: parseFloat(e.target.value) || 0})} className={inputClasses}/>
                </div>
                {error && <p className="text-red-500 text-xs mt-4 text-center">{error}</p>}
                <div className="mt-8 flex justify-end gap-4">
                    <button onClick={onClose} className="bg-gray-200 text-gray-800 px-6 py-2 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card focus:ring-gray-500 transition-colors font-semibold" disabled={isSaving}>إلغاء</button>
                    <button onClick={handleSaveClick} className="bg-primary text-white font-semibold px-6 py-2 rounded-lg hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card focus:ring-primary transition-colors flex items-center justify-center gap-2 w-40" disabled={isSaving}>
                        {isSaving && <Spinner />}
                        {isSaving ? 'جاري الحفظ...' : (productToEdit ? 'حفظ التعديلات' : 'حفظ المنتج')}
                    </button>
                </div>
            </div>
        </div>
    );
};

export default AddProductModal;
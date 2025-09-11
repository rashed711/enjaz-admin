
import React, { useState, useEffect } from 'react';
import { Product, ProductType, Unit } from '../types';

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
        }
    }, [isOpen, productToEdit]);

    const handleSaveClick = async () => {
        if (!productData.name || productData.unitPrice <= 0) {
            alert("يرجى ملء اسم المنتج والتأكد من أن السعر أكبر من صفر.");
            return;
        }
        setIsSaving(true);
        try {
            const productToSave = productToEdit ? { ...productData, id: productToEdit.id } : productData;
            const saved = await onSave(productToSave);
            if (saved) {
                onClose(); 
            } else {
                alert("حدث خطأ أثناء حفظ المنتج.");
            }
        } catch (error) {
            console.error("Error saving product:", error);
            alert("حدث خطأ فادح أثناء حفظ المنتج.");
        } finally {
            setIsSaving(false);
        }
    };
    
    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setProductData(prev => ({ ...prev, [name]: value }));
    };

    if (!isOpen) return null;

    const inputClasses = "border border-border bg-gray-50 text-dark-text p-3 rounded w-full text-right focus:outline-none focus:ring-2 focus:ring-[#10B981] focus:border-[#10B981] transition-colors";
    
    return (
        <div className="fixed inset-0 bg-black bg-opacity-60 flex justify-center items-center z-50 p-4">
            <div className="bg-white p-8 rounded-lg shadow-xl w-full max-w-md text-dark-text">
                <h2 className="text-2xl font-bold mb-6 text-dark-text text-center">
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
                <div className="mt-8 flex justify-end gap-4">
                    <button onClick={onClose} className="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-gray-400 transition-colors font-semibold" disabled={isSaving}>إلغاء</button>
                    <button onClick={handleSaveClick} className="bg-[#10B981] text-white font-semibold px-6 py-2 rounded-lg hover:bg-[#059669] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-[#10B981] transition-colors" disabled={isSaving}>
                        {isSaving ? 'جاري الحفظ...' : (productToEdit ? 'حفظ التعديلات' : 'حفظ المنتج')}
                    </button>
                </div>
            </div>
        </div>
    );
};

export default AddProductModal;

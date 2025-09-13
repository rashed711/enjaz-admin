

import React, { useState, useEffect } from 'react';
import { Product, ProductType, Unit } from '../types';
import Spinner from './Spinner';
import Modal from './Modal';

interface AddProductModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSave: (product: Omit<Product, 'id' | 'averagePurchasePrice' | 'averageSellingPrice'>) => Promise<{ product: Product | null; error: string | null }>;
}

const AddProductModal: React.FC<AddProductModalProps> = ({ isOpen, onClose, onSave }) => {
    const [productData, setProductData] = useState({ 
        name: '', 
        sellingPrice: 0,
        productType: ProductType.SIMPLE,
        unit: Unit.COUNT,
    });
    const [isSaving, setIsSaving] = useState(false);
    const [error, setError] = useState<string | null>(null);
    
    useEffect(() => {
        if (isOpen) {
            setProductData({ 
                name: '', 
                sellingPrice: 0,
                productType: ProductType.SIMPLE,
                unit: Unit.COUNT,
            });
            setIsSaving(false);
            setError(null);
        }
    }, [isOpen]);

    const handleSaveClick = async () => {
        setError(null);
        if (!productData.name.trim()) {
            setError("اسم المنتج مطلوب.");
            return;
        }
        if (productData.sellingPrice <= 0) {
            setError("سعر البيع يجب أن يكون أكبر من صفر.");
            return;
        }

        setIsSaving(true);
        try {
            const result = await onSave(productData);
            if (result.product) {
                onClose(); 
            } else {
                const friendlyError = result.error?.includes('duplicate key') 
                    ? 'منتج بهذا الاسم موجود بالفعل.' 
                    : result.error || 'حدث خطأ غير متوقع.';
                setError(`فشل حفظ المنتج: ${friendlyError}`);
            }
        } catch (e: any) {
            console.error("Error saving product:", e.message);
            setError(`حدث خطأ فادح أثناء حفظ المنتج: ${e.message}`);
        } finally {
            setIsSaving(false);
        }
    };
    
    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setProductData(prev => ({ ...prev, [name]: value }));
    };

    const inputClasses = "border border-border bg-white text-text-primary py-2 px-3 rounded-lg w-full text-right focus:outline-none focus:ring-2 focus:ring-primary transition-colors";
    
    const footer = (
        <>
            <button onClick={onClose} className="bg-white border border-border text-text-secondary px-6 py-2 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card focus:ring-gray-400 font-semibold" disabled={isSaving}>إلغاء</button>
            <button onClick={handleSaveClick} className="bg-primary text-white font-semibold px-6 py-2 rounded-lg hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card focus:ring-primary flex items-center justify-center gap-2 min-w-[150px] shadow-md hover:shadow-lg" disabled={isSaving}>
                {isSaving && <Spinner />}
                {isSaving ? 'جاري الحفظ...' : 'حفظ المنتج'}
            </button>
        </>
    );

    return (
        <Modal 
            isOpen={isOpen}
            onClose={onClose}
            title='إضافة منتج جديد'
            footer={footer}
        >
            <div className="space-y-5">
                <div>
                    <label htmlFor="name" className="block text-sm font-medium mb-2 text-right text-text-secondary">اسم المنتج</label>
                    <input type="text" id="name" name="name" value={productData.name} onChange={handleChange} className={inputClasses}/>
                </div>
                
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label htmlFor="productType" className="block text-sm font-medium mb-2 text-right text-text-secondary">التصنيف</label>
                        <select id="productType" name="productType" value={productData.productType} onChange={handleChange} className={inputClasses}>
                            {Object.values(ProductType).map(type => (
                                <option key={type} value={type}>{type}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label htmlFor="unit" className="block text-sm font-medium mb-2 text-right text-text-secondary">الوحدة</label>
                        <select id="unit" name="unit" value={productData.unit} onChange={handleChange} className={inputClasses}>
                            {Object.values(Unit).map(unit => (
                                <option key={unit} value={unit}>{unit}</option>
                            ))}
                        </select>
                    </div>
                </div>

                <div>
                    <label htmlFor="sellingPrice" className="block text-sm font-medium mb-2 text-right text-text-secondary">سعر البيع</label>
                    <input type="number" id="sellingPrice" name="sellingPrice" value={productData.sellingPrice || ''} onChange={e => setProductData({...productData, sellingPrice: parseFloat(e.target.value) || 0})} className={inputClasses}/>
                </div>
            </div>
            {error && <p className="text-red-500 text-sm mt-4 text-center" role="alert">{error}</p>}
        </Modal>
    );
};

export default AddProductModal;

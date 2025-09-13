import React from 'react';
import { DocumentItemState, Product, Unit } from '../types';
import TrashIcon from './icons/TrashIcon';

interface DocumentItemRowProps {
    item: DocumentItemState;
    index: number;
    onItemChange: (index: number, field: keyof DocumentItemState, value: any) => void;
    onProductSelection: (index: number, productId: number) => void;
    onRemoveItem: () => void;
    products: Product[];
    inputClasses: string;
}

const DocumentItemRow: React.FC<DocumentItemRowProps> = ({
    item,
    index,
    onItemChange,
    onProductSelection,
    onRemoveItem,
    products,
    inputClasses,
}) => {
    const handleSelectProduct = (e: React.ChangeEvent<HTMLSelectElement>) => {
        const productId = parseInt(e.target.value, 10);
        if (!isNaN(productId)) {
            onProductSelection(index, productId);
        }
    };

    const handleFieldChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
        const { name, value } = e.target;
        const isNumeric = ['quantity', 'unitPrice', 'length', 'width', 'height'].includes(name);
        onItemChange(index, name as keyof DocumentItemState, isNumeric ? parseFloat(value) || 0 : value);
    };

    return (
        <div className="bg-slate-50/50 border border-border rounded-lg p-3">
            {/* Mobile Header */}
            <div className="md:hidden flex justify-between items-center mb-3">
                <span className="font-bold text-text-primary truncate w-4/5">{item.description || 'بند جديد'}</span>
                <button
                    type="button" 
                    onClick={onRemoveItem}
                    className="p-2 text-red-500 hover:bg-red-100 rounded-full"
                    title="حذف البند"
                >
                    <TrashIcon className="w-5 h-5" />
                </button>
            </div>

            <div className="grid grid-cols-2 md:grid-cols-12 gap-x-3 gap-y-4">
                {/* Product & Description */}
                <div className="col-span-2 md:col-span-4 space-y-2">
                    <div>
                        <label className="md:hidden text-xs font-medium text-text-secondary">اختر منتج (اختياري)</label>
                        <select onChange={handleSelectProduct} value={item.productId || ''} className={`${inputClasses} text-sm`}>
                            <option value="">-- اختر منتج --</option>
                            {products.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
                        </select>
                    </div>
                    <div>
                        <label className="md:hidden text-xs font-medium text-text-secondary">الوصف</label>
                        <textarea
                            name="description"
                            value={item.description}
                            onChange={handleFieldChange}
                            placeholder="وصف البند"
                            rows={2}
                            className={`${inputClasses} text-sm leading-snug`}
                        />
                    </div>
                </div>

                {/* Dimensions */}
                <div className="col-span-2 md:col-span-3">
                    <label className="md:hidden text-xs font-medium text-text-secondary">الأبعاد (طول, عرض, ارتفاع)</label>
                    <div className="grid grid-cols-3 gap-2">
                        <input type="number" name="length" value={item.length || ''} onChange={handleFieldChange} placeholder="طول" className={`${inputClasses} text-center text-sm`} />
                        <input type="number" name="width" value={item.width || ''} onChange={handleFieldChange} placeholder="عرض" className={`${inputClasses} text-center text-sm`} />
                        <input type="number" name="height" value={item.height || ''} onChange={handleFieldChange} placeholder="ارتفاع" className={`${inputClasses} text-center text-sm`} />
                    </div>
                </div>

                {/* Quantity, Unit, Price */}
                <div className="col-span-1"><label className="md:hidden text-xs font-medium text-text-secondary">الكمية</label><input type="number" name="quantity" value={item.quantity || ''} onChange={handleFieldChange} placeholder="الكمية" className={`${inputClasses} text-center text-sm`} /></div>
                <div className="col-span-1"><label className="md:hidden text-xs font-medium text-text-secondary">الوحدة</label><select name="unit" value={item.unit} onChange={handleFieldChange} className={`${inputClasses} text-center text-sm`}>{Object.values(Unit).map(u => <option key={u} value={u}>{u}</option>)}</select></div>
                <div className="col-span-1"><label className="md:hidden text-xs font-medium text-text-secondary">السعر</label><input type="number" name="unitPrice" value={item.unitPrice || ''} onChange={handleFieldChange} placeholder="السعر" className={`${inputClasses} text-center text-sm`} /></div>

                {/* Total */}
                <div className="col-span-1 flex flex-col items-start md:items-center justify-center">
                    <label className="md:hidden text-xs font-medium text-text-secondary">الإجمالي</label>
                    <span className="font-semibold text-text-primary text-sm pt-1">{item.total?.toLocaleString() || '0'}</span>
                </div>

                {/* Remove Button (Desktop) */}
                <div className="hidden md:flex col-span-1 items-center justify-center">
                    <button
                        type="button"
                        onClick={onRemoveItem}
                        className="p-2 text-gray-400 hover:text-red-500 hover:bg-red-100 rounded-full"
                        title="حذف البند"
                    >
                        <TrashIcon className="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>
    );
};

export default DocumentItemRow;
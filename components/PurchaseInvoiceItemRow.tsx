import React from 'react';
import { PurchaseInvoiceItemState } from '../pages/PurchaseInvoiceEditorPage';
import { Product, ProductType, Unit } from '../types';

interface PurchaseInvoiceItemRowProps {
    item: PurchaseInvoiceItemState;
    index: number;
    onItemChange: (index: number, e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => void;
    onProductSelection: (index: number, productId: string) => void;
    onRemoveItem: (index: number) => void;
    products: Product[];
    inputClasses: string;
}

const PurchaseInvoiceItemRow: React.FC<PurchaseInvoiceItemRowProps> = ({
    item,
    index,
    onItemChange,
    onProductSelection,
    onRemoveItem,
    products,
    inputClasses
}) => {
    // Determine the product type to show/hide dimension fields
    const productType = item.productId ? products.find(p => p.id === item.productId)?.productType : ProductType.SIMPLE;

    return (
        <div className="grid grid-cols-1 md:grid-cols-12 gap-y-4 gap-x-2 mb-4 p-4 bg-slate-50 rounded-lg md:p-2 md:bg-transparent md:rounded-none md:items-start border md:border-0 border-border">
            {/* Product/Description */}
            <div className="md:col-span-4 space-y-2">
                <label className="text-xs text-text-secondary md:hidden">المنتج / الوصف</label>
                <select value={item.productId || 'custom'} onChange={(e) => onProductSelection(index, e.target.value)} className={inputClasses}>
                    <option value="custom">-- منتج مخصص --</option>
                    {products.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
                </select>
                <textarea name="description" placeholder="الوصف" value={item.description} onChange={(e) => onItemChange(index, e)} className={`${inputClasses} h-20`} />
            </div>

            {/* Dimensions */}
            <div className="md:col-span-3">
                {productType !== ProductType.SIMPLE && <label className="text-xs text-text-secondary md:hidden">الأبعاد</label>}
                <div className="flex gap-2">
                    {productType === ProductType.DIFFUSER && (
                        <>
                            <input type="number" name="length" placeholder="الطول" value={item.length || ''} onChange={(e) => onItemChange(index, e)} className={inputClasses} />
                            <input type="number" name="width" placeholder="العرض" value={item.width || ''} onChange={(e) => onItemChange(index, e)} className={inputClasses} />
                        </>
                    )}
                    {productType === ProductType.CABLE_TRAY && (
                        <>
                            <input type="number" name="width" placeholder="العرض" value={item.width || ''} onChange={(e) => onItemChange(index, e)} className={inputClasses} />
                            <input type="number" name="height" placeholder="الارتفاع" value={item.height || ''} onChange={(e) => onItemChange(index, e)} className={inputClasses} />
                        </>
                    )}
                </div>
            </div>

            {/* Quantity */}
            <div className="md:col-span-1">
                <label className="text-xs text-text-secondary md:hidden">الكمية</label>
                <input type="number" name="quantity" placeholder="الكمية" value={item.quantity} onChange={(e) => onItemChange(index, e)} className={inputClasses} />
            </div>

            {/* Unit */}
            <div className="md:col-span-1">
                <label className="text-xs text-text-secondary md:hidden">الوحدة</label>
                <select name="unit" value={item.unit} onChange={(e) => onItemChange(index, e)} className={inputClasses} disabled={!!item.productId}>
                    {Object.values(Unit).map(u => <option key={u} value={u}>{u}</option>)}
                </select>
            </div>

            {/* Unit Price */}
            <div className="md:col-span-1">
                <label className="text-xs text-text-secondary md:hidden">سعر الوحدة</label>
                <input type="number" step="any" name="unitPrice" placeholder="السعر" value={item.unitPrice} onChange={(e) => onItemChange(index, e)} className={inputClasses} />
            </div>

            {/* Total */}
            <div className="md:col-span-1">
                <label className="text-xs text-text-secondary md:hidden">الإجمالي</label>
                <input type="text" readOnly value={item.total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} className={`${inputClasses} bg-slate-100 cursor-not-allowed text-text-primary`} />
            </div>


            {/* Remove Button */}
            <div className="md:col-span-1 flex items-center justify-end">
                <button onClick={() => onRemoveItem(index)} title="حذف البند" className="w-full md:w-auto bg-red-500/10 text-red-500 hover:bg-red-500/20 hover:text-red-600 font-bold p-2 rounded transition-colors text-xl leading-none flex items-center justify-center h-10 w-10">×</button>
            </div>
        </div>
    );
};

export default PurchaseInvoiceItemRow;
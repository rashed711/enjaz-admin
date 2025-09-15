

import React, { useState } from 'react';
import { useProducts } from '../contexts/ProductContext';
import { useAuth } from '../hooks/useAuth';
import { usePermissions } from '../hooks/usePermissions';
import AddProductModal from '../components/AddProductModal';
import DeleteConfirmationModal from '../components/DeleteConfirmationModal';
import EmptyState from '../components/EmptyState';
import { Product, PermissionModule, PermissionAction, ProductType, Unit } from '../types';
import Spinner from '../components/Spinner';
import PencilIcon from '../components/icons/PencilIcon';
import TrashIcon from '../components/icons/TrashIcon';
import SearchIcon from '../components/icons/SearchIcon';
import CubeIcon from '../components/icons/CubeIcon';
import XIcon from '../components/icons/XIcon';
import CheckIcon from '../components/icons/CheckIcon';

const ProductsListPage: React.FC = () => {
    const { products, loading, addProduct, updateProduct, deleteProduct } = useProducts();
    const { currentUser, loading: isAuthLoading } = useAuth();
    const permissions = usePermissions();
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [productToDelete, setProductToDelete] = useState<Product | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [deleteError, setDeleteError] = useState<string | null>(null);

    // State for inline editing
    const [editingProductId, setEditingProductId] = useState<number | null>(null);
    const [editedProductData, setEditedProductData] = useState<Omit<Product, 'id' | 'averagePurchasePrice' | 'averageSellingPrice'>>({ name: '', description: '', sellingPrice: 0, productType: ProductType.SIMPLE, unit: Unit.COUNT });
    const [isSaving, setIsSaving] = useState(false);

    const canManage = permissions.can(PermissionModule.PRODUCTS, PermissionAction.MANAGE);

    // The useProducts hook has its own loading state, but we must wait for authentication to resolve first.
    // This prevents rendering the page with incorrect permissions or data.
    if (isAuthLoading) {
        return <div className="flex justify-center items-center p-10"><Spinner /></div>;
    }

    const handleOpenModalForAdd = () => {
        setIsModalOpen(true);
    };

    const handleSaveProduct = async (productData: Omit<Product, 'id' | 'averagePurchasePrice' | 'averageSellingPrice'>) => {
        if (!currentUser) {
            return { product: null, error: "User not authenticated" };
        }
        return addProduct(productData);
    };

    const handleConfirmDelete = async () => {
        if (!productToDelete) return;

        setIsDeleting(true);
        setDeleteError(null);

        const success = await deleteProduct(productToDelete.id);

        if (success) {
            setProductToDelete(null);
        } else {
            setDeleteError("فشل حذف المنتج. قد يكون مرتبطًا بعروض أسعار حالية.");
        }
        setIsDeleting(false);
    };

    // Handlers for inline editing
    const handleStartEdit = (product: Product) => {
        setEditingProductId(product.id);
        const { id, averagePurchasePrice, averageSellingPrice, ...editableData } = product;
        setEditedProductData(editableData);
    };

    const handleCancelEdit = () => {
        setEditingProductId(null);
    };

    const handleEditInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
        const { name, value } = e.target;
        setEditedProductData(prev => ({
            ...prev,
            [name]: name === 'sellingPrice' ? parseFloat(value) || 0 : value,
        }));
    };

    const handleSaveEdit = async () => {
        if (!editedProductData || editingProductId === null) return;

        setIsSaving(true);
        const productToUpdate: Product = {
            id: editingProductId,
            ...editedProductData,
        };
        const { error } = await updateProduct(productToUpdate);
        setIsSaving(false);

        if (!error) {
            handleCancelEdit();
        } else {
            console.error("Failed to update product:", error);
            alert(`فشل تحديث المنتج: ${error}`);
        }
    };


    const filteredProducts = products.filter(product =>
        product.name.toLowerCase().includes(searchQuery.toLowerCase())
    );

    const formatPrice = (price?: number) => {
        if (price === undefined || price === null) return '-';
        return price.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    return (
        <>
            <AddProductModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                onSave={handleSaveProduct}
            />
            <DeleteConfirmationModal
                isOpen={!!productToDelete}
                onClose={() => {
                    setProductToDelete(null);
                    setDeleteError(null);
                }}
                onConfirm={handleConfirmDelete}
                title="تأكيد الحذف"
                message={
                    <>
                        هل أنت متأكد أنك تريد حذف المنتج <span className="font-bold text-text-primary">{productToDelete?.name}</span>؟ لا يمكن التراجع عن هذا الإجراء.
                    </>
                }
                isProcessing={isDeleting}
                error={deleteError}
            />
            <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
                <h2 className="text-2xl font-bold text-text-primary self-start sm:self-center">قائمة المنتجات</h2>
                <div className="flex flex-col sm:flex-row w-full sm:w-auto gap-4">
                    <div className="relative w-full sm:w-64">
                        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <SearchIcon className="h-5 w-5 text-gray-400" />
                        </div>
                        <input
                            type="text"
                            placeholder="ابحث بالاسم..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="w-full border border-border bg-card text-text-primary p-2 pl-3 pr-10 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary transition-colors"
                            aria-label="Search products"
                        />
                    </div>
                    {canManage && (
                        <button
                            onClick={handleOpenModalForAdd}
                            className="w-full sm:w-auto bg-green-600 text-white font-semibold px-5 py-2 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background focus:ring-green-600 shadow-md hover:shadow-lg"
                        >
                            + إضافة منتج جديد
                        </button>
                    )}
                </div>
            </div>

            {loading ? (
                <div className="flex justify-center items-center p-10">
                    <Spinner />
                </div>
            ) : filteredProducts.length === 0 ? (
                <EmptyState
                    Icon={CubeIcon}
                    title={searchQuery ? 'لا توجد نتائج' : 'لا توجد منتجات بعد'}
                    message={
                        searchQuery
                            ? 'لم نجد أي منتجات تطابق بحثك. حاول استخدام كلمات أخرى.'
                            : 'ابدأ بإضافة أول منتج لك في النظام لإدارة المخزون وعروض الأسعار بسهولة.'
                    }
                    action={!searchQuery && canManage ? {
                        label: '+ إضافة منتج جديد',
                        onClick: handleOpenModalForAdd
                    } : undefined}
                />
            ) : (
                <div className="hidden lg:block bg-card rounded-lg shadow-sm border border-border overflow-x-auto">
                    <table className="w-full text-right min-w-[800px] text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-3 py-2 font-bold text-text-secondary sticky right-0 bg-slate-50 border-l border-border">اسم المنتج</th>
                                <th className="px-3 py-2 font-bold text-text-secondary">الوصف</th>
                                <th className="px-3 py-2 font-bold text-text-secondary">التصنيف</th>
                                <th className="px-3 py-2 font-bold text-text-secondary">الوحدة</th>
                                <th className="px-3 py-2 font-bold text-text-secondary">سعر البيع</th>
                                {canManage && <th className="px-3 py-2 font-bold text-text-secondary">متوسط سعر الشراء</th>}
                                {canManage && <th className="px-3 py-2 font-bold text-text-secondary">متوسط سعر البيع</th>}
                                {canManage && <th className="px-3 py-2 font-bold text-text-secondary text-left sticky left-0 bg-slate-50 border-r border-border">إجراءات</th>}
                            </tr>
                        </thead>
                        <tbody className="text-text-primary divide-y divide-border">
                            {filteredProducts.map((product) => (
                                editingProductId === product.id ? (
                                    <tr key={product.id} className="bg-indigo-50">
                                        <td className="px-3 py-2 sticky right-0 bg-indigo-50 border-l border-border">
                                            <input type="text" name="name" value={editedProductData.name} onChange={handleEditInputChange} className="w-full p-1 border rounded bg-white" />
                                        </td>
                                        <td className="px-3 py-2">
                                            <textarea name="description" value={editedProductData.description || ''} onChange={handleEditInputChange} className="w-full p-1 border rounded bg-white text-sm" rows={1}></textarea>
                                        </td>
                                        <td className="px-3 py-2">
                                            <select name="productType" value={editedProductData.productType} onChange={handleEditInputChange} className="w-full p-1 border rounded bg-white">
                                                {Object.values(ProductType).map(type => (<option key={type} value={type}>{type}</option>))}
                                            </select>
                                        </td>
                                        <td className="px-3 py-2">
                                            <select name="unit" value={editedProductData.unit} onChange={handleEditInputChange} className="w-full p-1 border rounded bg-white">
                                                {Object.values(Unit).map(unit => (<option key={unit} value={unit}>{unit}</option>))}
                                            </select>
                                        </td>
                                        <td className="px-3 py-2">
                                            <input type="number" name="sellingPrice" value={editedProductData.sellingPrice || ''} onChange={handleEditInputChange} className="w-full p-1 border rounded bg-white" />
                                        </td>
                                        {canManage && <td className="px-3 py-2 whitespace-nowrap">{formatPrice(product.averagePurchasePrice)}</td>}
                                        {canManage && <td className="px-3 py-2 whitespace-nowrap">{formatPrice(product.averageSellingPrice)}</td>}
                                        {canManage && (
                                            <td className="px-3 py-2 text-left sticky left-0 bg-indigo-50 border-r border-border">
                                                <div className="flex items-center justify-end gap-2">
                                                    <button onClick={handleSaveEdit} disabled={isSaving} title="حفظ" className="p-2 bg-green-100 text-green-700 rounded-full hover:bg-green-200 disabled:opacity-50">
                                                        {isSaving ? <Spinner /> : <CheckIcon className="w-5 h-5" />}
                                                    </button>
                                                    <button onClick={handleCancelEdit} disabled={isSaving} title="إلغاء" className="p-2 bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200">
                                                        <XIcon className="w-5 h-5" />
                                                    </button>
                                                </div>
                                            </td>
                                        )}
                                    </tr>
                                ) : (
                                    <tr key={product.id} className="hover:bg-slate-100 even:bg-slate-50/50">
                                        <td className="px-3 py-2 font-semibold sticky right-0 bg-inherit border-l border-border">{product.name}</td>
                                        <td className="px-3 py-2 text-text-secondary truncate max-w-xs">{product.description}</td>
                                        <td className="px-3 py-2">{product.productType}</td>
                                        <td className="px-3 py-2">{product.unit}</td>
                                        <td className="px-3 py-2 whitespace-nowrap">{formatPrice(product.sellingPrice)}</td>
                                        {canManage && <td className="px-3 py-2 whitespace-nowrap">{formatPrice(product.averagePurchasePrice)}</td>}
                                        {canManage && <td className="px-3 py-2 whitespace-nowrap">{formatPrice(product.averageSellingPrice)}</td>}
                                        {canManage && (
                                            <td className="px-3 py-2 text-left sticky left-0 bg-inherit border-r border-border">
                                                <div className="flex items-center justify-end gap-2">
                                                    <button onClick={() => handleStartEdit(product)} title="تعديل" className="p-2 bg-indigo-100 text-indigo-700 rounded-full hover:bg-indigo-200">
                                                        <PencilIcon className="w-5 h-5" />
                                                    </button>
                                                    <button onClick={() => setProductToDelete(product)} title="حذف" className="p-2 bg-red-100 text-red-700 rounded-full hover:bg-red-200">
                                                        <TrashIcon className="w-5 h-5" />
                                                    </button>
                                                </div>
                                            </td>
                                        )}
                                    </tr>
                                )))}
                        </tbody>
                    </table>
                </div>
            )}

            {/* --- Mobile Card View --- */}
            {!loading && filteredProducts.length > 0 && (
                <div className="lg:hidden space-y-4">
                    {filteredProducts.map((product) => (
                        <div key={product.id} className={`bg-card border border-border rounded-lg p-4 shadow-sm even:bg-slate-50/50 ${editingProductId === product.id ? 'ring-2 ring-primary' : ''}`}>
                            {editingProductId === product.id ? (
                                // --- Edit Mode Card ---
                                <div className="space-y-3">
                                    <div>
                                        <label className="text-xs font-medium text-text-secondary">اسم المنتج</label>
                                        <input type="text" name="name" value={editedProductData.name} onChange={handleEditInputChange} className="w-full p-2 border rounded bg-white text-sm" />
                                    </div>
                                    <div>
                                        <label className="text-xs font-medium text-text-secondary">الوصف</label>
                                        <textarea name="description" value={editedProductData.description || ''} onChange={handleEditInputChange} className="w-full p-2 border rounded bg-white text-sm" rows={2}></textarea>
                                    </div>
                                    <div className="grid grid-cols-2 gap-3">
                                        <div>
                                            <label className="text-xs font-medium text-text-secondary">التصنيف</label>
                                            <select name="productType" value={editedProductData.productType} onChange={handleEditInputChange} className="w-full p-2 border rounded bg-white text-sm">
                                                {Object.values(ProductType).map(type => (<option key={type} value={type}>{type}</option>))}
                                            </select>
                                        </div>
                                        <div>
                                            <label className="text-xs font-medium text-text-secondary">الوحدة</label>
                                            <select name="unit" value={editedProductData.unit} onChange={handleEditInputChange} className="w-full p-2 border rounded bg-white text-sm">
                                                {Object.values(Unit).map(unit => (<option key={unit} value={unit}>{unit}</option>))}
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label className="text-xs font-medium text-text-secondary">سعر البيع</label>
                                        <input type="number" name="sellingPrice" value={editedProductData.sellingPrice || ''} onChange={handleEditInputChange} className="w-full p-2 border rounded bg-white text-sm" />
                                    </div>
                                    <div className="flex items-center justify-end gap-2 pt-3 border-t border-border">
                                        <button onClick={handleSaveEdit} disabled={isSaving} title="حفظ" className="p-2 bg-green-100 text-green-700 rounded-full hover:bg-green-200 disabled:opacity-50">
                                            {isSaving ? <Spinner /> : <CheckIcon className="w-5 h-5" />}
                                        </button>
                                        <button onClick={handleCancelEdit} disabled={isSaving} title="إلغاء" className="p-2 bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200">
                                            <XIcon className="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>
                            ) : (
                                // --- View Mode Card ---
                                <>
                                    <div className="flex justify-between items-start mb-3">
                                        <p className="font-bold text-lg text-text-primary">{product.name}</p>
                                        <span className="text-sm font-semibold text-primary">{formatPrice(product.sellingPrice)}</span>
                                    </div>
                                    {product.description && (
                                        <p className="text-sm text-text-secondary mb-3 pb-3 border-b border-border">{product.description}</p>
                                    )}
                                    <div className="space-y-2 text-sm">
                                        <div className="flex justify-between"><span className="text-text-secondary">التصنيف:</span> <span className="font-medium">{product.productType}</span></div>
                                        <div className="flex justify-between"><span className="text-text-secondary">الوحدة:</span> <span className="font-medium">{product.unit}</span></div>
                                        {canManage && <div className="flex justify-between"><span className="text-text-secondary">متوسط الشراء:</span> <span className="font-medium">{formatPrice(product.averagePurchasePrice)}</span></div>}
                                        {canManage && <div className="flex justify-between"><span className="text-text-secondary">متوسط البيع:</span> <span className="font-medium">{formatPrice(product.averageSellingPrice)}</span></div>}
                                    </div>
                                    {canManage && (
                                        <div className="flex items-center justify-end gap-2 mt-4 border-t border-border pt-3">
                                            <button onClick={() => handleStartEdit(product)} title="تعديل" className="p-2 bg-indigo-100 text-indigo-700 rounded-full hover:bg-indigo-200"><PencilIcon className="w-5 h-5" /></button>
                                            <button onClick={() => setProductToDelete(product)} title="حذف" className="p-2 bg-red-100 text-red-700 rounded-full hover:bg-red-200"><TrashIcon className="w-5 h-5" /></button>
                                        </div>
                                    )}
                                </>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </>
    );
};

export default ProductsListPage;

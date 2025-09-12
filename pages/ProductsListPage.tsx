
import React, { useState } from 'react';
import { useProducts } from '../contexts/ProductContext';
import { useAuth } from '../hooks/useAuth';
import { usePermissions } from '../hooks/usePermissions';
import AddProductModal from '../components/AddProductModal';
import DeleteConfirmationModal from '../components/DeleteConfirmationModal';
import EmptyState from '../components/EmptyState';
// FIX: Import PermissionModule to use enum for type safety.
import { Product, PermissionModule } from '../types';
import Spinner from '../components/Spinner';
import PencilIcon from '../components/icons/PencilIcon';
import TrashIcon from '../components/icons/TrashIcon';
import SearchIcon from '../components/icons/SearchIcon';
import CubeIcon from '../components/icons/CubeIcon';

const ProductsListPage: React.FC = () => {
    const { products, loading, addProduct, updateProduct, deleteProduct } = useProducts();
    const { currentUser } = useAuth();
    const permissions = usePermissions();
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingProduct, setEditingProduct] = useState<Product | null>(null);
    const [searchQuery, setSearchQuery] = useState('');
    const [productToDelete, setProductToDelete] = useState<Product | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [deleteError, setDeleteError] = useState<string | null>(null);

    // FIX: Use PermissionModule enum instead of string literal.
    const canManage = permissions.can(PermissionModule.PRODUCTS, 'manage');

    const handleOpenModalForAdd = () => {
        setEditingProduct(null);
        setIsModalOpen(true);
    };

    const handleOpenModalForEdit = (product: Product) => {
        setEditingProduct(product);
        setIsModalOpen(true);
    };

    const handleSaveProduct = async (productData: Omit<Product, 'id' | 'averagePurchasePrice' | 'averageSellingPrice'> & { id?: number }) => {
        if (!currentUser) {
            return { product: null, error: "User not authenticated" };
        }
        if (productData.id) {
            return updateProduct(productData as Product);
        } else {
            return addProduct(productData as Omit<Product, 'id'>, currentUser.id);
        }
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
                productToEdit={editingProduct}
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
                <div className="bg-card rounded-lg shadow-sm border border-border overflow-x-auto">
                    <table className="w-full text-right min-w-[800px] text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-3 py-2 font-bold text-text-secondary sticky right-0 bg-slate-50 border-l border-border">اسم المنتج</th>
                                <th className="px-3 py-2 font-bold text-text-secondary">التصنيف</th>
                                <th className="px-3 py-2 font-bold text-text-secondary">الوحدة</th>
                                <th className="px-3 py-2 font-bold text-text-secondary">سعر البيع</th>
                                <th className="px-3 py-2 font-bold text-text-secondary">متوسط سعر الشراء</th>
                                <th className="px-3 py-2 font-bold text-text-secondary">متوسط سعر البيع</th>
                                {canManage && <th className="px-3 py-2 font-bold text-text-secondary text-left sticky left-0 bg-slate-50 border-r border-border">إجراءات</th>}
                            </tr>
                        </thead>
                        <tbody className="text-text-primary divide-y divide-border">
                            {filteredProducts.map((product) => (
                                <tr key={product.id} className="hover:bg-slate-50">
                                    <td className="px-3 py-2 font-semibold sticky right-0 bg-white hover:bg-slate-50 border-l border-border">{product.name}</td>
                                    <td className="px-3 py-2">{product.productType}</td>
                                    <td className="px-3 py-2">{product.unit}</td>
                                    <td className="px-3 py-2 whitespace-nowrap">{formatPrice(product.sellingPrice)}</td>
                                    <td className="px-3 py-2 whitespace-nowrap">{formatPrice(product.averagePurchasePrice)}</td>
                                    <td className="px-3 py-2 whitespace-nowrap">{formatPrice(product.averageSellingPrice)}</td>
                                    {canManage && (
                                        <td className="px-3 py-2 text-left sticky left-0 bg-white hover:bg-slate-50 border-r border-border">
                                            <div className="flex items-center justify-end gap-2">
                                                <button onClick={() => handleOpenModalForEdit(product)} title="تعديل" className="p-2 bg-indigo-100 text-indigo-700 rounded-full hover:bg-indigo-200">
                                                    <PencilIcon className="w-5 h-5" />
                                                </button>
                                                <button onClick={() => setProductToDelete(product)} title="حذف" className="p-2 bg-red-100 text-red-700 rounded-full hover:bg-red-200">
                                                    <TrashIcon className="w-5 h-5" />
                                                </button>
                                            </div>
                                        </td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </>
    );
};

export default ProductsListPage;
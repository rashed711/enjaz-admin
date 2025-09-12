import React, { useState } from 'react';
import { useProducts } from '../contexts/ProductContext';
import AddProductModal from '../components/AddProductModal';
import DeleteConfirmationModal from '../components/DeleteConfirmationModal';
import { Product } from '../types';
import Spinner from '../components/Spinner';
import PencilIcon from '../components/icons/PencilIcon';
import TrashIcon from '../components/icons/TrashIcon';

const ProductsListPage: React.FC = () => {
    const { products, loading, addProduct, updateProduct, deleteProduct } = useProducts();
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingProduct, setEditingProduct] = useState<Product | null>(null);
    const [searchQuery, setSearchQuery] = useState('');
    const [productToDelete, setProductToDelete] = useState<Product | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [deleteError, setDeleteError] = useState<string | null>(null);


    const handleOpenModalForAdd = () => {
        setEditingProduct(null);
        setIsModalOpen(true);
    };

    const handleOpenModalForEdit = (product: Product) => {
        setEditingProduct(product);
        setIsModalOpen(true);
    };

    const handleSaveProduct = async (productData: Omit<Product, 'id'> & { id?: number }) => {
        if (productData.id) {
            return await updateProduct(productData as Product);
        } else {
            return await addProduct(productData);
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
        product.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        (product.description && product.description.toLowerCase().includes(searchQuery.toLowerCase()))
    );
    
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
                    <input
                        type="text"
                        placeholder="ابحث بالاسم أو الوصف..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        className="w-full sm:w-64 border border-border bg-card text-text-primary p-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary transition-colors"
                        aria-label="Search products"
                    />
                    <button 
                        onClick={handleOpenModalForAdd}
                        className="w-full sm:w-auto bg-[#4F46E5] text-white font-semibold px-5 py-2 rounded-lg hover:bg-[#4338CA] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background focus:ring-[#4F46E5] transition-all duration-200"
                    >
                        + إضافة منتج جديد
                    </button>
                </div>
            </div>

            {loading ? (
                <div className="flex justify-center items-center p-10">
                    <Spinner />
                </div>
            ) : filteredProducts.length === 0 ? (
                 <div className="bg-card rounded-lg shadow-sm border border-border p-8 text-center">
                    <p className="text-text-secondary">
                        {searchQuery ? 'لا توجد منتجات تطابق بحثك.' : 'لا توجد منتجات لعرضها.'}
                    </p>
                </div>
            ) : (
                <>
                    {/* Desktop Table View */}
                    <div className="bg-card rounded-lg shadow-sm border border-border overflow-x-auto hidden md:block">
                        <table className="w-full text-right min-w-[640px]">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="p-4 font-bold text-text-secondary">اسم المنتج</th>
                                    <th className="p-4 font-bold text-text-secondary">الوصف</th>
                                    <th className="p-4 font-bold text-text-secondary">النوع</th>
                                    <th className="p-4 font-bold text-text-secondary">الوحدة</th>
                                    <th className="p-4 font-bold text-text-secondary">سعر الوحدة</th>
                                    <th className="p-4 font-bold text-text-secondary text-left">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody className="text-text-primary divide-y divide-border">
                                {filteredProducts.map((product) => (
                                    <tr key={product.id} className="hover:bg-slate-50 transition-colors duration-200">
                                        <td className="p-4 font-semibold">{product.name}</td>
                                        <td className="p-4">{product.description}</td>
                                        <td className="p-4">{product.productType}</td>
                                        <td className="p-4">{product.unit}</td>
                                        <td className="p-4">{product.unitPrice.toLocaleString()}</td>
                                        <td className="p-4 text-left">
                                            <div className="flex items-center justify-end gap-2">
                                                <button onClick={() => handleOpenModalForEdit(product)} title="تعديل" className="p-2 text-primary rounded-full hover:bg-primary/10 transition-colors">
                                                    <PencilIcon className="w-5 h-5" />
                                                </button>
                                                <button onClick={() => setProductToDelete(product)} title="حذف" className="p-2 text-red-500 rounded-full hover:bg-red-100 transition-colors">
                                                    <TrashIcon className="w-5 h-5" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Mobile Card View */}
                    <div className="md:hidden space-y-4">
                        {filteredProducts.map(product => (
                            <div key={product.id} className="bg-card rounded-lg shadow-sm p-4 border border-border">
                                <div className="flex justify-between items-start gap-2">
                                    <h3 className="font-bold text-base text-text-primary">{product.name}</h3>
                                    <span className="bg-primary/10 text-primary text-xs font-bold px-2 py-1 rounded-full shrink-0">{product.productType}</span>
                                </div>
                                <p className="text-text-secondary my-2 text-sm">{product.description || 'لا يوجد وصف'}</p>
                                <div className="flex justify-between items-center mt-4 pt-4 border-t border-border">
                                    <div className="text-right">
                                        <p className="font-bold text-lg text-primary">{product.unitPrice.toLocaleString()}</p>
                                        <p className="text-xs text-text-secondary">/ {product.unit}</p>
                                    </div>
                                    <div className="flex gap-1">
                                        <button onClick={() => handleOpenModalForEdit(product)} title="تعديل" className="p-2 text-primary rounded-full hover:bg-primary/10 active:bg-primary/20 transition-colors">
                                            <PencilIcon className="w-6 h-6" />
                                        </button>
                                        <button onClick={() => setProductToDelete(product)} title="حذف" className="p-2 text-red-500 rounded-full hover:bg-red-100 active:bg-red-200 transition-colors">
                                            <TrashIcon className="w-6 h-6" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </>
            )}
        </>
    );
};

export default ProductsListPage;
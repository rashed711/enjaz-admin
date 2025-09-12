import React, { useState } from 'react';
import { useProducts } from '../contexts/ProductContext';
import AddProductModal from '../components/AddProductModal';
import { Product } from '../types';

const ProductsListPage: React.FC = () => {
    const { products, loading, addProduct, updateProduct } = useProducts();
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingProduct, setEditingProduct] = useState<Product | null>(null);
    const [searchQuery, setSearchQuery] = useState('');

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
            <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
                <h2 className="text-2xl font-bold text-dark-text self-start sm:self-center">قائمة المنتجات</h2>
                <div className="flex flex-col sm:flex-row w-full sm:w-auto gap-4">
                    <input
                        type="text"
                        placeholder="ابحث بالاسم أو الوصف..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        className="w-full sm:w-64 border border-border bg-gray-50 text-dark-text p-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary transition-colors"
                        aria-label="Search products"
                    />
                    <button 
                        onClick={handleOpenModalForAdd}
                        className="w-full sm:w-auto bg-[#10B981] text-white font-semibold px-5 py-2 rounded-lg hover:bg-[#059669] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-light-bg focus:ring-[#10B981] transition-all duration-200"
                    >
                        + إضافة منتج جديد
                    </button>
                </div>
            </div>

            {loading ? (
                <p className="p-4 text-center text-muted-text">جاري تحميل المنتجات...</p>
            ) : filteredProducts.length === 0 ? (
                <p className="p-4 text-center text-muted-text">
                    {searchQuery ? 'لا توجد منتجات تطابق بحثك.' : 'لا توجد منتجات لعرضها.'}
                </p>
            ) : (
                <>
                    {/* Desktop Table View */}
                    <div className="bg-white rounded-lg shadow-md overflow-x-auto hidden md:block">
                        <table className="w-full text-right min-w-[640px]">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="p-4 font-bold text-muted-text">اسم المنتج</th>
                                    <th className="p-4 font-bold text-muted-text">الوصف</th>
                                    <th className="p-4 font-bold text-muted-text">النوع</th>
                                    <th className="p-4 font-bold text-muted-text">الوحدة</th>
                                    <th className="p-4 font-bold text-muted-text">سعر الوحدة</th>
                                    <th className="p-4 font-bold text-muted-text"></th>
                                </tr>
                            </thead>
                            <tbody className="text-dark-text">
                                {filteredProducts.map((product) => (
                                    <tr key={product.id} className="border-b border-border hover:bg-gray-50 transition-colors duration-200">
                                        <td className="p-4 font-semibold">{product.name}</td>
                                        <td className="p-4">{product.description}</td>
                                        <td className="p-4">{product.productType}</td>
                                        <td className="p-4">{product.unit}</td>
                                        <td className="p-4">{product.unitPrice.toLocaleString()}</td>
                                        <td className="p-4 text-center">
                                            <button
                                                onClick={() => handleOpenModalForEdit(product)}
                                                className="text-primary hover:underline font-semibold"
                                            >
                                                تعديل
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Mobile Card View */}
                    <div className="md:hidden space-y-4">
                        {filteredProducts.map(product => (
                            <div key={product.id} className="bg-white rounded-lg shadow p-4">
                                <div className="flex justify-between items-start gap-2">
                                    <h3 className="font-bold text-base text-dark-text">{product.name}</h3>
                                    <span className="bg-primary/10 text-primary text-xs font-bold px-2 py-1 rounded-full shrink-0">{product.productType}</span>
                                </div>
                                <p className="text-muted-text my-2 text-sm">{product.description || 'لا يوجد وصف'}</p>
                                <div className="flex justify-between items-center mt-4 pt-4 border-t border-border">
                                    <div className="text-right">
                                        <p className="font-bold text-lg text-primary">{product.unitPrice.toLocaleString()}</p>
                                        <p className="text-xs text-muted-text">/ {product.unit}</p>
                                    </div>
                                    <button onClick={() => handleOpenModalForEdit(product)} className="bg-primary/10 text-primary px-4 py-1.5 rounded-md font-semibold text-sm hover:bg-primary/20 transition-colors">تعديل</button>
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
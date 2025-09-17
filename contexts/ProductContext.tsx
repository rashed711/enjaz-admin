

import React, { createContext, useState, useContext, ReactNode, useEffect, useCallback, useMemo } from 'react';
import { Product, ProductType, Unit } from '../types';
import { supabase } from '../services/supabaseClient';
import { useAuth } from '../hooks/useAuth';

interface ProductContextType {
  products: Product[];
  loading: boolean;
  currentPage: number;
  pageSize: number;
  totalProducts: number;
  addProduct: (product: Omit<Product, 'id' | 'averagePurchasePrice' | 'averageSellingPrice'>) => Promise<{ product: Product | null; error: string | null }>;
  updateProduct: (product: Product) => Promise<{ product: Product | null; error: string | null }>;
  deleteProduct: (productId: number) => Promise<boolean>;
  fetchProducts: () => Promise<void>;
  setPage: (page: number) => void;
  setPageSize: (size: number) => void;
}

const ProductContext = createContext<ProductContextType | undefined>(undefined);

export const ProductProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const { currentUser, loading: authLoading } = useAuth();
  const [currentPage, setCurrentPage] = useState(1);
  const [pageSize, setPageSize] = useState(10); // Default page size
  const [totalProducts, setTotalProducts] = useState(0);

  // Internal fetch function that takes page and size
  const _fetchProducts = useCallback(async (page: number, size: number) => {
    setLoading(true);
    try {
      const from = (page - 1) * size;
      const to = from + size - 1;

      const { data, error, count } = await supabase
        .from('products_with_stats') // Query the new view
        .select('*', { count: 'exact' }) // Request total count
        .order('name', { ascending: true })
        .range(from, to); // Apply pagination - The semicolon was removed here

      if (error) throw error;

      const formattedProducts: Product[] = (data || []).map((p: any) => ({
        id: p.id,
        name: p.name,
        description: p.description || '',
        sellingPrice: p.unit_price, // This is the base selling price
        productType: p.product_type as ProductType,
        unit: p.unit as Unit,
        averagePurchasePrice: p.average_purchase_price, // This comes pre-calculated from the view
        averageSellingPrice: p.average_selling_price, // This also comes from the view
      }));
      setProducts(formattedProducts);
      setTotalProducts(count || 0); // Update total count
    } catch (error: any) {
        console.error('Critical error in fetchProducts. Error:', error.message);
        setProducts([]);
        setTotalProducts(0);
    } finally {
        setLoading(false);
    }
  }, []);


  useEffect(() => {
    if (authLoading) {
      return; // Wait for authentication to be resolved
    }
    if (currentUser) { // Now currentUser is either a user or null
        _fetchProducts(currentPage, pageSize); // Call with current state
    } else {
        // When user logs out, clear the products list
        setProducts([]);
        setTotalProducts(0); // Reset total count on logout
        setLoading(false);
    }
  }, [currentUser, authLoading, currentPage, pageSize, _fetchProducts]);

  const addProduct = useCallback(async (newProductData: Omit<Product, 'id' | 'averagePurchasePrice' | 'averageSellingPrice'>): Promise<{ product: Product | null; error: string | null }> => {
    const { data, error } = await supabase
      .from('products')
      .insert({
          name: newProductData.name,
          description: newProductData.description,
          unit_price: newProductData.sellingPrice,
          product_type: newProductData.productType,
          unit: newProductData.unit,
      })
      .select()
      .single();
    
    if (error) {
      console.error('Error adding product:', error.message);
      return { product: null, error: error.message };
    }
    
    if(data) {
        // Create a full product object for the local state, including default stats.
        const addedProduct: Product = {
            id: data.id,
            name: data.name,
            description: data.description || '',
            sellingPrice: data.unit_price,
            productType: data.product_type as ProductType,
            unit: data.unit as Unit,
            averagePurchasePrice: 0,
            averageSellingPrice: 0,
        };
        _fetchProducts(currentPage, pageSize); // Re-fetch current page to update list and total count
        return { product: addedProduct, error: null };
    }
    return { product: null, error: 'Failed to add product for an unknown reason.' };
  }, [_fetchProducts, currentPage, pageSize]);

  const updateProduct = useCallback(async (updatedProductData: Product): Promise<{ product: Product | null; error: string | null }> => {
    const { data, error } = await supabase
      .from('products')
      .update({
          name: updatedProductData.name,
          description: updatedProductData.description,
          unit_price: updatedProductData.sellingPrice,
          product_type: updatedProductData.productType,
          unit: updatedProductData.unit,
      })
      .eq('id', updatedProductData.id)
      .select()
      .single();
    
    if (error) {
      console.error('Error updating product:', error.message);
      return { product: null, error: error.message };
    }
    
    if(data) {
        _fetchProducts(currentPage, pageSize); // Re-fetch current page to update list
        // The returned product can be constructed from the updated data for immediate use if needed,
        // but re-fetching is safer for consistency.
        return { product: data as Product, error: null };
    }
    return { product: null, error: 'Failed to update product for an unknown reason.' };
  }, [_fetchProducts, currentPage, pageSize]);

  const deleteProduct = useCallback(async (productId: number): Promise<boolean> => {
    const { error } = await supabase
      .from('products')
      .delete()
      .eq('id', productId);
    
    if (error) {
        console.error('Error deleting product:', error.message);
        return false;
    }

    _fetchProducts(currentPage, pageSize); // Re-fetch current page to update list and total count
    return true;
  }, [_fetchProducts, currentPage, pageSize]);

  const value = useMemo(() => ({ products, loading, addProduct, updateProduct, deleteProduct, fetchProducts: () => _fetchProducts(currentPage, pageSize), currentPage, pageSize, totalProducts, setPage: setCurrentPage, setPageSize }),
    [products, loading, addProduct, updateProduct, deleteProduct, _fetchProducts, currentPage, pageSize, totalProducts, setPageSize]);

  return (
    <ProductContext.Provider value={value}>
      {children}
    </ProductContext.Provider>
  );
};

export const useProducts = () => {
  const context = useContext(ProductContext);
  if (!context) {
    throw new Error('useProducts must be used within a ProductProvider');
  }
  return context;
};

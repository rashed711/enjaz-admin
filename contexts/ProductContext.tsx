

import React, { createContext, useState, useContext, ReactNode, useEffect, useCallback, useMemo } from 'react';
import { Product, ProductType, Unit } from '../types';
import { supabase } from '../services/supabaseClient';
import { useAuth } from '../hooks/useAuth';

interface ProductContextType {
  products: Product[];
  loading: boolean;
  addProduct: (product: Omit<Product, 'id'>, userId: string) => Promise<{ product: Product | null; error: string | null }>;
  updateProduct: (product: Product) => Promise<{ product: Product | null; error: string | null }>;
  deleteProduct: (productId: number) => Promise<boolean>;
  fetchProducts: () => Promise<void>;
}

const ProductContext = createContext<ProductContextType | undefined>(undefined);

export const ProductProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const { currentUser } = useAuth();

  const fetchProducts = useCallback(async () => {
    setLoading(true);
    try {
      // Fetch products directly from the view that calculates stats.
      // This is more reliable than an RPC function for this use case.
      const { data, error } = await supabase
        .from('products_with_stats')
        .select('*')
        .order('name', { ascending: true });

      if (error) {
        // If the view fails, it's a critical error (likely permissions or it doesn't exist).
        // Throwing the error will be caught by the catch block.
        throw error;
      }

      const formattedProducts: Product[] = data.map((p: any) => ({
        id: p.id,
        name: p.name,
        sellingPrice: p.unit_price,
        productType: p.product_type as ProductType,
        unit: p.unit as Unit,
        averagePurchasePrice: p.average_purchase_price || 0,
        averageSellingPrice: p.average_selling_price || 0,
      }));
      setProducts(formattedProducts);
    } catch (error: any) {
        console.error('Critical error in fetchProducts. Ensure the `products_with_stats` VIEW is created and has SELECT permissions granted. Error:', error.message);
        setProducts([]);
    } finally {
        setLoading(false);
    }
  }, []);


  useEffect(() => {
    if (currentUser) {
        fetchProducts();
    } else {
        // When user logs out, clear the products list
        setProducts([]);
        setLoading(false);
    }
  }, [currentUser, fetchProducts]);

  const addProduct = useCallback(async (newProductData: Omit<Product, 'id'>, userId: string): Promise<{ product: Product | null; error: string | null }> => {
    const { data, error } = await supabase
      .from('products')
      .insert({
          name: newProductData.name,
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
        // Optimistic update: add the new product to the local state immediately.
        // The full list with updated averages will be fetched on the next page load/refresh.
        const addedProduct = {
            id: data.id,
            name: data.name,
            sellingPrice: data.unit_price,
            productType: data.product_type as ProductType,
            unit: data.unit as Unit,
        };
        setProducts(prev => [addedProduct, ...prev].sort((a, b) => a.name.localeCompare(b.name)));
        return { product: addedProduct, error: null };
    }
    return { product: null, error: 'Failed to add product for an unknown reason.' };
  }, [fetchProducts]);

  const updateProduct = useCallback(async (updatedProductData: Product): Promise<{ product: Product | null; error: string | null }> => {
    const { data, error } = await supabase
      .from('products')
      .update({
          name: updatedProductData.name,
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
        // Optimistic update: update the product in the local state.
        // The full list with updated averages will be fetched on the next page load/refresh.
        const updatedProduct = {
            id: data.id,
            name: data.name,
            sellingPrice: data.unit_price,
            productType: data.product_type as ProductType,
            unit: data.unit as Unit,
        };
        setProducts(prev => prev.map(p => p.id === updatedProduct.id ? updatedProduct : p));
        return { product: updatedProduct, error: null };
    }
    return { product: null, error: 'Failed to update product for an unknown reason.' };
  }, [fetchProducts]);

  const deleteProduct = useCallback(async (productId: number): Promise<boolean> => {
    const { error } = await supabase
      .from('products')
      .delete()
      .eq('id', productId);
    
    if (error) {
        console.error('Error deleting product:', error.message);
        return false;
    }

    // Optimistic update: remove the product from local state.
    setProducts(prev => prev.filter(p => p.id !== productId));
    return true;
  }, [fetchProducts]);

  const value = useMemo(() => ({ products, loading, addProduct, updateProduct, deleteProduct, fetchProducts }), [products, loading, addProduct, updateProduct, deleteProduct, fetchProducts]);

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

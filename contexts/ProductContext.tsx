

import React, { createContext, useState, useContext, ReactNode, useEffect, useCallback, useMemo } from 'react';
import { Product, ProductType, Unit } from '../types';
import { supabase } from '../services/supabaseClient';
import { useAuth } from '../hooks/useAuth';

interface ProductContextType {
  products: Product[];
  loading: boolean;
  addProduct: (product: Omit<Product, 'id' | 'averagePurchasePrice' | 'averageSellingPrice'>) => Promise<{ product: Product | null; error: string | null }>;
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
      // Step 1: Fetch all base products.
      const { data: productsData, error: productsError } = await supabase
        .from('products')
        .select('id, name, description, unit_price, product_type, unit')
        .order('name', { ascending: true });

      if (productsError) throw productsError;

      // This approach is less performant than a DB view but avoids manual SQL execution by the user.
      // It fetches all items to calculate averages on the client.
      // This can be slow if there are many thousands of invoice items.
      const [purchaseStats, salesStats] = await Promise.all([
        supabase.from('purchase_invoice_items').select('product_id, unit_price'),
        supabase.from('sales_invoice_items').select('product_id, unit_price')
      ]);

      const calculateAverages = (items: { product_id: number | null; unit_price: number }[] | null) => {
        if (!items) return new Map<number, number>();
        const sums = new Map<number, { sum: number; count: number }>();
        items.forEach(item => {
          if (item.product_id) {
            const existing = sums.get(item.product_id) || { sum: 0, count: 0 };
            sums.set(item.product_id, { sum: existing.sum + item.unit_price, count: existing.count + 1 });
          }
        });
        const averages = new Map<number, number>();
        sums.forEach((value, key) => {
          averages.set(key, value.sum / value.count);
        });
        return averages;
      }

      const purchaseAverages = calculateAverages(purchaseStats.data);
      const salesAverages = calculateAverages(salesStats.data);

      const formattedProducts: Product[] = productsData.map((p: any) => ({
        id: p.id,
        name: p.name,
        description: p.description || '',
        sellingPrice: p.unit_price,
        productType: p.product_type as ProductType,
        unit: p.unit as Unit,
        averagePurchasePrice: purchaseAverages.get(p.id) || 0,
        averageSellingPrice: salesAverages.get(p.id) || 0,
      }));
      setProducts(formattedProducts);
    } catch (error: any) {
        console.error('Critical error in fetchProducts. Error:', error.message);
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
        setProducts(prev => [addedProduct, ...prev].sort((a, b) => a.name.localeCompare(b.name)));
        return { product: addedProduct, error: null };
    }
    return { product: null, error: 'Failed to add product for an unknown reason.' };
  }, []);

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
        let returnedProduct: Product | null = null;
        // Optimistic update: merge the updated data with the existing product in the local state
        // to preserve calculated stats like average prices.
        setProducts(prev => prev.map(p => {
            if (p.id === data.id) {
                returnedProduct = {
                    ...p, // Keep existing stats
                    name: data.name,
                    description: data.description || '',
                    sellingPrice: data.unit_price,
                    productType: data.product_type as ProductType,
                    unit: data.unit as Unit,
                };
                return returnedProduct;
            }
            return p;
        }));
        return { product: returnedProduct, error: null };
    }
    return { product: null, error: 'Failed to update product for an unknown reason.' };
  }, []);

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
  }, []);

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

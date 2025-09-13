

import React, { createContext, useState, useContext, ReactNode, useEffect, useCallback } from 'react';
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
      // 1. Fetch all products
      const { data: productsData, error: productsError } = await supabase
        .from('products')
        .select('id, name, unit_price, product_type, unit')
        .order('created_at', { ascending: false });

      if (productsError) throw productsError;

      // 2. Fetch all sales invoice items for more accurate sales data
      const { data: saleItemsData, error: saleItemsError } = await supabase
        .from('sales_invoice_items')
        .select('product_id, quantity, total')
        .not('product_id', 'is', null);

      let saleItems = saleItemsData;

      if (saleItemsError) {
        // Gracefully handle "table not found" error, which can happen before DB migration
        if (saleItemsError.message.includes('does not exist') || saleItemsError.message.includes('in the schema cache')) {
            console.warn("`sales_invoice_items` table not found. Average selling price will be 0. Please run the database migration script.");
            saleItems = []; // Treat as empty if table is missing, allowing the rest of the app to function.
        } else {
            // For other, unexpected errors, we should still throw them to be aware of issues.
            throw saleItemsError;
        }
      }

      // 3. Fetch all purchase invoice items with a product_id
      const { data: purchaseItems, error: purchaseItemsError } = await supabase
        .from('purchase_invoice_items')
        .select('product_id, quantity, total')
        .not('product_id', 'is', null);

      if (purchaseItemsError) throw purchaseItemsError;

      // Helper function to process items and aggregate stats robustly
      const processItems = (items: any[], statsMap: Map<number, { totalValue: number; totalQuantity: number }>) => {
        if (!Array.isArray(items)) {
          console.warn('processItems expected an array but received:', items);
          return;
        }

        for (const item of items) {
          if (!item || typeof item !== 'object' || !item.product_id) {
            continue;
          }

          const total = Number(item.total || 0);
          const quantity = Number(item.quantity || 0);
          const productId = Number(item.product_id);
          
          if (isNaN(productId) || isNaN(total) || isNaN(quantity) || quantity <= 0) {
            console.warn('Skipping invoice item with invalid or non-positive quantity data:', item);
            continue;
          }

          const stats = statsMap.get(productId) || { totalValue: 0, totalQuantity: 0 };
          stats.totalValue += total;
          stats.totalQuantity += quantity;
          statsMap.set(productId, stats);
        }
      };


      // 4. Process sale items to calculate total value and quantity per product
      const saleStats = new Map<number, { totalValue: number; totalQuantity: number }>();
      processItems(saleItems, saleStats);

      // 5. Process purchase items to calculate total value and quantity per product
      const purchaseStats = new Map<number, { totalValue: number; totalQuantity: number }>();
      processItems(purchaseItems, purchaseStats);

      // 6. Augment product data with calculated averages
      const formattedProducts: Product[] = productsData.map(p => {
        const pSaleStats = saleStats.get(p.id);
        const averageSellingPrice = pSaleStats && pSaleStats.totalQuantity > 0 ? pSaleStats.totalValue / pSaleStats.totalQuantity : 0;
        
        const pPurchaseStats = purchaseStats.get(p.id);
        const averagePurchasePrice = pPurchaseStats && pPurchaseStats.totalQuantity > 0 ? pPurchaseStats.totalValue / pPurchaseStats.totalQuantity : 0;

        return {
          id: p.id,
          name: p.name,
          sellingPrice: p.unit_price, // Map db field `unit_price` to `sellingPrice`
          productType: p.product_type as ProductType,
          unit: p.unit as Unit,
          averageSellingPrice,
          averagePurchasePrice,
        };
      });

      setProducts(formattedProducts);
    } catch (error: any) {
        console.error('Error fetching products and calculating averages:', error.message);
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

  const addProduct = async (newProductData: Omit<Product, 'id'>, userId: string): Promise<{ product: Product | null; error: string | null }> => {
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
        await fetchProducts(); 
        const addedProduct = {
            id: data.id,
            name: data.name,
            sellingPrice: data.unit_price,
            productType: data.product_type as ProductType,
            unit: data.unit as Unit,
        };
        return { product: addedProduct, error: null };
    }
    return { product: null, error: 'Failed to add product for an unknown reason.' };
  };

  const updateProduct = async (updatedProductData: Product): Promise<{ product: Product | null; error: string | null }> => {
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
        await fetchProducts();
        const updatedProduct = {
            id: data.id,
            name: data.name,
            sellingPrice: data.unit_price,
            productType: data.product_type as ProductType,
            unit: data.unit as Unit,
        };
        return { product: updatedProduct, error: null };
    }
    return { product: null, error: 'Failed to update product for an unknown reason.' };
  };

  const deleteProduct = async (productId: number): Promise<boolean> => {
    const { error } = await supabase
      .from('products')
      .delete()
      .eq('id', productId);
    
    if (error) {
        console.error('Error deleting product:', error.message);
        return false;
    }

    setProducts(prevProducts => prevProducts.filter(p => p.id !== productId));
    return true;
  };


  return (
    <ProductContext.Provider value={{ products, loading, addProduct, updateProduct, deleteProduct, fetchProducts }}>
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
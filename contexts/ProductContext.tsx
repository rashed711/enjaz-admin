import React, { createContext, useState, useContext, ReactNode, useEffect } from 'react';
import { Product, ProductType, Unit } from '../types';
import { supabase } from '../services/supabaseClient';

interface ProductContextType {
  products: Product[];
  loading: boolean;
  addProduct: (product: Omit<Product, 'id'>) => Promise<Product | null>;
  updateProduct: (product: Product) => Promise<Product | null>;
  deleteProduct: (productId: number) => Promise<boolean>;
}

const ProductContext = createContext<ProductContextType | undefined>(undefined);

export const ProductProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchProducts = async () => {
      setLoading(true);
      const { data, error } = await supabase
        .from('products')
        .select('id, name, description, unit_price, product_type, unit')
        .order('created_at', { ascending: false });

      if (error) {
        console.error('Error fetching products:', error);
      } else if (data) {
        const formattedProducts: Product[] = data.map(p => ({
            id: p.id,
            name: p.name,
            description: p.description,
            unitPrice: p.unit_price,
            productType: p.product_type as ProductType,
            unit: p.unit as Unit,
        }));
        setProducts(formattedProducts);
      }
      setLoading(false);
    };
    fetchProducts();
  }, []);

  const addProduct = async (newProductData: Omit<Product, 'id'>) => {
    const { data, error } = await supabase
      .from('products')
      .insert({
          name: newProductData.name,
          description: newProductData.description,
          unit_price: newProductData.unitPrice,
          product_type: newProductData.productType,
          unit: newProductData.unit,
      })
      .select()
      .single();
    
    if (error) {
      console.error('Error adding product:', error);
      return null;
    }
    
    if(data) {
        const addedProduct: Product = { 
            id: data.id,
            name: data.name,
            description: data.description,
            unitPrice: data.unit_price,
            productType: data.product_type as ProductType,
            unit: data.unit as Unit
        };
        setProducts(prevProducts => [addedProduct, ...prevProducts]);
        return addedProduct;
    }
    return null;
  };

  const updateProduct = async (updatedProductData: Product) => {
    const { data, error } = await supabase
      .from('products')
      .update({
          name: updatedProductData.name,
          description: updatedProductData.description,
          unit_price: updatedProductData.unitPrice,
          product_type: updatedProductData.productType,
          unit: updatedProductData.unit,
      })
      .eq('id', updatedProductData.id)
      .select()
      .single();
    
    if (error) {
      console.error('Error updating product:', error);
      return null;
    }
    
    if(data) {
        const updatedProduct: Product = { 
            id: data.id,
            name: data.name,
            description: data.description,
            unitPrice: data.unit_price,
            productType: data.product_type as ProductType,
            unit: data.unit as Unit
        };
        setProducts(prevProducts => prevProducts.map(p => p.id === updatedProduct.id ? updatedProduct : p));
        return updatedProduct;
    }
    return null;
  };

  const deleteProduct = async (productId: number): Promise<boolean> => {
    const { error } = await supabase
      .from('products')
      .delete()
      .eq('id', productId);
    
    if (error) {
        console.error('Error deleting product:', error);
        return false;
    }

    setProducts(prevProducts => prevProducts.filter(p => p.id !== productId));
    return true;
  };


  return (
    <ProductContext.Provider value={{ products, loading, addProduct, updateProduct, deleteProduct }}>
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
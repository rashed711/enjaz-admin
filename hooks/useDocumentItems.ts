import { useCallback } from 'react';
import { Product, DocumentItemState, Unit, ProductType } from '../types';

// A generic document state that we can work with.
type DocumentWithItems = {
    items: DocumentItemState[];
    [key: string]: any;
};

/**
 * A hook to manage document items (add, remove, update, select product).
 * This centralizes the logic used across Quotation, SalesInvoice, and PurchaseInvoice editors.
 * @param setDocument - The state setter function for the document being edited.
 * @param products - The list of available products.
 */
export const useDocumentItems = (
    setDocument: React.Dispatch<React.SetStateAction<DocumentWithItems | null>>,
    products: Product[]
) => {

    const handleItemChange = useCallback((index: number, field: keyof DocumentItemState, value: any) => {
        setDocument(prev => {
            if (!prev) return null;
            const newItems = [...prev.items];
            const currentItem = newItems[index];
            
            const updatedItem = { ...currentItem, [field]: value };

            // Recalculate total when quantity or unitPrice changes
            if (field === 'quantity' || field === 'unitPrice') {
                const quantity = updatedItem.quantity || 0;
                const unitPrice = updatedItem.unitPrice || 0;
                updatedItem.total = parseFloat((quantity * unitPrice).toFixed(2));
            }

            newItems[index] = updatedItem;
            return { ...prev, items: newItems };
        });
    }, [setDocument]);

    const handleProductSelection = useCallback((index: number, productId: number) => {
        const product = products.find(p => p.id === productId);
        if (!product) return;

        setDocument(prev => {
            if (!prev) return null;
            const newItems = [...prev.items];
            const currentItem = newItems[index];

            // Auto-fill description from product, fallback to name. User can edit it later.
            const description = product.description?.trim() ? product.description : product.name;

            const updatedItem = {
                ...currentItem,
                productId: product.id,
                productName: product.name,
                description: description,
                unitPrice: product.sellingPrice,
                unit: product.unit,
                productType: product.productType,
            };

            const quantity = updatedItem.quantity || 1;
            updatedItem.total = parseFloat((quantity * updatedItem.unitPrice).toFixed(2));

            newItems[index] = updatedItem;
            return { ...prev, items: newItems };
        });
    }, [products, setDocument]);

    const addItem = useCallback(() => {
        setDocument(prev => {
            if (!prev) return null;
            const newItem: DocumentItemState = {
                id: -Date.now(), // Temporary unique ID for new items
                productName: '',
                description: '',
                quantity: 1,
                unitPrice: 0,
                total: 0,
                unit: Unit.COUNT,
                productType: ProductType.SIMPLE,
            };
            return { ...prev, items: [...prev.items, newItem] };
        });
    }, [setDocument]);

    const removeItem = useCallback((itemId: number) => {
        setDocument(prev => {
            if (!prev) return null;
            return {
                ...prev,
                items: prev.items.filter(item => item.id !== itemId),
            };
        });
    }, [setDocument]);

    return { handleItemChange, handleProductSelection, addItem, removeItem };
};
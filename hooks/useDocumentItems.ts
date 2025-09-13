import React from 'react';
import { DocumentItemState, Product, Unit } from '../types';

// A generic type for any document state that has an 'items' array.
type DocumentWithItems = {
    items: DocumentItemState[];
    [key: string]: any;
};

// A generic type for the setDocument state dispatcher.
type SetDocument<T> = React.Dispatch<React.SetStateAction<T | null>>;

/**
 * A custom hook to manage document items (add, remove, update).
 * It ensures that new items get a unique temporary ID for stable keys in React.
 */
export const useDocumentItems = <T extends DocumentWithItems>(
    setDocument: SetDocument<T>,
    products: Product[]
) => {
    // This ref will provide unique negative IDs for new, unsaved items.
    const tempIdCounter = React.useRef(-1);

    const calculateItemTotal = (item: DocumentItemState): number => {
        return (item.quantity || 0) * (item.unitPrice || 0);
    };

    const handleItemChange = (index: number, field: keyof DocumentItemState, value: any) => {
        setDocument(prev => {
            if (!prev) return null;
            const newItems = [...prev.items];
            const updatedItem = { ...newItems[index], [field]: value };
            
            // Recalculate total when a relevant field changes
            if (['quantity', 'unitPrice'].includes(field)) {
                updatedItem.total = calculateItemTotal(updatedItem);
            }

            newItems[index] = updatedItem;
            return { ...prev, items: newItems };
        });
    };

    const handleProductSelection = (index: number, productId: number) => {
        const product = products.find(p => p.id === productId);
        if (!product) return;

        setDocument(prev => {
            if (!prev) return null;
            const newItems = [...prev.items];
            const updatedItem = { ...newItems[index], productId: product.id, description: product.name, unit: product.unit, unitPrice: product.sellingPrice };
            updatedItem.total = calculateItemTotal(updatedItem);
            newItems[index] = updatedItem;
            return { ...prev, items: newItems };
        });
    };

    const addItem = () => {
        setDocument(prev => {
            if (!prev) return null;
            const newItem: DocumentItemState = { id: tempIdCounter.current, description: '', quantity: 1, unit: Unit.COUNT, unitPrice: 0, total: 0 };
            tempIdCounter.current -= 1; // Decrement for the next new item
            return { ...prev, items: [...prev.items, newItem] };
        });
    };

    const removeItem = (idToRemove: number) => {
        setDocument(prev => prev ? { ...prev, items: prev.items.filter(item => item.id !== idToRemove) } : null);
    };

    return { handleItemChange, handleProductSelection, addItem, removeItem };
};
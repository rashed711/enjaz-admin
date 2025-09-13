import { useCallback } from 'react';
import { Product, DocumentItemState, Unit, ProductType } from '../types';

// A generic document type that has items.
interface EditableDocument {
    items: DocumentItemState[];
}

// The setter function for the document state.
type DocumentSetter<T extends EditableDocument> = React.Dispatch<React.SetStateAction<T | null>>;

export const useDocumentItems = <T extends EditableDocument>(
    setDocument: DocumentSetter<T>,
    products: Product[],
) => {

    const updateItemDescription = useCallback((item: DocumentItemState, product?: Product): string => {
        const p = product || products.find(prod => prod.id === item.productId);
        if (!p) return item.description;

        let desc = p.name;
        if (p.productType === ProductType.DIFFUSER && item.length && item.width) {
            desc += ` (${item.length} x ${item.width})`;
        } else if (p.productType === ProductType.CABLE_TRAY && item.width && item.height) {
            desc += ` (${item.width} x ${item.height})`;
        }
        return desc;
    }, [products]);

    const handleItemChange = useCallback((index: number, e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
        setDocument(prev => {
            if (!prev) return null;
            const newItems = [...prev.items];
            const itemToUpdate = { ...newItems[index] };
            
            const { name, value } = e.target;
            const numValue = parseFloat(value) || 0;

            switch (name) {
                case 'description': itemToUpdate.description = value; break;
                case 'quantity': itemToUpdate.quantity = numValue; break;
                case 'unitPrice': itemToUpdate.unitPrice = numValue; break;
                case 'length': itemToUpdate.length = numValue; break;
                case 'width': itemToUpdate.width = numValue; break;
                case 'height': itemToUpdate.height = numValue; break;
                case 'unit': itemToUpdate.unit = value as Unit; break;
            }

            if (name === 'length' || name === 'width' || name === 'height') {
                itemToUpdate.description = updateItemDescription(itemToUpdate);
            }
            
            itemToUpdate.total = parseFloat((itemToUpdate.quantity * itemToUpdate.unitPrice).toFixed(2));
            newItems[index] = itemToUpdate;
            return { ...prev, items: newItems };
        });
    }, [setDocument, updateItemDescription]);


    const handleProductSelection = useCallback((itemIndex: number, selectedProductId: string) => {
        setDocument(prev => {
            if (!prev) return null;
            const newItems = [...prev.items];
            let itemToUpdate = { ...newItems[itemIndex] };

            if (selectedProductId === 'custom' || !selectedProductId) {
                itemToUpdate = {
                    ...itemToUpdate,
                    productId: undefined, description: '', unitPrice: 0, unit: Unit.COUNT,
                    productType: ProductType.SIMPLE, length: undefined, width: undefined, height: undefined,
                };
            } else {
                const product = products.find(p => p.id === parseInt(selectedProductId, 10));
                if (product) {
                    itemToUpdate = {
                        ...itemToUpdate,
                        productId: product.id,
                        description: product.name,
                        unitPrice: product.sellingPrice,
                        unit: product.unit,
                        productType: product.productType,
                        length: undefined,
                        width: undefined,
                        height: undefined,
                    };
                    itemToUpdate.description = updateItemDescription(itemToUpdate, product);
                }
            }
            
            itemToUpdate.total = parseFloat((itemToUpdate.quantity * itemToUpdate.unitPrice).toFixed(2));
            newItems[itemIndex] = itemToUpdate;
            return { ...prev, items: newItems };
        });
    }, [setDocument, products, updateItemDescription]);


    const addItem = useCallback(() => {
        setDocument(prev => {
            if (!prev) return null;
            const newItem: DocumentItemState = {
                description: '', quantity: 1, unitPrice: 0, total: 0, unit: Unit.COUNT, productType: ProductType.SIMPLE
            };
            return { ...prev, items: [...prev.items, newItem] };
        });
    }, [setDocument]);
    
    const removeItem = useCallback((index: number) => {
        setDocument(prev => {
            if (!prev) return null;
            const newItems = prev.items.filter((_, i) => i !== index);
            return { ...prev, items: newItems };
        });
    }, [setDocument]);
    
    return {
        handleItemChange,
        handleProductSelection,
        addItem,
        removeItem,
    };
};

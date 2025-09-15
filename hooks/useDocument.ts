

import { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { supabase } from '../services/supabaseClient';
import { useAuth } from './useAuth';
import { useProducts } from '../contexts/ProductContext';
import { 
    Quotation, SalesInvoice, PurchaseInvoice, DocumentItem, DocumentItemState,
    ProductType, Unit, Currency, PurchaseInvoiceStatus, SalesInvoiceStatus 
} from '../types';

type DocumentType = 'quotation' | 'purchase_invoice' | 'sales_invoice';
type AnyDocument = Quotation | PurchaseInvoice | SalesInvoice;
type AnyDocumentState = AnyDocument & { items: DocumentItemState[] };

interface UseDocumentProps {
    documentType: DocumentType;
    id?: string;
}

export const getTaxInfo = (currency: Currency): { rate: number; label: string } => {
    switch (currency) {
        case Currency.EGP: return { rate: 0.14, label: 'Tax (14%)' };
        case Currency.SAR: return { rate: 0.15, label: 'Tax (15%)' };
        case Currency.USD: return { rate: 0, label: 'Tax (0%)' };
        default: return { rate: 0, label: 'Tax' };
    }
};

const docConfig = {
    quotation: {
        mainTable: 'quotations',
        itemsTable: 'quotation_items',
        foreignKey: 'quotation_id',
        listPath: '/quotations',
        viewPath: '/quotations/:id/view',
        dbFieldMap: {
            quotationNumber: 'quotation_number',
            clientName: 'client_name',
            company: 'company',
            project: 'project',
            quotationType: 'quotation_type',
            taxIncluded: 'tax_included',
            discount: null, // FIX: Removed direct mapping to prevent DB error. Discount is now a line item.
            supplierName: null,
            status: null,
            quotationId: null,
        },
        payloadMap: {
            quotation_number: 'quotationNumber',
            client_name: 'clientName',
            company: 'company',
            project: 'project',
            quotation_type: 'quotationType',
            tax_included: 'taxIncluded',
        }
    },
    purchase_invoice: {
        mainTable: 'purchase_invoices',
        itemsTable: 'purchase_invoice_items',
        foreignKey: 'invoice_id',
        listPath: '/invoices',
        viewPath: '/invoices/:id/view',
        dbFieldMap: {
            invoiceNumber: 'invoice_number',
            supplierName: 'supplier_name',
            status: 'status',
            clientName: null,
            company: null,
            project: null,
            quotationType: null,
            quotationId: null,
        },
        payloadMap: {
            invoice_number: 'invoiceNumber',
            supplier_name: 'supplierName',
            status: 'status',
        }
    },
    sales_invoice: {
        mainTable: 'sales_invoices',
        itemsTable: 'sales_invoice_items',
        foreignKey: 'invoice_id',
        listPath: '/sales-invoices',
        viewPath: '/sales-invoices/:id/view',
        dbFieldMap: {
            invoiceNumber: 'invoice_number',
            clientName: 'client_name',
            company: 'company',
            project: 'project',
            status: 'status',
            quotationId: 'quotation_id',
            supplierName: null,
            quotationType: null,
        },
        payloadMap: {
            invoice_number: 'invoiceNumber',
            client_name: 'clientName',
            company: 'company',
            project: 'project',
            status: 'status',
            quotation_id: 'quotationId',
        }
    },
};

export const useDocument = <T extends AnyDocumentState>({ documentType, id: idParam }: UseDocumentProps) => {
    const navigate = useNavigate();
    const { currentUser } = useAuth();
    const { products, fetchProducts } = useProducts();
    const config = docConfig[documentType];

    const [document, setDocument] = useState<T | null>(null);
    const [loading, setLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);
    const [saveError, setSaveError] = useState<string | null>(null);

    const isNew = idParam === 'new';

    const getNewDocumentDefault = useCallback(async (): Promise<T> => {
        const { count } = await supabase.from(config.mainTable).select('*', { count: 'exact', head: true });
        const prefix = config.mainTable.replace('_', '-').slice(0, 4).toUpperCase();
        const newDocNumber = `${prefix}-2024-${(count ?? 0) + 1}`;

        const base = {
            id: undefined,
            date: new Date().toISOString().split('T')[0],
            items: [{
                id: -Date.now(), // Temporary unique ID for new items
                productName: '',
                description: '',
                quantity: 1,
                unitPrice: 0,
                total: 0,
                unit: Unit.COUNT
            }],
            totalAmount: 0,
            createdBy: currentUser?.id || '',
        };

        switch (documentType) {
            case 'quotation':
                return { ...base, quotationNumber: newDocNumber, clientName: '', company: '', project: '', quotationType: '', currency: Currency.EGP, taxIncluded: true, discount: 0 } as T;
            case 'purchase_invoice':
                return { ...base, invoiceNumber: newDocNumber, supplierName: '', status: PurchaseInvoiceStatus.DRAFT, currency: Currency.EGP } as T;
            case 'sales_invoice':
                return { ...base, invoiceNumber: newDocNumber, clientName: '', company: '', project: '', status: SalesInvoiceStatus.DRAFT, currency: Currency.EGP } as T;
            default:
                throw new Error("Invalid document type for new document generation.");
        }
    }, [config, currentUser, documentType]);

    useEffect(() => {
        const fetchDocument = async (docId: number) => {
            setLoading(true);
            try {
                // Fetch main document and its items concurrently for better performance.
                const [docResult, itemsResult] = await Promise.all([
                    supabase.from(config.mainTable).select('*').eq('id', docId).single(),
                    supabase.from(config.itemsTable).select('*').eq(config.foreignKey, docId)
                ]);

                const { data: docData, error: docError } = docResult;
                if (docError) throw docError;

                const { data: itemsData, error: itemsError } = itemsResult;
                if (itemsError) throw itemsError;

                // Fetch creator name
                let creatorName = 'غير معروف';
                if (docData.created_by) {
                    const { data: profile, error: profileError } = await supabase
                        .from('profiles')
                        .select('name')
                        .eq('id', docData.created_by)
                        .single();
                    
                    if (profile) creatorName = profile.name;
                    else if (profileError) console.warn(`Could not fetch creator name for user ${docData.created_by}:`, profileError.message);
                }

                // For quotations, extract the discount which is saved as a special line item.
                const discountItem = documentType === 'quotation' 
                    ? (itemsData || []).find(item => item.description === 'Discount' && item.quantity === 1)
                    : null;
                const discountAmount = discountItem ? -discountItem.unit_price : 0;
                // Filter out the discount item from the main items list.
                const regularItemsData = (itemsData || []).filter(item => item.id !== discountItem?.id);
                
                let wasTaxIncluded = true; // Default for quotations
                if (documentType === 'quotation') {
                    // If the `tax_included` column exists and has a value, use it.
                    if (docData.tax_included !== null && docData.tax_included !== undefined) {
                        wasTaxIncluded = docData.tax_included;
                    } else {
                        // Fallback logic for old records: infer if tax was included by checking totals.
                        const subTotal = regularItemsData.reduce((acc, item) => acc + (item.total || 0), 0);
                        const taxableAmount = subTotal - discountAmount;
                        const taxInfo = getTaxInfo(docData.currency as Currency);
                        const tax = taxableAmount * taxInfo.rate;
                        const grandTotalWithTax = taxableAmount + tax;
                        
                        // Compare the calculated total with the saved total.
                        const savedTotalAmount = docData.total_amount;
                        // Use a small tolerance for floating point comparison.
                        wasTaxIncluded = Math.abs(savedTotalAmount - grandTotalWithTax) < 0.01;
                    }
                }


                const augmentedItems: DocumentItemState[] = (regularItemsData || []).map(item => {
                    const product = products.find(p => p.id === item.product_id);
                    return {
                        id: item.id,
                        productId: item.product_id,
                        productName: product?.name,
                        description: item.description,
                        quantity: item.quantity,
                        unitPrice: item.unit_price,
                        total: item.total,
                        unit: (item.unit as Unit) || (product?.unit || Unit.COUNT),
                        productType: product?.productType || ProductType.SIMPLE,
                    };
                });
                
                const docFields = Object.entries(config.dbFieldMap).reduce((acc, [key, val]) => {
                    if (val) {
                        acc[key] = docData[val];
                    }
                    return acc;
                }, {} as any);

                if (documentType === 'quotation') {
                    docFields.discount = discountAmount;
                    docFields.taxIncluded = wasTaxIncluded;
                }
                
                const fetchedDoc = {
                    id: docData.id,
                    date: docData.date,
                    currency: docData.currency,
                    totalAmount: docData.total_amount,
                    createdBy: docData.created_by,
                    creatorName: creatorName,
                    items: augmentedItems,
                    ...docFields
                } as T;
                
                setDocument(fetchedDoc);

            } catch (error: any) {
                console.error(`Error fetching ${documentType}:`, error.message);
                navigate('/404');
            } finally {
                setLoading(false);
            }
        };

        if (isNew) {
            getNewDocumentDefault().then(setDocument).finally(() => setLoading(false));
        } else if (idParam && products.length > 0) {
            fetchDocument(parseInt(idParam, 10));
        }
    }, [idParam, navigate, currentUser, isNew, products, config, documentType, getNewDocumentDefault]);

    const handleSave = async () => {
        if (!document) return;

        // --- Validation ---
        if (documentType === 'quotation') {
            const quote = document as Quotation;
            if (!quote.clientName?.trim() && !quote.company?.trim()) {
                setSaveError("يجب إدخال اسم العميل أو اسم الشركة على الأقل.");
                return; // Stop before setting isSaving to true
            }
            if (quote.items.length === 0 || quote.items.every(item => !item.description?.trim())) {
                setSaveError("يجب أن يحتوي عرض السعر على بند واحد على الأقل.");
                return; // Stop before setting isSaving to true
            }
        }
        // --- End Validation ---

        setIsSaving(true);
        setSaveError(null);

        try {
            const { items, id, ...docDetails } = document;
            
            let discountToSaveAsItem = 0;
            if (documentType === 'quotation') {
                const quoteDetails = docDetails as any;
                discountToSaveAsItem = quoteDetails.discount || 0;
                delete quoteDetails.discount;
            }

            const payload = Object.entries(config.payloadMap).reduce((acc, [dbKey, docKey]) => {
                const value = docDetails[docKey as keyof typeof docDetails];
                if (value !== undefined) {
                    acc[dbKey] = value;
                }
                return acc;
            }, {} as any);
            
            payload.date = docDetails.date;
            payload.currency = docDetails.currency;
            payload.total_amount = docDetails.totalAmount;

            let savedDocId = id;

            if (isNew) {
                payload.created_by = currentUser?.id || '';
                const { data: newDoc, error } = await supabase.from(config.mainTable).insert(payload).select('id').single();
                if (error || !newDoc) throw error || new Error('Failed to create document');
                savedDocId = newDoc.id;
            } else {
                const { error } = await supabase.from(config.mainTable).update(payload).eq('id', id);
                if (error) throw error;
                await supabase.from(config.itemsTable).delete().eq(config.foreignKey, id);
            }

            const finalItems = [...items];
            if (documentType === 'quotation' && discountToSaveAsItem > 0) {
                finalItems.push({
                    description: 'Discount',
                    quantity: 1,
                    unitPrice: -discountToSaveAsItem,
                    total: -discountToSaveAsItem,
                    unit: Unit.COUNT,
                    productId: null,
                    productType: ProductType.SIMPLE
                } as DocumentItemState);
            }


            if (finalItems.length > 0) {
                const itemsToInsert = finalItems.map(item => ({
                    [config.foreignKey]: savedDocId,
                    product_id: item.productId || null,
                    description: item.description,
                    quantity: item.quantity,
                    unit_price: item.unitPrice,
                    total: item.total,
                    unit: item.unit,
                }));
                const { error: itemsError } = await supabase.from(config.itemsTable).insert(itemsToInsert);
                if (itemsError) throw itemsError;
            }

            await fetchProducts();
            navigate(config.viewPath.replace(':id', savedDocId!.toString()), { replace: true });

        } catch (error: any) {
            console.error(`Failed to save ${documentType}:`, error.message);
            setSaveError(`حدث خطأ أثناء حفظ المستند: ${error.message}`);
        } finally {
            setIsSaving(false);
        }
    };
    
    return { document, setDocument, loading, isSaving, saveError, handleSave };
};
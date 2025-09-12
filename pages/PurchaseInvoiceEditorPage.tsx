import React, { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { PurchaseInvoice, PurchaseInvoiceItem, Currency, Product, ProductType, Unit, PurchaseInvoiceStatus } from '../types';
import { useAuth } from '../hooks/useAuth';
import { useProducts } from '../contexts/ProductContext';
import { supabase } from '../services/supabaseClient';
import PurchaseInvoiceViewer from '../components/PurchaseInvoiceViewer';
import PurchaseInvoiceEditorForm from '../components/PurchaseInvoiceEditorForm';

// Extend PurchaseInvoiceItem for editor-specific state
export interface PurchaseInvoiceItemState extends PurchaseInvoiceItem {
    productType?: ProductType;
}

export type PurchaseInvoiceState = Omit<PurchaseInvoice, 'items'> & { items: PurchaseInvoiceItemState[] };

const PurchaseInvoiceEditorPage: React.FC = () => {
    const { id: idParam, mode } = useParams<{ id: string; mode?: string }>();
    const navigate = useNavigate();
    const { currentUser } = useAuth();
    const { products } = useProducts();

    const [invoice, setInvoice] = useState<PurchaseInvoiceState | null>(null);
    const [loading, setLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);
    const [saveError, setSaveError] = useState<string | null>(null);

    const isNew = idParam === 'new';
    const isEditMode = mode === 'edit';

    useEffect(() => {
        const createNewInvoice = async () => {
            const { count } = await supabase.from('purchase_invoices').select('*', { count: 'exact', head: true });
            const newInvoiceNumber = `INV-2024-${(count ?? 0) + 1}`;

            setInvoice({
                invoiceNumber: newInvoiceNumber,
                supplierName: '',
                date: new Date().toISOString().split('T')[0],
                currency: Currency.EGP,
                status: PurchaseInvoiceStatus.DRAFT,
                items: [{ description: '', quantity: 1, unitPrice: 0, total: 0, unit: Unit.COUNT }],
                totalAmount: 0,
                createdBy: currentUser?.id || '',
            });
            setLoading(false);
        };
        
        const fetchInvoice = async (invoiceId: number) => {
            setLoading(true);
            try {
                const { data: iData, error: iError } = await supabase.from('purchase_invoices').select('id, invoice_number, supplier_name, date, currency, status, total_amount, created_by').eq('id', invoiceId).single();
                if (iError) throw iError;
                
                const { data: itemsData, error: itemsError } = await supabase.from('purchase_invoice_items').select('*').eq('invoice_id', invoiceId);
                if (itemsError) throw itemsError;
                
                const augmentedItems: PurchaseInvoiceItemState[] = itemsData ? itemsData.map(item => {
                    const product = products.find(p => p.id === item.product_id);
                    const fetchedItem: PurchaseInvoiceItemState = {
                        id: item.id,
                        productId: item.product_id,
                        description: item.description,
                        quantity: item.quantity,
                        unitPrice: item.unit_price,
                        total: item.total,
                        unit: (item.unit as Unit) || (product ? product.unit : Unit.COUNT),
                        productType: product ? product.productType : ProductType.SIMPLE,
                        length: item.length,
                        width: item.width,
                        height: item.height,
                    };
                    return fetchedItem;
                }) : [];
    
                const fetchedInvoice: PurchaseInvoiceState = {
                    id: iData.id,
                    invoiceNumber: iData.invoice_number,
                    supplierName: iData.supplier_name,
                    date: iData.date,
                    currency: iData.currency as Currency,
                    status: iData.status as PurchaseInvoiceStatus,
                    totalAmount: iData.total_amount,
                    createdBy: iData.created_by,
                    items: augmentedItems,
                };
    
                setInvoice(fetchedInvoice);

            } catch (error: any) {
                console.error('Error fetching invoice details:', error);
                navigate('/404');
            } finally {
                setLoading(false);
            }
        }

        if (isNew) {
            createNewInvoice();
        } else if (idParam && products.length > 0) {
            fetchInvoice(parseInt(idParam, 10));
        }
    }, [idParam, navigate, currentUser, isNew, products]);

    const createInvoice = async (inv: PurchaseInvoiceState) => {
        const { items, ...invoiceDetails } = inv;
        const { data: newI, error: createError } = await supabase.from('purchase_invoices').insert({
            invoice_number: invoiceDetails.invoiceNumber,
            supplier_name: invoiceDetails.supplierName,
            date: invoiceDetails.date,
            currency: invoiceDetails.currency,
            status: invoiceDetails.status,
            total_amount: invoiceDetails.totalAmount,
            created_by: invoiceDetails.createdBy,
        }).select().single();
        if (createError || !newI) throw createError;

        const itemsWithId = items.map(item => ({
            invoice_id: newI.id,
            product_id: item.productId || null,
            description: item.description,
            quantity: item.quantity,
            unit_price: item.unitPrice,
            total: item.total,
            unit: item.unit,
            length: item.length,
            width: item.width,
            height: item.height,
        }));
        if (itemsWithId.length > 0) {
            const { error: itemsError } = await supabase.from('purchase_invoice_items').insert(itemsWithId);
            if (itemsError) throw itemsError;
        }

        navigate(`/invoices/${newI.id}/view`);
    };

    const updateInvoice = async (inv: PurchaseInvoiceState) => {
        const { items, id, ...invoiceDetails } = inv;
        const { error: updateError } = await supabase.from('purchase_invoices').update({
            invoice_number: invoiceDetails.invoiceNumber,
            supplier_name: invoiceDetails.supplierName,
            date: invoiceDetails.date,
            currency: invoiceDetails.currency,
            status: invoiceDetails.status,
            total_amount: invoiceDetails.totalAmount,
        }).eq('id', id);
        if (updateError) throw updateError;

        const { error: deleteError } = await supabase.from('purchase_invoice_items').delete().eq('invoice_id', id);
        if (deleteError) throw deleteError;

        if (items.length > 0) {
            const itemsWithId = items.map(item => ({
                invoice_id: id,
                product_id: item.productId || null,
                description: item.description,
                quantity: item.quantity,
                unit_price: item.unitPrice,
                total: item.total,
                unit: item.unit,
                length: item.length,
                width: item.width,
                height: item.height,
            }));
            const { error: itemsError } = await supabase.from('purchase_invoice_items').insert(itemsWithId);
            if (itemsError) throw itemsError;
        }

        navigate(`/invoices/${inv.id}/view`, { replace: true });
    };

    const handleSave = async () => {
        if (!invoice || !currentUser) return;
        setIsSaving(true);
        setSaveError(null);
    
        try {
            if (!invoice.id) {
                await createInvoice(invoice);
            } else {
                await updateInvoice(invoice);
            }
        } catch (error: any) {
            console.error("Failed to save invoice:", error);
            let errorMessage = "An unexpected error occurred. Check the console for details.";
            if (error) {
                errorMessage = typeof error.message === 'string' ? error.message : JSON.stringify(error);
            }
            setSaveError(`حدث خطأ أثناء حفظ الفاتورة: ${errorMessage}`);
        } finally {
            setIsSaving(false);
        }
    };
    
    if (loading || !invoice) {
        return <div className="flex justify-center items-center h-full text-dark-text">جاري التحميل...</div>;
    }
    
    if (isNew || isEditMode) {
        return (
            <PurchaseInvoiceEditorForm 
                invoice={invoice}
                setInvoice={setInvoice}
                onSave={handleSave}
                isSaving={isSaving}
                onCancel={() => isNew ? navigate('/invoices') : navigate(`/invoices/${invoice.id}/view`)}
                saveError={saveError}
            />
        )
    }

    return (
        <PurchaseInvoiceViewer 
            invoice={invoice as PurchaseInvoice}
        />
    );
};

export default PurchaseInvoiceEditorPage;
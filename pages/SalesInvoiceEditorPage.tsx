
import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { SalesInvoice, Currency, Product, ProductType, Unit, SalesInvoiceStatus, DocumentItemState } from '../types';
import { useAuth } from '../hooks/useAuth';
import { useProducts } from '../contexts/ProductContext';
import { supabase } from '../services/supabaseClient';
import SalesInvoiceViewer from '../components/SalesInvoiceViewer';
import SalesInvoiceEditorForm from '../components/SalesInvoiceEditorForm';

export type SalesInvoiceState = Omit<SalesInvoice, 'items'> & { items: DocumentItemState[] };

const SalesInvoiceEditorPage: React.FC = () => {
    const { id: idParam, mode } = useParams<{ id: string; mode?: string }>();
    const navigate = useNavigate();
    const { currentUser } = useAuth();
    const { products, fetchProducts } = useProducts();

    const [invoice, setInvoice] = useState<SalesInvoiceState | null>(null);
    const [loading, setLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);
    const [saveError, setSaveError] = useState<string | null>(null);

    const isNew = idParam === 'new';
    const isEditMode = mode === 'edit';

    useEffect(() => {
        const createNewInvoice = async () => {
            const { count } = await supabase.from('sales_invoices').select('*', { count: 'exact', head: true });
            const newInvoiceNumber = `SALE-INV-2024-${(count ?? 0) + 1}`;

            setInvoice({
                invoiceNumber: newInvoiceNumber,
                clientName: '',
                company: '',
                project: '',
                date: new Date().toISOString().split('T')[0],
                currency: Currency.SAR,
                status: SalesInvoiceStatus.DRAFT,
                items: [{ description: '', quantity: 1, unitPrice: 0, total: 0, unit: Unit.COUNT }],
                totalAmount: 0,
                createdBy: currentUser?.id || '',
            });
            setLoading(false);
        };
        
        const fetchInvoice = async (invoiceId: number) => {
            setLoading(true);
            try {
                const { data: iData, error: iError } = await supabase.from('sales_invoices').select('*').eq('id', invoiceId).single();
                if (iError) throw iError;
                
                const { data: itemsData, error: itemsError } = await supabase.from('sales_invoice_items').select('*').eq('invoice_id', invoiceId);
                if (itemsError) throw itemsError;
                
                const augmentedItems: DocumentItemState[] = itemsData ? itemsData.map(item => {
                    const product = products.find(p => p.id === item.product_id);
                    return {
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
                }) : [];
    
                const fetchedInvoice: SalesInvoiceState = {
                    id: iData.id,
                    invoiceNumber: iData.invoice_number,
                    clientName: iData.client_name,
                    company: iData.company,
                    project: iData.project,
                    date: iData.date,
                    currency: iData.currency as Currency,
                    status: iData.status as SalesInvoiceStatus,
                    totalAmount: iData.total_amount,
                    createdBy: iData.created_by,
                    quotationId: iData.quotation_id,
                    items: augmentedItems,
                };
    
                setInvoice(fetchedInvoice);

            } catch (error: any) {
                console.error('Error fetching sales invoice:', error.message);
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

    const handleSave = async () => {
        if (!invoice || !currentUser) return;
        setIsSaving(true);
        setSaveError(null);
    
        try {
            const { items, id, ...invoiceDetails } = invoice;
            const payload = {
                invoice_number: invoiceDetails.invoiceNumber,
                client_name: invoiceDetails.clientName,
                company: invoiceDetails.company,
                project: invoiceDetails.project,
                date: invoiceDetails.date,
                currency: invoiceDetails.currency,
                status: invoiceDetails.status,
                total_amount: invoiceDetails.totalAmount,
                created_by: invoiceDetails.createdBy,
                quotation_id: invoiceDetails.quotationId,
            };

            let savedInvoiceId = id;

            if (isNew) {
                const { data: newI, error } = await supabase.from('sales_invoices').insert(payload).select('id').single();
                if (error || !newI) throw error || new Error('Failed to create invoice');
                savedInvoiceId = newI.id;
            } else {
                const { error } = await supabase.from('sales_invoices').update(payload).eq('id', id);
                if (error) throw error;
                // Delete old items before inserting new ones
                const { error: deleteError } = await supabase.from('sales_invoice_items').delete().eq('invoice_id', id);
                if (deleteError) throw deleteError;
            }
            
            if (items.length > 0) {
                const itemsWithId = items.map(item => ({
                    invoice_id: savedInvoiceId,
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
                const { error: itemsError } = await supabase.from('sales_invoice_items').insert(itemsWithId);
                if (itemsError) throw itemsError;
            }

            await fetchProducts(); // Recalculate product averages
            navigate(`/sales-invoices/${savedInvoiceId}/view`, { replace: true });

        } catch (error: any) {
            console.error("Failed to save sales invoice:", error.message);
            setSaveError(`حدث خطأ أثناء حفظ الفاتورة: ${error.message}`);
        } finally {
            setIsSaving(false);
        }
    };
    
    if (loading || !invoice) {
        return <div className="flex justify-center items-center h-full">جاري التحميل...</div>;
    }
    
    if (isNew || isEditMode) {
        return (
            <SalesInvoiceEditorForm 
                invoice={invoice}
                setInvoice={setInvoice}
                onSave={handleSave}
                isSaving={isSaving}
                onCancel={() => isNew ? navigate('/sales-invoices') : navigate(`/sales-invoices/${invoice.id}/view`)}
                saveError={saveError}
            />
        )
    }

    return (
        <SalesInvoiceViewer 
            invoice={invoice as SalesInvoice}
        />
    );
};

export default SalesInvoiceEditorPage;

import React, { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Quotation, QuotationItem, Currency, Product, ProductType, Unit } from '../types';
import { useAuth } from '../hooks/useAuth';
import { useProducts } from '../contexts/ProductContext';
import { supabase } from '../services/supabaseClient';
import QuotationViewer from '../components/QuotationViewer';
import QuotationEditorForm from '../components/QuotationEditorForm';

// Extend QuotationItem for editor-specific state
export interface QuotationItemState extends QuotationItem {
    productType?: ProductType;
}

export type QuotationState = Omit<Quotation, 'items'> & { items: QuotationItemState[] };

const QuotationEditorPage: React.FC = () => {
    const { id: idParam, mode } = useParams<{ id: string; mode?: string }>();
    const navigate = useNavigate();
    const { currentUser } = useAuth();
    const { products } = useProducts();

    const [quotation, setQuotation] = useState<QuotationState | null>(null);
    const [loading, setLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);
    const [saveError, setSaveError] = useState<string | null>(null);

    const isNew = idParam === 'new';
    const isEditMode = mode === 'edit';

    useEffect(() => {
        const createNewQuotation = async () => {
            const { count } = await supabase.from('quotations').select('*', { count: 'exact', head: true });
            const newQuotationNumber = `ENJ-2024-${(count ?? 0) + 1}`;

            setQuotation({
                quotationNumber: newQuotationNumber,
                clientName: '',
                company: '',
                project: '',
                quotationType: '',
                date: new Date().toISOString().split('T')[0],
                currency: Currency.SAR,
                items: [{ description: '', quantity: 1, unitPrice: 0, total: 0, unit: Unit.COUNT }],
                totalAmount: 0,
                createdBy: currentUser?.id || '',
            });
            setLoading(false);
        };
        
        const fetchQuotation = async (quotationId: number) => {
            setLoading(true);
            
            const { data: qData, error: qError } = await supabase.from('quotations').select('*').eq('id', quotationId).single();
            if (qError) {
                console.error('Error fetching quotation:', qError);
                return navigate('/404');
            }
            
            const { data: itemsData, error: itemsError } = await supabase.from('quotation_items').select('*').eq('quotation_id', quotationId);
            if (itemsError) console.error('Error fetching items:', itemsError);
            
            const augmentedItems: QuotationItemState[] = itemsData ? itemsData.map(item => {
                const product = products.find(p => p.id === item.product_id);
                const fetchedItem: QuotationItemState = {
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

            const fetchedQuotation: QuotationState = {
                id: qData.id,
                quotationNumber: qData.quotation_number,
                clientName: qData.client_name,
                company: qData.company,
                project: qData.project,
                quotationType: qData.quotation_type,
                date: qData.date,
                currency: qData.currency as Currency,
                totalAmount: qData.total_amount,
                createdBy: qData.created_by,
                items: augmentedItems,
            };

            setQuotation(fetchedQuotation);
            setLoading(false);
        }

        if (isNew) {
            createNewQuotation();
        } else if (idParam && products.length > 0) {
            fetchQuotation(parseInt(idParam, 10));
        }
    }, [idParam, navigate, currentUser, isNew, products]);

    const createQuotation = async (q: QuotationState) => {
        const { items, ...quotationDetails } = q;
        const { data: newQ, error: createError } = await supabase.from('quotations').insert({
            quotation_number: quotationDetails.quotationNumber,
            client_name: quotationDetails.clientName,
            company: quotationDetails.company,
            project: quotationDetails.project,
            quotation_type: quotationDetails.quotationType,
            date: quotationDetails.date,
            currency: quotationDetails.currency,
            total_amount: quotationDetails.totalAmount,
            created_by: quotationDetails.createdBy,
        }).select().single();
        if (createError || !newQ) throw createError;

        const itemsWithId = items.map(item => ({
            quotation_id: newQ.id,
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
        const { error: itemsError } = await supabase.from('quotation_items').insert(itemsWithId);
        if (itemsError) throw itemsError;

        navigate(`/quotations/${newQ.id}/view`);
    };

    const updateQuotation = async (q: QuotationState) => {
        const { items, id, ...quotationDetails } = q;
        const { error: updateError } = await supabase.from('quotations').update({
            quotation_number: quotationDetails.quotationNumber,
            client_name: quotationDetails.clientName,
            company: quotationDetails.company,
            project: quotationDetails.project,
            quotation_type: quotationDetails.quotationType,
            date: quotationDetails.date,
            currency: quotationDetails.currency,
            total_amount: quotationDetails.totalAmount,
        }).eq('id', id);
        if (updateError) throw updateError;

        const { error: deleteError } = await supabase.from('quotation_items').delete().eq('quotation_id', id);
        if (deleteError) throw deleteError;

        if (items.length > 0) {
            const itemsWithId = items.map(item => ({
                quotation_id: id,
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
            const { error: itemsError } = await supabase.from('quotation_items').insert(itemsWithId);
            if (itemsError) throw itemsError;
        }

        navigate(`/quotations/${q.id}/view`, { replace: true });
    };

    const handleSave = async () => {
        if (!quotation || !currentUser) return;
        setIsSaving(true);
        setSaveError(null);
    
        try {
            if (!quotation.id) {
                await createQuotation(quotation);
            } else {
                await updateQuotation(quotation);
            }
        } catch (error: any) {
            console.error("Failed to save quotation:", error);
            const errorMessage = error?.message || "An unexpected error occurred.";
            setSaveError(`حدث خطأ أثناء حفظ عرض السعر: ${errorMessage}`);
        } finally {
            setIsSaving(false);
        }
    };
    
    if (loading || !quotation) {
        return <div className="flex justify-center items-center h-full text-dark-text">جاري التحميل...</div>;
    }
    
    if (isNew || isEditMode) {
        return (
            <QuotationEditorForm 
                quotation={quotation}
                setQuotation={setQuotation}
                onSave={handleSave}
                isSaving={isSaving}
                onCancel={() => isNew ? navigate('/quotations') : navigate(`/quotations/${quotation.id}/view`)}
                saveError={saveError}
            />
        )
    }

    return (
        <QuotationViewer 
            quotation={quotation as Quotation}
        />
    );
};

export default QuotationEditorPage;
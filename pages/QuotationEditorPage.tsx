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
    const { id: idParam } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const { currentUser } = useAuth();
    const { products } = useProducts();

    const [quotation, setQuotation] = useState<QuotationState | null>(null);
    const [isEditing, setIsEditing] = useState(false);
    const [loading, setLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);

    const isNew = idParam === 'new';
    
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
            setIsEditing(true);
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

    const handleSave = async () => {
        if (!quotation || !currentUser) return;
        setIsSaving(true);
    
        const quotationDataForDb = {
            quotation_number: quotation.quotationNumber,
            client_name: quotation.clientName,
            company: quotation.company,
            project: quotation.project,
            quotation_type: quotation.quotationType,
            date: quotation.date,
            currency: quotation.currency,
            total_amount: quotation.totalAmount,
            created_by: quotation.createdBy,
        };
    
        const itemsDataForDb = quotation.items.map(item => ({
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
    
        try {
            if (!quotation.id) { // Create new quotation
                const { data: newQ, error: createError } = await supabase.from('quotations').insert(quotationDataForDb).select().single();
                if (createError || !newQ) throw createError;
    
                const itemsWithId = itemsDataForDb.map(item => ({ ...item, quotation_id: newQ.id }));
                const { error: itemsError } = await supabase.from('quotation_items').insert(itemsWithId);
                if (itemsError) throw itemsError;
    
                navigate(`/quotations/${newQ.id}`);
    
            } else { // Update existing quotation
                const { error: updateError } = await supabase.from('quotations').update(quotationDataForDb).eq('id', quotation.id);
                if (updateError) throw updateError;
    
                const { error: deleteError } = await supabase.from('quotation_items').delete().eq('quotation_id', quotation.id);
                if (deleteError) throw deleteError;
    
                const itemsWithId = itemsDataForDb.map(item => ({ ...item, quotation_id: quotation.id }));
                if (itemsWithId.length > 0) {
                    const { error: itemsError } = await supabase.from('quotation_items').insert(itemsWithId);
                    if (itemsError) throw itemsError;
                }
    
                setIsEditing(false);
            }
        } catch (error: any) {
            console.error("Failed to save quotation:", error);
            const errorMessage = error?.message || "An unexpected error occurred.";
            alert(`حدث خطأ أثناء حفظ عرض السعر: ${errorMessage}`);
        } finally {
            setIsSaving(false);
        }
    };
    
    if (loading || !quotation) {
        return <div className="flex justify-center items-center h-full text-dark-text">جاري التحميل...</div>;
    }
    
    if (isEditing) {
        return (
            <QuotationEditorForm 
                quotation={quotation}
                setQuotation={setQuotation}
                onSave={handleSave}
                isSaving={isSaving}
                onCancel={() => isNew ? navigate('/quotations') : setIsEditing(false)}
            />
        )
    }

    return (
        <QuotationViewer 
            quotation={quotation as Quotation}
            onEdit={() => setIsEditing(true)}
        />
    );
};

export default QuotationEditorPage;
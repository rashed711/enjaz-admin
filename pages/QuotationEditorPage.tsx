
import React, { useEffect } from 'react';
import { useParams, useNavigate, useLocation } from 'react-router-dom';
import { Quotation, DocumentItemState, Currency } from '../types';
import { useDocument } from '../hooks/useDocument';
import QuotationViewer from '../components/QuotationViewer';
import QuotationEditorForm from '../components/QuotationEditorForm';

export type QuotationState = Omit<Quotation, 'items'> & { items: DocumentItemState[] };

const QuotationEditorPage: React.FC = () => {
    const { id: idParam, mode } = useParams<{ id: string; mode?: string }>();
    const navigate = useNavigate();
    const location = useLocation();

    const preloadedData = location.state?.preloadedData as QuotationState | undefined;

    const {
        document: quotation,
        setDocument: setQuotation,
        loading,
        isSaving,
        saveError,
        handleSave,
    } = useDocument<QuotationState>({
        documentType: 'quotation',
        id: idParam,
        preloadedData,
    });
    
    const isNew = idParam === 'new';
    const isEditMode = mode === 'edit' || isNew;

    if (loading || !quotation) {
        return <div className="flex justify-center items-center h-full text-dark-text">جاري التحميل...</div>;
    }
    
    if (isEditMode) {
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
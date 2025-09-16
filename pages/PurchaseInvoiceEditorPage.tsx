
import React, { useEffect } from 'react';
import { useParams, useNavigate, useLocation } from 'react-router-dom';
import { PurchaseInvoice, DocumentItemState, Currency } from '../types';
import { useDocument } from '../hooks/useDocument';
import PurchaseInvoiceViewer from '../components/PurchaseInvoiceViewer';
import PurchaseInvoiceEditorForm from '../components/PurchaseInvoiceEditorForm';

export type PurchaseInvoiceState = Omit<PurchaseInvoice, 'items'> & { items: DocumentItemState[] };

const PurchaseInvoiceEditorPage: React.FC = () => {
    const { id: idParam, mode } = useParams<{ id: string; mode?: string }>();
    const navigate = useNavigate();
    const location = useLocation();

    const preloadedData = location.state?.preloadedData as PurchaseInvoiceState | undefined;

    const {
        document: invoice,
        setDocument: setInvoice,
        loading,
        isSaving,
        saveError,
        handleSave,
    } = useDocument<PurchaseInvoiceState>({
        documentType: 'purchase_invoice',
        id: idParam,
        preloadedData,
    });

    const isNew = idParam === 'new';
    const isEditMode = mode === 'edit' || isNew;

    if (loading || !invoice) {
        return <div className="flex justify-center items-center h-full text-dark-text">جاري التحميل...</div>;
    }
    
    if (isEditMode) {
        return (
            <PurchaseInvoiceEditorForm 
                invoice={invoice}
                setInvoice={setInvoice}
                onSave={handleSave}
                isSaving={isSaving}
                onCancel={() => isNew ? navigate('/purchase-invoices') : navigate(`/purchase-invoices/${invoice.id}/view`)}
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
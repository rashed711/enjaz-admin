
import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { PurchaseInvoice, DocumentItemState } from '../types';
import { useDocument } from '../hooks/useDocument';
import PurchaseInvoiceViewer from '../components/PurchaseInvoiceViewer';
import PurchaseInvoiceEditorForm from '../components/PurchaseInvoiceEditorForm';

export type PurchaseInvoiceState = Omit<PurchaseInvoice, 'items'> & { items: DocumentItemState[] };

const PurchaseInvoiceEditorPage: React.FC = () => {
    const { id: idParam, mode } = useParams<{ id: string; mode?: string }>();
    const navigate = useNavigate();

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
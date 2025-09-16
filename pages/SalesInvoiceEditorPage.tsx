
import React, { useEffect } from 'react';
import { useParams, useNavigate, useLocation } from 'react-router-dom';
import { SalesInvoice, DocumentItemState, Currency } from '../types';
import { useDocument } from '../hooks/useDocument';
import SalesInvoiceViewer from '../components/SalesInvoiceViewer';
import SalesInvoiceEditorForm from '../components/SalesInvoiceEditorForm';

export type SalesInvoiceState = Omit<SalesInvoice, 'items'> & { items: DocumentItemState[] };

const SalesInvoiceEditorPage: React.FC = () => {
    const { id: idParam, mode } = useParams<{ id: string; mode?: string }>();
    const navigate = useNavigate();
    const location = useLocation();

    const preloadedData = location.state?.preloadedData as SalesInvoiceState | undefined;

    const {
        document: invoice,
        setDocument: setInvoice,
        loading,
        isSaving,
        saveError,
        handleSave,
    } = useDocument<SalesInvoiceState>({
        documentType: 'sales_invoice',
        id: idParam,
        preloadedData,
    });

    const isNew = idParam === 'new';
    const isEditMode = mode === 'edit' || isNew;

    if (loading || !invoice) {
        return <div className="flex justify-center items-center h-full">جاري التحميل...</div>;
    }
    
    if (isEditMode) {
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
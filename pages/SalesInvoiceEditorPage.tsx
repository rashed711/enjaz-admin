
import React from 'react';
import { SalesInvoice, DocumentItemState } from '../types';
import SalesInvoiceViewer from '../components/SalesInvoiceViewer';
import SalesInvoiceEditorForm from '../components/SalesInvoiceEditorForm';
import DocumentPage from './DocumentPage';

export type SalesInvoiceState = Omit<SalesInvoice, 'items'> & { items: DocumentItemState[] };

const SalesInvoiceEditorPage: React.FC = () => {
    return (
        <DocumentPage<SalesInvoiceState, SalesInvoice>
            documentType="sales_invoice"
            EditorFormComponent={SalesInvoiceEditorForm}
            ViewerComponent={SalesInvoiceViewer}
            listPath="/sales-invoices"
        />
    );
};

export default SalesInvoiceEditorPage;
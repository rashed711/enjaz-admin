
import React from 'react';
import { PurchaseInvoice, DocumentItemState, Currency } from '../types';
import PurchaseInvoiceViewer from '../components/PurchaseInvoiceViewer';
import PurchaseInvoiceEditorForm from '../components/PurchaseInvoiceEditorForm';
import DocumentPage from './DocumentPage';

export type PurchaseInvoiceState = Omit<PurchaseInvoice, 'items'> & { items: DocumentItemState[] };

const PurchaseInvoiceEditorPage: React.FC = () => {
    return (
        <DocumentPage<PurchaseInvoiceState, PurchaseInvoice>
            documentType="purchase_invoice"
            EditorFormComponent={PurchaseInvoiceEditorForm}
            ViewerComponent={PurchaseInvoiceViewer}
            listPath="/purchase-invoices"
        />
    );
};

export default PurchaseInvoiceEditorPage;
import React from 'react';
import { PurchaseInvoice as PurchaseInvoiceType, DocumentItemState } from '../types';
import DocumentPage from './DocumentPage';
import PurchaseInvoiceEditorForm from '../components/PurchaseInvoiceEditorForm';
import PurchaseInvoiceViewer from '../components/PurchaseInvoiceViewer';

// Define the state type for the editor, which includes items with a temporary ID
export type PurchaseInvoiceState = Omit<PurchaseInvoiceType, 'items'> & { items: DocumentItemState[] };

const PurchaseInvoiceEditorPage: React.FC = () => {
    return (
        <DocumentPage<PurchaseInvoiceState, PurchaseInvoiceType>
            documentType="purchase_invoice"
            EditorFormComponent={PurchaseInvoiceEditorForm}
            ViewerComponent={PurchaseInvoiceViewer} // تم التحديث لاستخدام المكون الصحيح مع الأزرار
            listPath="/purchase-invoices"
        />
    );
};

export default PurchaseInvoiceEditorPage;
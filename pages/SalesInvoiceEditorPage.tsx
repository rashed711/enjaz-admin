import React from 'react';
import { SalesInvoice, DocumentItemState } from '../types';
import SalesInvoiceEditorForm from '../components/SalesInvoiceEditorForm';
import DocumentPage from './DocumentPage';
import SalesInvoiceViewer from '../components/SalesInvoiceViewer';

// Define the state type for the editor, which includes items with a temporary ID
export type SalesInvoiceState = Omit<SalesInvoice, 'items'> & { items: DocumentItemState[] };

const SalesInvoiceEditorPage: React.FC = () => {
    return (
        <DocumentPage<SalesInvoiceState, SalesInvoice>
            documentType="sales_invoice"
            EditorFormComponent={SalesInvoiceEditorForm}
            ViewerComponent={SalesInvoiceViewer} // تم التحديث لاستخدام المكون الجديد مع الأزرار
            listPath="/sales-invoices"
        />
    );
};

export default SalesInvoiceEditorPage;
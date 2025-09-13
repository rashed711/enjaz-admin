

import React, { useState } from 'react';
import { PurchaseInvoice } from '../types';
import PurchaseInvoiceComponent from './PurchaseInvoice';
import DocumentViewerLayout from './DocumentViewerLayout';

interface PurchaseInvoiceViewerProps {
    invoice: PurchaseInvoice;
}

const PurchaseInvoiceViewer: React.FC<PurchaseInvoiceViewerProps> = ({ invoice }) => {
    const [isProcessing, setIsProcessing] = useState(false);

    return (
        <DocumentViewerLayout
            backPath="/purchase-invoices"
            document={invoice}
            pdfElementId="invoice-pdf"
            isProcessing={isProcessing}
            setIsProcessing={setIsProcessing}
        >
            <PurchaseInvoiceComponent invoice={invoice} />
        </DocumentViewerLayout>
    );
};

export default PurchaseInvoiceViewer;
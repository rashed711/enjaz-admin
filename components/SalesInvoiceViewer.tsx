

import React, { useState } from 'react';
import { SalesInvoice } from '../types';
import SalesInvoiceComponent from './SalesInvoice';
import DocumentViewerLayout from './DocumentViewerLayout';


interface SalesInvoiceViewerProps {
    invoice: SalesInvoice;
}

const SalesInvoiceViewer: React.FC<SalesInvoiceViewerProps> = ({ invoice }) => {
    const [isProcessing, setIsProcessing] = useState(false);

    return (
       <DocumentViewerLayout
            backPath="/sales-invoices"
            document={invoice}
            pdfElementId="sales-invoice-pdf"
            isProcessing={isProcessing}
            setIsProcessing={setIsProcessing}
       >
            <SalesInvoiceComponent invoice={invoice} />
       </DocumentViewerLayout>
    );
};

export default SalesInvoiceViewer;
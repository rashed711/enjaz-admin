
import React from 'react';
import { Quotation, DocumentItemState, Currency } from '../types';
import QuotationViewer from '../components/QuotationViewer';
import QuotationEditorForm from '../components/QuotationEditorForm';
import DocumentPage from './DocumentPage';

export type QuotationState = Omit<Quotation, 'items'> & { items: DocumentItemState[] };

const QuotationEditorPage: React.FC = () => {
    return (
        <DocumentPage<QuotationState, Quotation>
            documentType="quotation"
            EditorFormComponent={QuotationEditorForm}
            ViewerComponent={QuotationViewer}
            listPath="/quotations"
        />
    );
};

export default QuotationEditorPage;
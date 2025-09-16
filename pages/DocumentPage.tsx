import React from 'react';
import { useParams, useNavigate, useLocation } from 'react-router-dom';
import { useDocument } from '../hooks/useDocument';
import { DocumentItemState } from '../types';
import Spinner from '../components/Spinner';

// Define a generic state that all document states must adhere to.
export interface GenericDocumentState {
    id: number | string; // 'new' or a number
    items: DocumentItemState[];
}

// Props for the generic Editor Form component
export interface EditorFormProps<TState> {
    document: TState;
    setDocument: React.Dispatch<React.SetStateAction<TState | null>>;
    onSave: () => Promise<void>;
    isSaving: boolean;
    onCancel: () => void;
    saveError: string | null;
}

// Props for the generic Viewer component
export interface ViewerProps<TDoc> {
    document: TDoc;
}

// Props for the generic DocumentPage component
interface DocumentPageProps<TState extends GenericDocumentState, TDoc> {
    documentType: 'quotation' | 'purchase_invoice' | 'sales_invoice';
    EditorFormComponent: React.ComponentType<EditorFormProps<TState>>;
    ViewerComponent: React.ComponentType<ViewerProps<TDoc>>;
    listPath: string;
}

/**
 * @ generic DocumentPage component
 * @param {documentType} - The type of document (quotation, purchase_invoice, sales_invoice).
 * @param {EditorFormComponent} - The component used to edit the document.
 * @param {ViewerComponent} - The component used to view the document.
 */
const DocumentPage = <TState extends GenericDocumentState, TDoc>({
    documentType,
    EditorFormComponent,
    ViewerComponent,
    listPath,
}: DocumentPageProps<TState, TDoc>) => {
    const { id: idParam, mode } = useParams<{ id: string; mode?: string }>();
    const navigate = useNavigate();
    const location = useLocation();

    const preloadedData = location.state?.preloadedData as TState | undefined;

    const {
        document,
        setDocument,
        loading,
        isSaving,
        saveError,
        handleSave,
    } = useDocument<TState>({
        documentType: documentType,
        id: idParam,
        preloadedData,
    });

    const isNew = idParam === 'new';
    const isEditMode = mode === 'edit' || isNew;

    if (loading || !document) {
        return <div className="flex justify-center items-center h-full"><Spinner /></div>;
    }

    if (isEditMode) {
        return (
            <EditorFormComponent
                document={document}
                setDocument={setDocument}
                onSave={handleSave}
                isSaving={isSaving}
                onCancel={() => isNew ? navigate(listPath) : navigate(`${listPath}/${document.id}/view`)}
                saveError={saveError}
            />
        );
    }

    return (
        <ViewerComponent
            document={document as unknown as TDoc}
        />
    );
};

export default DocumentPage;
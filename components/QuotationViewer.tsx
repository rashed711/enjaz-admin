

import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { Quotation, SalesInvoiceStatus, PermissionModule, PermissionAction, SalesInvoice, DocumentItemState } from '../types';
import QuotationComponent from './Quotation';
import DocumentViewerLayout from './DocumentViewerLayout';
import { useAuth } from '../hooks/useAuth';
import { usePermissions } from '../hooks/usePermissions';
import { useProducts } from '../contexts/ProductContext';
import { supabase } from '../services/supabaseClient';
import ArrowRightCircleIcon from './icons/ArrowRightCircleIcon';
import PencilIcon from './icons/PencilIcon';
import TrashIcon from './icons/TrashIcon';
import DeleteConfirmationModal from './DeleteConfirmationModal';
import { generateDocumentNumber } from '../utils/numbering';

interface QuotationViewerProps {
    document: Quotation;
}

const QuotationViewer: React.FC<QuotationViewerProps> = ({ document: quotation }) => {
    const navigate = useNavigate();
    const { currentUser } = useAuth();
    const permissions = usePermissions();
    const { fetchProducts } = useProducts();
    const [isProcessing, setIsProcessing] = useState(false);
    const [quotationToDelete, setQuotationToDelete] = useState<Quotation | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [deleteError, setDeleteError] = useState<string | null>(null);

    const canEdit = permissions.can(PermissionModule.QUOTATIONS, PermissionAction.EDIT_OWN, quotation.createdBy);
    const canDelete = permissions.can(PermissionModule.QUOTATIONS, PermissionAction.DELETE_OWN, quotation.createdBy);

    const handleConvertToInvoice = async () => {
        if (!quotation || !currentUser) return;
        setIsProcessing(true);
    
        try {
            const { data, error } = await supabase.rpc('convert_quotation_to_invoice', {
                quotation_id_to_convert: quotation.id,
                user_id: currentUser.id
            });
    
            if (error) throw error;
    
            if (data.success) {
                // Navigate to the newly created invoice
                navigate(`/sales-invoices/${data.invoice_id}/view`);
            } else {
                // Handle cases where the invoice already exists or other server-side errors
                alert(data.message);
                if (data.invoice_id) {
                    navigate(`/sales-invoices/${data.invoice_id}/view`);
                }
                return;
            }
    
        } catch (error: any) {
            console.error("Error converting to invoice:", error.message);
            alert(`فشل تحويل عرض السعر إلى فاتورة: ${error.message}`);
        } finally {
            setIsProcessing(false);
        }
    };
    
    const handleConfirmDelete = async () => {
        if (!quotationToDelete || !quotationToDelete.id) return;
    
        setIsDeleting(true);
        setDeleteError(null);
    
        try {
            const { error: itemsError } = await supabase
                .from('quotation_items')
                .delete()
                .eq('quotation_id', quotationToDelete.id);
    
            if (itemsError) throw itemsError;
    
            const { error: quotationError } = await supabase
                .from('quotations')
                .delete()
                .eq('id', quotationToDelete.id);
    
            if (quotationError) throw quotationError;
    
            await fetchProducts();
            navigate('/quotations');
    
        } catch (error: any) {
            console.error("Error deleting quotation:", error.message);
            setDeleteError(error.message || "فشل حذف عرض السعر. يرجى المحاولة مرة أخرى.");
        } finally {
            setIsDeleting(false);
        }
    };

    const iconButtonClasses = "p-2 rounded-full disabled:opacity-50 disabled:cursor-not-allowed transition-colors";

    const customActions = (
        <>
            {canEdit && (
                <Link to={`/quotations/${quotation.id}/edit`} title="تعديل" className={`${iconButtonClasses} bg-indigo-100 text-indigo-600 hover:bg-indigo-200`}>
                    <PencilIcon className="w-6 h-6" />
                </Link>
            )}
            {canDelete && (
                <button onClick={() => setQuotationToDelete(quotation)} title="حذف" className={`${iconButtonClasses} bg-red-100 text-red-600 hover:bg-red-200`}>
                    <TrashIcon className="w-6 h-6" />
                </button>
            )}
            <button onClick={handleConvertToInvoice} title="تحويل إلى فاتورة" className={`${iconButtonClasses} bg-blue-100 text-blue-600 hover:bg-blue-200`} disabled={isProcessing}>
                <ArrowRightCircleIcon className="w-6 h-6" />
            </button>
        </>
    );

    return (
        <>
            <DeleteConfirmationModal
                isOpen={!!quotationToDelete}
                onClose={() => {
                    setQuotationToDelete(null);
                    setDeleteError(null);
                }}
                onConfirm={handleConfirmDelete}
                title="تأكيد الحذف"
                message={
                    <>
                        هل أنت متأكد أنك تريد حذف عرض السعر رقم <span className="font-bold text-text-primary">{quotationToDelete?.quotationNumber}</span>؟ سيتم حذف جميع البنود المرتبطة به.
                    </>
                }
                isProcessing={isDeleting}
                error={deleteError}
            />
            <DocumentViewerLayout
                backPath="/quotations"
                document={quotation}
                pdfElementId="quotation-pdf"
                isProcessing={isProcessing}
                setIsProcessing={setIsProcessing}
                customActions={customActions}
            >
                <QuotationComponent quotation={quotation} />
            </DocumentViewerLayout>
        </>
    );
};

export default QuotationViewer;
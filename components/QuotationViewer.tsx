

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
import Spinner from './Spinner';
import DeleteConfirmationModal from './DeleteConfirmationModal';

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

    // Guard clause to prevent rendering with a null document or while permissions are loading.
    if (!quotation || !permissions) {
        return <div className="flex justify-center items-center p-20"><Spinner /></div>;
    }

    // Defensively check for createdBy before checking ownership-based permissions.
    // This prevents a crash if the can() function doesn't handle a null ownerId gracefully.
    const canEdit = quotation.createdBy ? permissions.can(PermissionModule.QUOTATIONS, PermissionAction.EDIT_OWN, quotation.createdBy) : false;
    const canDelete = quotation.createdBy ? permissions.can(PermissionModule.QUOTATIONS, PermissionAction.DELETE_OWN, quotation.createdBy) : false;

    const handleConvertToInvoice = () => {
        if (!quotation || !currentUser) return;
        // This function now prepares data and navigates to the editor,
        // instead of calling a server-side RPC. This gives the user a chance to review
        // and ensures all data (like tax settings) is correctly passed.
        const newInvoiceData = {
            // id is undefined for a new document
            clientName: quotation.clientName,
            company: quotation.company,
            project: quotation.project,
            date: new Date().toISOString().split('T')[0], // Default to today's date
            currency: quotation.currency,
            status: SalesInvoiceStatus.DRAFT,
            taxIncluded: quotation.taxIncluded, // This is the fix
            items: (quotation.items || []).map(item => ({
                ...item,
                id: -Date.now() + Math.random(), // Assign a new temporary negative ID for the editor
            })),
            totalAmount: 0, // Will be recalculated in the editor
            createdBy: currentUser.id,
            quotationId: quotation.id,
            quotationNumber: quotation.quotationNumber, // Pass the quote number for reference
        };

        navigate('/sales-invoices/new', { state: { preloadedData: newInvoiceData } });
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
    
            // انتقل أولاً لمنع الصفحة الحالية من محاولة إعادة جلب المستند المحذوف
            navigate('/quotations');
            await fetchProducts();
    
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
            <button onClick={handleConvertToInvoice} title="تحويل إلى فاتورة" className={`${iconButtonClasses} bg-blue-100 text-blue-600 hover:bg-blue-200`}>
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
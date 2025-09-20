import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { SalesInvoice, PermissionModule, PermissionAction } from '../types';
import SalesInvoiceComponent from './SalesInvoice';
import DocumentViewerLayout from './DocumentViewerLayout';
import { usePermissions } from '../hooks/usePermissions';
import { supabase } from '../services/supabaseClient';
import DeleteConfirmationModal from './DeleteConfirmationModal';
import PencilIcon from './icons/PencilIcon';
import TrashIcon from './icons/TrashIcon';
import Spinner from './Spinner';

interface SalesInvoiceViewerProps {
    document: SalesInvoice;
}

const SalesInvoiceViewer: React.FC<SalesInvoiceViewerProps> = ({ document: invoice }) => {
    const navigate = useNavigate();
    const permissions = usePermissions();
    const [isProcessing, setIsProcessing] = useState(false);
    const [invoiceToDelete, setInvoiceToDelete] = useState<SalesInvoice | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [deleteError, setDeleteError] = useState<string | null>(null);

    // Guard clause to prevent rendering with a null document or while permissions are loading.
    if (!invoice || !permissions) {
        return <div className="flex justify-center items-center p-20"><Spinner /></div>;
    }

    // Defensively check for createdBy before checking ownership-based permissions.
    // This prevents a crash if the can() function doesn't handle a null ownerId gracefully.
    const canEdit = invoice.createdBy ? permissions.can(PermissionModule.SALES_INVOICES, PermissionAction.EDIT_OWN, invoice.createdBy) : false;
    const canDelete = invoice.createdBy ? permissions.can(PermissionModule.SALES_INVOICES, PermissionAction.DELETE_OWN, invoice.createdBy) : false;

    const handleConfirmDelete = async () => {
        if (!invoiceToDelete || !invoiceToDelete.id) return;
    
        setIsDeleting(true);
        setDeleteError(null);
    
        try {
            // Delete associated journal entries first
            const { error: journalError } = await supabase.from('journal_entries').delete().like('description', `فاتورة مبيعات رقم ${invoiceToDelete.invoiceNumber}%`);
            if (journalError) {
                throw new Error(`فشل حذف القيود المحاسبية المرتبطة: ${journalError.message}`);
            }

            // Delete invoice items then the invoice itself
            await supabase.from('sales_invoice_items').delete().eq('invoice_id', invoiceToDelete.id);
            await supabase.from('sales_invoices').delete().eq('id', invoiceToDelete.id);

            navigate('/sales-invoices');
        } catch (error: any) {
            console.error("Error deleting sales invoice:", error.message);
            setDeleteError(error.message || "فشل حذف فاتورة المبيعات.");
        } finally {
            setIsDeleting(false);
        }
    };

    const iconButtonClasses = "p-2 rounded-full disabled:opacity-50 disabled:cursor-not-allowed transition-colors";

    const customActions = (
        <>
            {canEdit && <Link to={`/sales-invoices/${invoice.id}/edit`} title="تعديل" className={`${iconButtonClasses} bg-indigo-100 text-indigo-600 hover:bg-indigo-200`}><PencilIcon className="w-6 h-6" /></Link>}
            {canDelete && <button onClick={() => setInvoiceToDelete(invoice)} title="حذف" className={`${iconButtonClasses} bg-red-100 text-red-600 hover:bg-red-200`}><TrashIcon className="w-6 h-6" /></button>}
        </>
    );

    return (
        <>
            <DeleteConfirmationModal isOpen={!!invoiceToDelete} onClose={() => { setInvoiceToDelete(null); setDeleteError(null); }} onConfirm={handleConfirmDelete} title="تأكيد الحذف" message={<>هل أنت متأكد أنك تريد حذف الفاتورة رقم <span className="font-bold text-text-primary">{invoiceToDelete?.invoiceNumber}</span>؟ سيتم حذف القيود المحاسبية المرتبطة بها.</>} isProcessing={isDeleting} error={deleteError} />
            <DocumentViewerLayout
                backPath="/sales-invoices"
                document={invoice}
                pdfElementId="sales-invoice-pdf" // Assuming SalesInvoiceComponent has this ID
                isProcessing={isProcessing || isDeleting}
                setIsProcessing={setIsProcessing}
                customActions={customActions}
            >
                <SalesInvoiceComponent invoice={invoice} />
            </DocumentViewerLayout>
        </>
    );
};

export default SalesInvoiceViewer;
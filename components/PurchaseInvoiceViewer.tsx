import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { PurchaseInvoice, PermissionModule, PermissionAction } from '../types';
import PurchaseInvoiceComponent from './PurchaseInvoice';
import DocumentViewerLayout from './DocumentViewerLayout';
import { usePermissions } from '../hooks/usePermissions';
import { useProducts } from '../contexts/ProductContext';
import { supabase } from '../services/supabaseClient';
import DeleteConfirmationModal from './DeleteConfirmationModal';
import PencilIcon from './icons/PencilIcon';
import TrashIcon from './icons/TrashIcon';

interface PurchaseInvoiceViewerProps {
    invoice: PurchaseInvoice;
}

const PurchaseInvoiceViewer: React.FC<PurchaseInvoiceViewerProps> = ({ invoice }) => {
    const navigate = useNavigate();
    const permissions = usePermissions();
    const { fetchProducts } = useProducts();
    const [isProcessing, setIsProcessing] = useState(false);
    const [invoiceToDelete, setInvoiceToDelete] = useState<PurchaseInvoice | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [deleteError, setDeleteError] = useState<string | null>(null);

    const canEdit = permissions.can(PermissionModule.PURCHASE_INVOICES, PermissionAction.EDIT_OWN, invoice.createdBy);
    const canDelete = permissions.can(PermissionModule.PURCHASE_INVOICES, PermissionAction.DELETE_OWN, invoice.createdBy);

    const handleConfirmDelete = async () => {
        if (!invoiceToDelete || !invoiceToDelete.id) return;
    
        setIsDeleting(true);
        setDeleteError(null);
    
        try {
            // New: Delete associated journal entries first
            const { error: journalError } = await supabase.from('journal_entries').delete().like('description', `فاتورة مشتريات رقم ${invoiceToDelete.invoiceNumber}%`);
            if (journalError) {
                throw new Error(`فشل حذف القيود المحاسبية المرتبطة: ${journalError.message}`);
            }

            await supabase.from('purchase_invoice_items').delete().eq('invoice_id', invoiceToDelete.id);
            await supabase.from('purchase_invoices').delete().eq('id', invoiceToDelete.id);
            await fetchProducts();
            navigate('/purchase-invoices');
        } catch (error: any) {
            console.error("Error deleting purchase invoice:", error.message);
            setDeleteError(error.message || "فشل حذف فاتورة المشتريات.");
        } finally {
            setIsDeleting(false);
        }
    };

    const iconButtonClasses = "p-2 rounded-full disabled:opacity-50 disabled:cursor-not-allowed transition-colors";

    const customActions = (
        <>
            {canEdit && (
                <Link to={`/purchase-invoices/${invoice.id}/edit`} title="تعديل" className={`${iconButtonClasses} bg-indigo-100 text-indigo-600 hover:bg-indigo-200`}>
                    <PencilIcon className="w-6 h-6" />
                </Link>
            )}
            {canDelete && (
                <button onClick={() => setInvoiceToDelete(invoice)} title="حذف" className={`${iconButtonClasses} bg-red-100 text-red-600 hover:bg-red-200`}>
                    <TrashIcon className="w-6 h-6" />
                </button>
            )}
        </>
    );

    return (
        <>
            <DeleteConfirmationModal
                isOpen={!!invoiceToDelete}
                onClose={() => { setInvoiceToDelete(null); setDeleteError(null); }}
                onConfirm={handleConfirmDelete}
                title="تأكيد الحذف"
                message={<>هل أنت متأكد أنك تريد حذف الفاتورة رقم <span className="font-bold text-text-primary">{invoiceToDelete?.invoiceNumber}</span>؟ سيتم حذف القيود المحاسبية المرتبطة بها.</>}
                isProcessing={isDeleting}
                error={deleteError}
            />
            <DocumentViewerLayout
                backPath="/purchase-invoices"
                document={invoice}
                pdfElementId="invoice-pdf"
                isProcessing={isProcessing || isDeleting}
                setIsProcessing={setIsProcessing}
                customActions={customActions}
            >
                <PurchaseInvoiceComponent invoice={invoice} />
            </DocumentViewerLayout>
        </>
    );
};

export default PurchaseInvoiceViewer;
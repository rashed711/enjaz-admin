import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { SalesInvoice, PermissionModule, PermissionAction } from '../types';
import SalesInvoiceComponent from './SalesInvoice';
import DocumentViewerLayout from './DocumentViewerLayout';
import { usePermissions } from '../hooks/usePermissions';
import { useProducts } from '../contexts/ProductContext';
import { supabase } from '../services/supabaseClient';
import DeleteConfirmationModal from './DeleteConfirmationModal';
import PencilIcon from './icons/PencilIcon';
import TrashIcon from './icons/TrashIcon';


interface SalesInvoiceViewerProps {
    invoice: SalesInvoice;
}

const SalesInvoiceViewer: React.FC<SalesInvoiceViewerProps> = ({ invoice }) => {
    const navigate = useNavigate();
    const permissions = usePermissions();
    const { fetchProducts } = useProducts();
    const [isProcessing, setIsProcessing] = useState(false);
    const [invoiceToDelete, setInvoiceToDelete] = useState<SalesInvoice | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [deleteError, setDeleteError] = useState<string | null>(null);

    const canEdit = permissions.can(PermissionModule.SALES_INVOICES, PermissionAction.EDIT_OWN, invoice.createdBy);
    const canDelete = permissions.can(PermissionModule.SALES_INVOICES, PermissionAction.DELETE_OWN, invoice.createdBy);

    const handleConfirmDelete = async () => {
        if (!invoiceToDelete || !invoiceToDelete.id) return;
    
        setIsDeleting(true);
        setDeleteError(null);
    
        try {
            await supabase.from('sales_invoice_items').delete().eq('invoice_id', invoiceToDelete.id);
            await supabase.from('sales_invoices').delete().eq('id', invoiceToDelete.id);
            await fetchProducts();
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
            {canEdit && (
                <Link to={`/sales-invoices/${invoice.id}/edit`} title="تعديل" className={`${iconButtonClasses} bg-indigo-100 text-indigo-600 hover:bg-indigo-200`}>
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
                message={<>هل أنت متأكد أنك تريد حذف الفاتورة رقم <span className="font-bold text-text-primary">{invoiceToDelete?.invoiceNumber}</span>؟</>}
                isProcessing={isDeleting}
                error={deleteError}
            />
            <DocumentViewerLayout
                backPath="/sales-invoices"
                document={invoice}
                pdfElementId="sales-invoice-pdf"
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
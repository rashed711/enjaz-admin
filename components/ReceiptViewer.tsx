import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { Receipt, PermissionModule, PermissionAction } from '../types';
import ReceiptComponent from './ReceiptComponent';
import DocumentViewerLayout from './DocumentViewerLayout';
import { usePermissions } from '../hooks/usePermissions';
import { supabase } from '../services/supabaseClient';
import PencilIcon from './icons/PencilIcon';
import TrashIcon from './icons/TrashIcon';
import DeleteConfirmationModal from './DeleteConfirmationModal';

interface ReceiptViewerProps {
    receipt: Receipt;
}

const ReceiptViewer: React.FC<ReceiptViewerProps> = ({ receipt }) => {
    const navigate = useNavigate();
    const permissions = usePermissions();
    const [isProcessing, setIsProcessing] = useState(false);
    const [receiptToDelete, setReceiptToDelete] = useState<Receipt | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [deleteError, setDeleteError] = useState<string | null>(null);

    const canEdit = permissions.can(PermissionModule.RECEIPTS, PermissionAction.EDIT_OWN, receipt.createdBy);
    const canDelete = permissions.can(PermissionModule.RECEIPTS, PermissionAction.DELETE_OWN, receipt.createdBy);

    const handleConfirmDelete = async () => {
        if (!receiptToDelete || !receiptToDelete.id) return;
    
        setIsDeleting(true);
        setDeleteError(null);
    
        try {
            // 1. Delete journal entries associated with the receipt
            const { error: journalError } = await supabase
                .from('journal_entries')
                .delete()
                .like('description', `سند قبض رقم ${receiptToDelete.id}:%`);
            
            if (journalError) throw new Error(`فشل حذف القيود المرتبطة: ${journalError.message}`);
    
            // 2. Delete the receipt itself
            const { error: receiptError } = await supabase
                .from('receipts')
                .delete()
                .eq('id', receiptToDelete.id);
    
            if (receiptError) throw receiptError;
    
            navigate('/accounts/receipts');
    
        } catch (error: any) {
            console.error("Error deleting receipt:", error.message);
            setDeleteError(error.message || "فشل حذف السند. قد يكون مرتبطًا بعمليات أخرى.");
        } finally {
            setIsDeleting(false);
        }
    };

    const iconButtonClasses = "p-2 rounded-full disabled:opacity-50 disabled:cursor-not-allowed transition-colors";

    const customActions = (
        <>
            {canEdit && (
                <Link to={`/accounts/receipts/${receipt.id}/edit`} title="تعديل" className={`${iconButtonClasses} bg-indigo-100 text-indigo-600 hover:bg-indigo-200`}>
                    <PencilIcon className="w-6 h-6" />
                </Link>
            )}
            {canDelete && (
                <button onClick={() => setReceiptToDelete(receipt)} title="حذف" className={`${iconButtonClasses} bg-red-100 text-red-600 hover:bg-red-200`}>
                    <TrashIcon className="w-6 h-6" />
                </button>
            )}
        </>
    );

    return (
        <>
            <DeleteConfirmationModal isOpen={!!receiptToDelete} onClose={() => { setReceiptToDelete(null); setDeleteError(null); }} onConfirm={handleConfirmDelete} title="تأكيد الحذف" message={<>هل أنت متأكد أنك تريد حذف سند القبض رقم <span className="font-bold text-text-primary">{receiptToDelete?.id}</span>؟ سيتم حذف القيود المحاسبية المرتبطة به.</>} isProcessing={isDeleting} error={deleteError} />
            <DocumentViewerLayout backPath="/accounts/receipts" document={receipt} pdfElementId="receipt-pdf" isProcessing={isProcessing} setIsProcessing={setIsProcessing} customActions={customActions}>
                <ReceiptComponent receipt={receipt} />
            </DocumentViewerLayout>
        </>
    );
};

export default ReceiptViewer;
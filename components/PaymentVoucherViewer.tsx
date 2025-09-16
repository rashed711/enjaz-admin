import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { PaymentVoucher, PermissionModule, PermissionAction } from '../types';
import PaymentVoucherComponent from './PaymentVoucherComponent';
import DocumentViewerLayout from './DocumentViewerLayout';
import { usePermissions } from '../hooks/usePermissions';
import { supabase } from '../services/supabaseClient';
import PencilIcon from './icons/PencilIcon';
import TrashIcon from './icons/TrashIcon';
import DeleteConfirmationModal from './DeleteConfirmationModal';

interface PaymentVoucherViewerProps {
    voucher: PaymentVoucher;
}

const PaymentVoucherViewer: React.FC<PaymentVoucherViewerProps> = ({ voucher }) => {
    const navigate = useNavigate();
    const permissions = usePermissions();
    const [isProcessing, setIsProcessing] = useState(false);
    const [voucherToDelete, setVoucherToDelete] = useState<PaymentVoucher | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [deleteError, setDeleteError] = useState<string | null>(null);

    const canEdit = permissions.can(PermissionModule.PAYMENT_VOUCHERS, PermissionAction.EDIT_OWN, voucher.createdBy);
    const canDelete = permissions.can(PermissionModule.PAYMENT_VOUCHERS, PermissionAction.DELETE_OWN, voucher.createdBy);

    const handleConfirmDelete = async () => {
        if (!voucherToDelete || !voucherToDelete.id) return;
    
        setIsDeleting(true);
        setDeleteError(null);
    
        try {
            // 1. Delete journal entries associated with the voucher
            const { error: journalError } = await supabase
                .from('journal_entries')
                .delete()
                .like('description', `سند صرف رقم ${voucherToDelete.id}:%`);
            
            if (journalError) throw new Error(`فشل حذف القيود المرتبطة: ${journalError.message}`);
    
            // 2. Delete the voucher itself
            const { error: voucherError } = await supabase
                .from('payment_vouchers')
                .delete()
                .eq('id', voucherToDelete.id);
    
            if (voucherError) throw voucherError;
    
            navigate('/accounts/payment-vouchers');
    
        } catch (error: any) {
            console.error("Error deleting payment voucher:", error.message);
            setDeleteError(error.message || "فشل حذف السند. قد يكون مرتبطًا بعمليات أخرى.");
        } finally {
            setIsDeleting(false);
        }
    };

    const iconButtonClasses = "p-2 rounded-full disabled:opacity-50 disabled:cursor-not-allowed transition-colors";

    const customActions = (
        <>
            {canEdit && (
                <Link to={`/accounts/payment-vouchers/${voucher.id}/edit`} title="تعديل" className={`${iconButtonClasses} bg-indigo-100 text-indigo-600 hover:bg-indigo-200`}>
                    <PencilIcon className="w-6 h-6" />
                </Link>
            )}
            {canDelete && (
                <button onClick={() => setVoucherToDelete(voucher)} title="حذف" className={`${iconButtonClasses} bg-red-100 text-red-600 hover:bg-red-200`}>
                    <TrashIcon className="w-6 h-6" />
                </button>
            )}
        </>
    );

    return (
        <>
            <DeleteConfirmationModal isOpen={!!voucherToDelete} onClose={() => { setVoucherToDelete(null); setDeleteError(null); }} onConfirm={handleConfirmDelete} title="تأكيد الحذف" message={<>هل أنت متأكد أنك تريد حذف سند الصرف رقم <span className="font-bold text-text-primary">{voucherToDelete?.id}</span>؟ سيتم حذف القيود المحاسبية المرتبطة به.</>} isProcessing={isDeleting} error={deleteError} />
            <DocumentViewerLayout backPath="/accounts/payment-vouchers" document={voucher} pdfElementId="payment-voucher-pdf" isProcessing={isProcessing} setIsProcessing={setIsProcessing} customActions={customActions}>
                <PaymentVoucherComponent voucher={voucher} />
            </DocumentViewerLayout>
        </>
    );
};

export default PaymentVoucherViewer;
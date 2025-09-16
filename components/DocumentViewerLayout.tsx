

import React from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { Quotation, PurchaseInvoice, SalesInvoice, Receipt, PaymentVoucher } from '../types';
import Spinner from './Spinner';
import { generatePdfBlob } from '../utils/pdfUtils';
import WhatsappIcon from './icons/WhatsappIcon';

type AnyDocument = Quotation | PurchaseInvoice | SalesInvoice | Receipt | PaymentVoucher;

interface DocumentViewerLayoutProps {
    children: React.ReactNode;
    backPath: string;
    document: AnyDocument;
    pdfElementId: string;
    isProcessing: boolean;
    setIsProcessing: (isProcessing: boolean) => void;
    customActions?: React.ReactNode;
}

const DocumentViewerLayout: React.FC<DocumentViewerLayoutProps> = ({ children, backPath, document, pdfElementId, isProcessing, setIsProcessing, customActions, }) => {
    const navigate = useNavigate();
    const location = useLocation();

    const getDocumentInfo = (doc: AnyDocument): { number: string | number; label: string } => {
        if ('quotationNumber' in doc) {
            return { number: doc.quotationNumber, label: 'عرض سعر' };
        }
        if ('invoiceNumber' in doc) {
            return { number: doc.invoiceNumber, label: 'supplierName' in doc ? 'فاتورة مشتريات' : 'فاتورة مبيعات' };
        }
        // Handle Receipt and PaymentVoucher by checking the URL path
        if ('payment_method' in doc) {
            if (location.pathname.includes('/receipts/')) {
                return { number: doc.id, label: 'سند قبض' };
            }
            if (location.pathname.includes('/payment-vouchers/')) {
                return { number: doc.id, label: 'سند صرف' };
            }
        }
        return { number: doc.id, label: 'مستند' }; // Fallback
    };

    const { number: documentNumber, label: documentTypeLabel } = getDocumentInfo(document);
    const handleShare = async () => {
        setIsProcessing(true);
        const blob = await generatePdfBlob(pdfElementId);
        setIsProcessing(false);

        if (!blob) {
            alert("لا يمكن إنشاء ملف PDF للمشاركة.");
            return;
        }

        const fileName = `${documentTypeLabel.replace(/\s/g, '-')}-${documentNumber}.pdf`;
        const file = new File([blob], fileName, { type: 'application/pdf' });
        const shareData = {
            files: [file],
            title: `${documentTypeLabel} ${documentNumber}`,
            text: `مرفق ${documentTypeLabel} رقم ${documentNumber}.`,
        };

        if (navigator.share && navigator.canShare?.({ files: [file] })) {
            try {
                await navigator.share(shareData);
            } catch (error) {
                console.error('Error sharing:', error);
            }
        } else {
            const message = encodeURIComponent(shareData.text);
            window.open(`https://wa.me/?text=${message}`, '_blank');
        }
    };

    const buttonClasses = "w-full sm:w-auto px-5 py-2 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2";

    return (
        <>
            <div className="flex flex-col-reverse sm:flex-row sm:justify-between items-center gap-3 mb-6">
                <button onClick={() => navigate(backPath)} className={`bg-gray-200 hover:bg-gray-300 text-gray-800 focus:ring-gray-500 ${buttonClasses}`} disabled={isProcessing}>
                    &larr; العودة
                </button>
                
                <div className="flex justify-end items-center gap-2 bg-slate-100 p-2 rounded-lg border border-border w-full sm:w-auto">
                    {customActions}
                    <div className="w-px h-6 bg-border mx-1" />
                    <button onClick={handleShare} className="p-2 bg-green-100 text-green-600 hover:bg-green-200 rounded-full transition-colors" disabled={isProcessing} title="مشاركة عبر واتساب">
                        <WhatsappIcon className="w-6 h-6" />
                    </button>
                </div>
            </div>
            {children}
        </>
    );
};

export default DocumentViewerLayout;
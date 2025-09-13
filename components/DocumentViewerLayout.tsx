

import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Quotation, PurchaseInvoice, SalesInvoice } from '../types';
import Spinner from './Spinner';
import { generatePdfBlob } from '../utils/pdfUtils';
import WhatsappIcon from './icons/WhatsappIcon';
import DocumentArrowDownIcon from './icons/DocumentArrowDownIcon';

type AnyDocument = Quotation | PurchaseInvoice | SalesInvoice;

interface DocumentViewerLayoutProps {
    children: React.ReactNode;
    backPath: string;
    document: AnyDocument;
    pdfElementId: string;
    isProcessing: boolean;
    setIsProcessing: (isProcessing: boolean) => void;
    actions?: React.ReactNode;
}

const DocumentViewerLayout: React.FC<DocumentViewerLayoutProps> = ({
    children,
    backPath,
    document,
    pdfElementId,
    isProcessing,
    setIsProcessing,
    actions,
}) => {
    const navigate = useNavigate();
    const documentNumber = 'quotationNumber' in document ? document.quotationNumber : document.invoiceNumber;
    const documentTypeLabel = 'quotationNumber' in document ? 'عرض سعر' : ('supplierName' in document ? 'فاتورة مشتريات' : 'فاتورة مبيعات');


    const handleExportToPDF = async () => {
        setIsProcessing(true);
        const blob = await generatePdfBlob(pdfElementId);
        if (blob && documentNumber) {
            const url = URL.createObjectURL(blob);
            const a = window.document.createElement('a');
            a.href = url;
            a.download = `${documentNumber}.pdf`;
            window.document.body.appendChild(a);
            a.click();
            window.document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } else {
            alert("حدث خطأ أثناء إنشاء ملف PDF.");
        }
        setIsProcessing(false);
    };

    const handleShare = async () => {
        setIsProcessing(true);
        const blob = await generatePdfBlob(pdfElementId);
        setIsProcessing(false);

        if (!blob) {
            alert("لا يمكن إنشاء ملف PDF للمشاركة.");
            return;
        }

        const fileName = `${documentNumber}.pdf`;
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

    const iconButtonClasses = "w-12 h-10 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center";
    const buttonClasses = "w-full sm:w-auto px-5 py-2 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2";

    return (
        <>
            <div className="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 mb-6">
                <button onClick={() => navigate(backPath)} className={`bg-gray-200 hover:bg-gray-300 text-gray-800 focus:ring-gray-500 ${buttonClasses}`} disabled={isProcessing}>
                    العودة للقائمة
                </button>
                
                {actions}

                <div className="flex justify-end gap-3">
                    <button onClick={handleShare} className={`bg-green-500 hover:bg-green-600 text-white focus:ring-green-500 ${iconButtonClasses}`} disabled={isProcessing} title="مشاركة">
                        <WhatsappIcon className="w-5 h-5" />
                    </button>
                    <button onClick={handleExportToPDF} className={`bg-red-600 hover:bg-red-700 text-white focus:ring-red-500 ${iconButtonClasses}`} disabled={isProcessing} title="تصدير PDF">
                        {isProcessing ? <Spinner /> : <DocumentArrowDownIcon className="w-5 h-5" />}
                    </button>
                </div>
            </div>
            {children}
        </>
    );
};

export default DocumentViewerLayout;
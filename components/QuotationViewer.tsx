import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Quotation } from '../types';
import QuotationComponent from './Quotation';
import Spinner from './Spinner';
import { generatePdfBlob } from '../utils/pdfUtils';
import WhatsappIcon from './icons/WhatsappIcon';
import DocumentArrowDownIcon from './icons/DocumentArrowDownIcon';


interface QuotationViewerProps {
    quotation: Quotation;
}

const QuotationViewer: React.FC<QuotationViewerProps> = ({ quotation }) => {
    const navigate = useNavigate();
    const [isProcessing, setIsProcessing] = useState(false);

    const handleExportToPDF = async () => {
        setIsProcessing(true);
        const blob = await generatePdfBlob();
        if (blob && quotation) {
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${quotation.quotationNumber}.pdf`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } else {
            alert("حدث خطأ أثناء إنشاء ملف PDF.");
        }
        setIsProcessing(false);
    };

    const handleShare = async () => {
        if (!quotation) return;

        setIsProcessing(true);
        const blob = await generatePdfBlob();
        setIsProcessing(false); // Stop processing indicator after blob is generated

        if (!blob) {
            alert("لا يمكن إنشاء ملف PDF للمشاركة.");
            return;
        }

        const fileName = `${quotation.quotationNumber}.pdf`;
        const file = new File([blob], fileName, { type: 'application/pdf' });
        const shareData = {
            files: [file],
            title: `عرض سعر ${quotation.quotationNumber}`,
            text: `مرحباً،\n\nتجدون مرفقاً عرض السعر رقم ${quotation.quotationNumber} من شركة إنجاز.\n\nشكراً لكم.`,
        };

        if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
            try {
                await navigator.share(shareData);
            } catch (error) {
                console.error('Error sharing:', error);
            }
        } else {
            // Fallback for browsers that do not support sharing files (like desktop)
            const message = encodeURIComponent(shareData.text);
            window.open(`https://wa.me/?text=${message}`, '_blank');
        }
    };

    const buttonClasses = "w-full sm:w-auto px-5 py-2 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2";
    const iconButtonClasses = "w-12 h-10 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center";

    return (
        <>
            <div className="flex flex-col sm:flex-row sm:justify-end gap-3 mb-6">
                <button onClick={() => navigate('/quotations')} className={`bg-gray-200 hover:bg-gray-300 text-gray-800 focus:ring-gray-500 ${buttonClasses}`} disabled={isProcessing}>
                    العودة للقائمة
                </button>
                <button onClick={handleShare} className={`bg-green-500 hover:bg-green-600 text-white focus:ring-green-500 ${iconButtonClasses}`} disabled={isProcessing} aria-busy={isProcessing} title="مشاركة عبر واتساب">
                    {isProcessing ? <Spinner /> : <WhatsappIcon className="w-5 h-5" />}
                </button>
                <button onClick={handleExportToPDF} className={`bg-red-600 hover:bg-red-700 text-white focus:ring-red-500 ${iconButtonClasses}`} disabled={isProcessing} aria-busy={isProcessing} title="تصدير PDF">
                     {isProcessing ? <Spinner /> : <DocumentArrowDownIcon className="w-5 h-5" />}
                </button>
            </div>
            <QuotationComponent quotation={quotation} />
        </>
    );
};

export default QuotationViewer;

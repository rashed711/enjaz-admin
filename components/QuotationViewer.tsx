import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { jsPDF } from 'jspdf';
import html2canvas from 'html2canvas';
import { Quotation } from '../types';
import QuotationComponent from './Quotation';
import Spinner from './Spinner';

interface QuotationViewerProps {
    quotation: Quotation;
    onEdit: () => void;
}

const QuotationViewer: React.FC<QuotationViewerProps> = ({ quotation, onEdit }) => {
    const navigate = useNavigate();
    const [isProcessingPdf, setIsProcessingPdf] = useState(false);

    const generatePdfBlob = async (): Promise<Blob | null> => {
        const input = document.getElementById('quotation-pdf');
        if (!input) return null;

        const originalWidth = input.style.width;
        input.style.width = '1024px';

        try {
            const canvas = await html2canvas(input, { scale: 2, useCORS: true });
            
            const pdf = new jsPDF('p', 'mm', 'a4');
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = pdf.internal.pageSize.getHeight();
            
            const canvasWidth = canvas.width;
            const canvasHeight = canvas.height;
            const ratio = canvasWidth / pdfWidth;
            
            let canvasPosition = 0;
            while (canvasPosition < canvasHeight) {
                const pageHeightInCanvas = Math.min(pdfHeight * ratio, canvasHeight - canvasPosition);
                const pageCanvas = document.createElement('canvas');
                pageCanvas.width = canvasWidth;
                pageCanvas.height = pageHeightInCanvas;
                
                const ctx = pageCanvas.getContext('2d');
                if (ctx) {
                    ctx.drawImage(canvas, 0, canvasPosition, canvasWidth, pageHeightInCanvas, 0, 0, canvasWidth, pageHeightInCanvas);
                    const imgData = pageCanvas.toDataURL('image/png');
                    const pageHeightInPDF = pageHeightInCanvas / ratio;
                    if (canvasPosition > 0) pdf.addPage();
                    pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pageHeightInPDF);
                    canvasPosition += pageHeightInCanvas;
                } else {
                    return null;
                }
            }
            return pdf.output('blob');
        } catch (error) {
            console.error("Error generating PDF:", error);
            return null;
        } finally {
            input.style.width = originalWidth;
        }
    };

    const handleExportToPDF = async () => {
        setIsProcessingPdf(true);
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
        setIsProcessingPdf(false);
    };

    const handleShare = async () => {
        if (!quotation) return;

        setIsProcessingPdf(true);
        const blob = await generatePdfBlob();
        if (!blob) {
            alert("لا يمكن إنشاء ملف PDF للمشاركة.");
            setIsProcessingPdf(false);
            return;
        }

        const fileName = `${quotation.quotationNumber}.pdf`;
        const file = new File([blob], fileName, { type: 'application/pdf' });
        const shareData = {
            files: [file],
            title: `عرض سعر ${quotation.quotationNumber}`,
            text: `مرحباً،\n\nتجدون مرفقاً عرض السعر رقم ${quotation.quotationNumber} من شركة إنجاز.\n\nشكراً لكم.`,
        };

        if (navigator.share && navigator.canShare && navigator.canShare(shareData)) {
            try {
                await navigator.share(shareData);
            } catch (error) {
                console.error('Error sharing:', error);
            }
        } else {
            alert("مشاركة الملفات غير مدعومة على هذا المتصفح. سيتم فتح واتساب مع رسالة نصية.");
            const message = encodeURIComponent(shareData.text);
            window.open(`https://wa.me/?text=${message}`, '_blank');
        }
        setIsProcessingPdf(false);
    };

    const buttonClasses = "px-5 py-2 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2";

    return (
        <>
            <div className="flex flex-col sm:flex-row sm:justify-end gap-3 mb-6">
                <button onClick={() => navigate('/quotations')} className={`w-full sm:w-auto bg-gray-500 hover:bg-gray-600 text-white ${buttonClasses}`} disabled={isProcessingPdf}>
                    العودة للقائمة
                </button>
                <button onClick={onEdit} className={`w-full sm:w-auto bg-yellow-500 hover:bg-yellow-600 text-dark-text ${buttonClasses}`} disabled={isProcessingPdf}>
                    تعديل
                </button>
                <button onClick={handleShare} className={`w-full sm:w-auto bg-green-500 hover:bg-green-600 text-white ${buttonClasses}`} disabled={isProcessingPdf} aria-busy={isProcessingPdf}>
                    {isProcessingPdf && <Spinner />}
                    {isProcessingPdf ? 'جاري التحضير...' : 'مشاركة عبر واتساب'}
                </button>
                <button onClick={handleExportToPDF} className={`w-full sm:w-auto bg-[#10B981] hover:bg-[#059669] text-white ${buttonClasses}`} disabled={isProcessingPdf} aria-busy={isProcessingPdf}>
                    {isProcessingPdf && <Spinner />}
                    {isProcessingPdf ? 'جاري التحضير...' : 'تصدير PDF'}
                </button>
            </div>
            <QuotationComponent quotation={quotation} />
        </>
    );
};

export default QuotationViewer;

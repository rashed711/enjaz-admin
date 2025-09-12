import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Quotation, SalesInvoiceStatus } from '../types';
import QuotationComponent from './Quotation';
import Spinner from './Spinner';
import { generatePdfBlob } from '../utils/pdfUtils';
import { useAuth } from '../hooks/useAuth';
import { supabase } from '../services/supabaseClient';
import WhatsappIcon from './icons/WhatsappIcon';
import DocumentArrowDownIcon from './icons/DocumentArrowDownIcon';
import ArrowRightCircleIcon from './icons/ArrowRightCircleIcon';


interface QuotationViewerProps {
    quotation: Quotation;
}

const QuotationViewer: React.FC<QuotationViewerProps> = ({ quotation }) => {
    const navigate = useNavigate();
    const { currentUser } = useAuth();
    const [isProcessing, setIsProcessing] = useState(false);

    const handleExportToPDF = async () => {
        setIsProcessing(true);
        const blob = await generatePdfBlob('quotation-pdf');
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
        const blob = await generatePdfBlob('quotation-pdf');
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
            } catch (error: any) {
                console.error('Error sharing:', error.message);
            }
        } else {
            // Fallback for browsers that do not support sharing files (like desktop)
            const message = encodeURIComponent(shareData.text);
            window.open(`https://wa.me/?text=${message}`, '_blank');
        }
    };

    const handleConvertToInvoice = async () => {
        if (!quotation || !currentUser) return;
        setIsProcessing(true);
    
        try {
            // Check if an invoice for this quotation already exists
            const { data: existingInvoice, error: checkError } = await supabase
                .from('sales_invoices')
                .select('id, invoice_number')
                .eq('quotation_id', quotation.id)
                .maybeSingle();

            if (checkError) throw checkError;

            if (existingInvoice) {
                alert(`هذا العرض تم تحويله بالفعل إلى الفاتورة رقم ${existingInvoice.invoice_number}.`);
                navigate(`/sales-invoices/${existingInvoice.id}/view`);
                return;
            }

            // Generate new invoice number
            const { count, error: countError } = await supabase
                .from('sales_invoices')
                .select('*', { count: 'exact', head: true });
            if (countError) throw countError;
            const newInvoiceNumber = `SALE-INV-2024-${(count ?? 0) + 1}`;
    
            // Create the invoice object
            const newInvoicePayload = {
                invoice_number: newInvoiceNumber,
                client_name: quotation.clientName,
                company: quotation.company,
                project: quotation.project,
                date: new Date().toISOString().split('T')[0],
                currency: quotation.currency,
                status: SalesInvoiceStatus.DRAFT,
                total_amount: quotation.totalAmount,
                created_by: currentUser.id,
                quotation_id: quotation.id,
            };
    
            // Insert the new invoice and get its ID
            const { data: newInvoice, error: invoiceError } = await supabase
                .from('sales_invoices')
                .insert(newInvoicePayload)
                .select('id')
                .single();
            if (invoiceError || !newInvoice) throw invoiceError || new Error("Failed to create invoice.");
    
            // Prepare and insert invoice items
            const newInvoiceItems = quotation.items.map(item => ({
                invoice_id: newInvoice.id,
                product_id: item.productId || null,
                description: item.description,
                quantity: item.quantity,
                unit_price: item.unitPrice,
                total: item.total,
                unit: item.unit,
                length: item.length,
                width: item.width,
                height: item.height,
            }));

            if (newInvoiceItems.length > 0) {
                const { error: itemsError } = await supabase
                    .from('sales_invoice_items')
                    .insert(newInvoiceItems);
                if (itemsError) throw itemsError;
            }
    
            navigate(`/sales-invoices/${newInvoice.id}/view`);
    
        } catch (error: any) {
            console.error("Error converting to invoice:", error.message);
            alert(`فشل تحويل عرض السعر إلى فاتورة: ${error.message}`);
        } finally {
            setIsProcessing(false);
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
                <button onClick={handleConvertToInvoice} className={`bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500 ${buttonClasses}`} disabled={isProcessing} aria-busy={isProcessing}>
                    <ArrowRightCircleIcon className="w-5 h-5" />
                    <span>تحويل إلى فاتورة</span>
                </button>
                <button onClick={handleShare} className={`bg-green-500 hover:bg-green-600 text-white focus:ring-green-500 ${iconButtonClasses}`} disabled={isProcessing} aria-busy={isProcessing} title="مشاركة عبر واتساب">
                    <WhatsappIcon className="w-5 h-5" />
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

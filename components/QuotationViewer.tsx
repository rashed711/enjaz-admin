

import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Quotation, SalesInvoiceStatus } from '../types';
import QuotationComponent from './Quotation';
import DocumentViewerLayout from './DocumentViewerLayout';
import { useAuth } from '../hooks/useAuth';
import { supabase } from '../services/supabaseClient';
import ArrowRightCircleIcon from './icons/ArrowRightCircleIcon';

interface QuotationViewerProps {
    quotation: Quotation;
}

const QuotationViewer: React.FC<QuotationViewerProps> = ({ quotation }) => {
    const navigate = useNavigate();
    const { currentUser } = useAuth();
    const [isProcessing, setIsProcessing] = useState(false);

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
    
    const actions = (
        <button 
            onClick={handleConvertToInvoice} 
            className={`bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500 ${buttonClasses}`} 
            disabled={isProcessing} 
            aria-busy={isProcessing}
        >
            <ArrowRightCircleIcon className="w-5 h-5" />
            <span>تحويل إلى فاتورة</span>
        </button>
    );

    return (
        <DocumentViewerLayout
            backPath="/quotations"
            document={quotation}
            pdfElementId="quotation-pdf"
            isProcessing={isProcessing}
            setIsProcessing={setIsProcessing}
            actions={actions}
        >
            <QuotationComponent quotation={quotation} />
        </DocumentViewerLayout>
    );
};

export default QuotationViewer;
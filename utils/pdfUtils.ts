import { jsPDF } from 'jspdf';
import html2canvas from 'html2canvas';

/**
 * Generates a PDF from an HTML element with a specific ID and returns it as a Blob.
 * @returns {Promise<Blob | null>} A promise that resolves with the PDF Blob, or null if an error occurs.
 */
export const generatePdfBlob = async (): Promise<Blob | null> => {
    const input = document.getElementById('quotation-pdf');
    if (!input) {
        console.error("PDF generation failed: Element with ID 'quotation-pdf' not found.");
        return null;
    }

    // Temporarily set a fixed width to ensure consistent PDF output
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
        // Paginate the canvas image into the PDF
        while (canvasPosition < canvasHeight) {
            const pageHeightInCanvas = Math.min(pdfHeight * ratio, canvasHeight - canvasPosition);
            const pageCanvas = document.createElement('canvas');
            pageCanvas.width = canvasWidth;
            pageCanvas.height = pageHeightInCanvas;
            
            const ctx = pageCanvas.getContext('2d');
            if (ctx) {
                // Draw the relevant part of the main canvas onto the page canvas
                ctx.drawImage(canvas, 0, canvasPosition, canvasWidth, pageHeightInCanvas, 0, 0, canvasWidth, pageHeightInCanvas);
                const imgData = pageCanvas.toDataURL('image/png');
                const pageHeightInPDF = pageHeightInCanvas / ratio;
                if (canvasPosition > 0) pdf.addPage();
                pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pageHeightInPDF);
                canvasPosition += pageHeightInCanvas;
            } else {
                 console.error("Could not get canvas context for PDF page generation.");
                return null;
            }
        }
        return pdf.output('blob');
    } catch (error) {
        console.error("Error generating PDF:", error);
        return null;
    } finally {
        // Restore original width
        input.style.width = originalWidth;
    }
};

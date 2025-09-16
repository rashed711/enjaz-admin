import html2canvas from 'html2canvas';
import jsPDF from 'jspdf';

/**
 * Generates a PDF blob from an HTML element with improved quality and compression.
 * @param elementId The ID of the HTML element to convert to PDF.
 * @returns A Promise that resolves to a Blob, or null if an error occurs.
 */
export const generatePdfBlob = async (elementId: string): Promise<Blob | null> => {
  const element = document.getElementById(elementId);
  if (!element) {
    console.error(`PDF Generation Error: Element with id "${elementId}" not found.`);
    return null;
  }

  try {
    // ننتظر حتى يتم تحميل جميع الخطوط في الصفحة بالكامل قبل البدء في عملية التحويل
    await document.fonts.ready;

    // 1. Increase scale for higher resolution canvas. A scale of 3 provides much better quality.
    const canvas = await html2canvas(element, { scale: 3, useCORS: true });
    
    // 2. Use JPEG format for better compression. The quality is set to 0.95 (out of 1.0).
    const imgData = canvas.toDataURL('image/jpeg', 0.95);
    
    // 3. Initialize jsPDF with compression enabled.
    const pdf = new jsPDF({
        orientation: 'p', // portrait
        unit: 'mm',
        format: 'a4',
        compress: true,
    });

    const pdfWidth = pdf.internal.pageSize.getWidth();
    const imgWidth = canvas.width;
    const imgHeight = canvas.height;
    const ratio = pdfWidth / imgWidth;

    pdf.addImage(imgData, 'JPEG', 0, 0, imgWidth * ratio, imgHeight * ratio);
    return pdf.output('blob');

  } catch (error) {
    console.error("Error generating PDF:", error);
    return null;
  }
};
import { jsPDF } from 'jspdf';
import html2canvas from 'html2canvas';
import { getEmbeddedFontCSS } from './fontEmbedder';

/**
 * Generates a PDF blob from an HTML element using html2canvas.
 * This creates a "screenshot" of the element.
 * @param elementId The ID of the HTML element to convert to PDF.
 * @returns A Promise that resolves to a Blob, or null if an error occurs.
 */
export const generatePdfBlob = async (elementId: string): Promise<Blob | null> => {
    const element = document.getElementById(elementId);
    if (!element) {
        console.error(`PDF Generation Error: Element with id "${elementId}" not found.`);
        return null;
    }

    // Pre-fetch and prepare the font CSS with embedded data URIs to ensure correct font rendering.
    const fontCSSUrl = 'https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap';
    const embeddedFontCSS = await getEmbeddedFontCSS(fontCSSUrl);

    try {
        // Wait for all fonts on the page to be loaded before rendering the canvas
        await document.fonts.ready;

        const canvas = await html2canvas(element, {
            scale: 2, // Increase scale for better resolution
            useCORS: true,
            allowTaint: true,
            backgroundColor: '#ffffff', // Ensure a white background for transparent elements
            onclone: (clonedDoc) => {
                if (embeddedFontCSS) {
                    // Inject the font CSS with data URIs into the cloned document's head.
                    // This makes the font available synchronously to html2canvas.
                    const style = clonedDoc.createElement('style');
                    style.appendChild(clonedDoc.createTextNode(embeddedFontCSS));
                    clonedDoc.head.appendChild(style);
                }
            },
        });

        const imgData = canvas.toDataURL('image/jpeg', 0.95);

        const pdf = new jsPDF({
            orientation: 'p',
            unit: 'pt',
            format: 'a4',
            compress: true,
        });

        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = pdf.internal.pageSize.getHeight();
        const imgWidth = canvas.width;
        const imgHeight = canvas.height;

        const ratio = Math.min(pdfWidth / imgWidth, pdfHeight / imgHeight);
        const imgX = (pdfWidth - imgWidth * ratio) / 2;
        const imgY = 0;

        pdf.addImage(imgData, 'JPEG', imgX, imgY, imgWidth * ratio, imgHeight * ratio);
        return pdf.output('blob');
    } catch (error) {
        console.error("Error generating PDF from HTML element:", error);
        return null;
    }
};
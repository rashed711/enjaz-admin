import { jsPDF } from 'jspdf';
import { Account, JournalEntry } from '../types';

// The jspdf-autotable plugin and fonts are loaded dynamically before this function is called.
// We just need to declare the autoTable method for TypeScript.
declare module 'jspdf' {
    interface jsPDF {
        autoTable: (options: any) => jsPDF;
    }
}

const formatCurrency = (amount: number) => {
    const absAmount = Math.abs(amount);
    const formatted = absAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    return amount < 0 ? `(${formatted})` : formatted;
};

export const generateStatementPdfBlob = async (
    party: Account,
    entries: (JournalEntry & { balance: number })[],
    finalBalance: number
): Promise<Blob> => {
    const doc = new jsPDF({
        orientation: 'p',
        unit: 'pt',
        format: 'a4'
    });

    // --- Font Setup (Crucial for Arabic) ---
    // ملفات الخطوط التي أضفتها تقوم بتحميل الخطوط في نظام الملفات الافتراضي للمكتبة.
    // قد تقوم بتسجيلها بأسماء عائلات مختلفة، لذلك نقوم هنا بإعادة تسجيلها تحت عائلة واحدة "Cairo" لضمان عملها بشكل صحيح.
    // استخدمنا أسماء الملفات الصحيحة من داخل VFS التي تضيفها السكريبتات الخاصة بك.
    doc.addFont('Cairo-Regular-normal.ttf', 'Cairo', 'normal');
    doc.addFont('Cairo-Bold-bold.ttf', 'Cairo', 'bold');
    doc.setFont('Cairo');

    const pageWidth = doc.internal.pageSize.getWidth();
    const margin = 40;

    // --- Helper for RTL text ---
    const rtlText = (text: string, x: number, y: number, options: any = {}) => {
        doc.text(text, x, y, { ...options, align: 'right', lang: 'ar' });
    };

    // --- Header ---
    doc.setFontSize(18);
    doc.setFont('Cairo', 'bold');
    rtlText('انجاز للتكنولوجيا والمقاولات', pageWidth - margin, margin);
    doc.setFontSize(10);
    doc.setFont('Cairo', 'normal');
    rtlText('www.EnjazTec.com', pageWidth - margin, margin + 15);

    doc.setFontSize(24);
    doc.setFont('Cairo', 'bold');
    doc.setTextColor('#10B981'); // Primary color
    doc.text('كشف حساب', margin, margin, { lang: 'ar' });
    doc.setFontSize(12);
    doc.setTextColor(100); // Gray
    doc.text('Account Statement', margin, margin + 15);
    doc.setDrawColor('#10B981');
    doc.setLineWidth(2);
    doc.line(margin, margin + 30, pageWidth - margin, margin + 30);

    // --- Info Section ---
    const infoY = margin + 60;
    doc.setFontSize(10);
    doc.setTextColor(0); // Black

    // Right Box
    rtlText('الاسم:', pageWidth - margin, infoY);
    rtlText(party.name, pageWidth - margin - 70, infoY);
    rtlText('الكود:', pageWidth - margin, infoY + 15);
    rtlText(party.code || '-', pageWidth - margin - 70, infoY + 15);

    // Left Box
    rtlText('تاريخ البيان:', pageWidth / 2, infoY);
    rtlText(new Date().toLocaleDateString('en-GB'), pageWidth / 2 - 70, infoY);
    rtlText('الرصيد الحالي:', pageWidth / 2, infoY + 30);
    doc.setFont('Cairo', 'bold');
    doc.setTextColor(finalBalance >= 0 ? '#DC2626' : '#16A34A'); // Red-600 or Green-600
    rtlText(formatCurrency(finalBalance), pageWidth / 2 - 70, infoY + 30);
    doc.setFont('Cairo', 'normal');
    doc.setTextColor(0);

    // --- Table ---
    const tableY = infoY + 60;
    const head = [['الرصيد', 'دائن', 'مدين', 'الوصف', 'التاريخ', 'الوقت']];
    const body = entries.map(entry => [
        { content: formatCurrency(entry.balance), styles: { halign: 'center', fontStyle: 'bold', textColor: entry.balance >= 0 ? '#DC2626' : '#16A34A' } },
        { content: entry.credit > 0 ? formatCurrency(entry.credit) : '-', styles: { halign: 'center' } },
        { content: entry.debit > 0 ? formatCurrency(entry.debit) : '-', styles: { halign: 'center' } },
        { content: entry.description, styles: { halign: 'right' } },
        { content: new Date(entry.date).toLocaleDateString('en-GB'), styles: { halign: 'center' } },
        { content: entry.createdAt ? new Date(entry.createdAt).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '-', styles: { halign: 'center' } },
    ]);

    const totalDebit = entries.reduce((sum, e) => sum + (e.debit || 0), 0);
    const totalCredit = entries.reduce((sum, e) => sum + (e.credit || 0), 0);

    doc.autoTable({
        startY: tableY,
        head: head,
        body: body,
        theme: 'grid',
        styles: { font: 'Cairo', cellPadding: 5, fontSize: 9 },
        headStyles: { fillColor: '#10B981', textColor: 255, fontStyle: 'bold', halign: 'center' },
        foot: [[
            { content: formatCurrency(finalBalance), styles: { halign: 'center', fontStyle: 'bold' } },
            { content: formatCurrency(totalCredit), styles: { halign: 'center', fontStyle: 'bold' } },
            { content: formatCurrency(totalDebit), styles: { halign: 'center', fontStyle: 'bold' } },
            { content: 'الإجمالي', colSpan: 3, styles: { halign: 'right', fontStyle: 'bold' } },
        ]],
        footStyles: { fillColor: '#10B981', textColor: 255 },
        columnStyles: { 3: { cellWidth: 'auto' } }, // Description column
    });

    // --- Footer ---
    const footerY = doc.internal.pageSize.getHeight() - 30;
    doc.setFontSize(8);
    doc.setTextColor(150);
    rtlText('إذا كان لديكم أي استفسار بخصوص كشف الحساب هذا، يرجى التواصل معنا.', pageWidth - margin, footerY);
    rtlText('شكراً لتعاملكم معنا.', pageWidth - margin, footerY + 10);

    return doc.output('blob');
};
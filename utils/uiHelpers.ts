import { PurchaseInvoiceStatus, SalesInvoiceStatus } from '../types';

export const getStatusChipClassName = (status: PurchaseInvoiceStatus | SalesInvoiceStatus): string => {
    switch (status) {
        // Sales & Purchase Common Statuses
        case SalesInvoiceStatus.PAID:
        case PurchaseInvoiceStatus.PAID:
            return 'bg-green-100 text-green-800';
        case SalesInvoiceStatus.DRAFT:
        case PurchaseInvoiceStatus.DRAFT:
            return 'bg-yellow-100 text-yellow-800';
        case SalesInvoiceStatus.CANCELLED:
        case PurchaseInvoiceStatus.CANCELLED:
            return 'bg-red-100 text-red-800';
        
        // Sales Invoice Specific Statuses
        case SalesInvoiceStatus.SENT:
            return 'bg-blue-100 text-blue-800';
        case SalesInvoiceStatus.OVERDUE:
            return 'bg-orange-100 text-orange-800';

        default:
            return 'bg-gray-100 text-gray-800';
    }
}

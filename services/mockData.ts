
import { User, Quotation, Role, Currency } from '../types';

export const mockUsers: User[] = [
  { id: '1', name: 'علي الأحمد', email: 'ceo@enjaz.com', role: Role.CEO },
  { id: '2', name: 'فاطمة الزهراء', email: 'sales.manager@enjaz.com', role: Role.SALES_MANAGER },
  { id: '3', name: 'خالد عبدالله', email: 'sales1@enjaz.com', role: Role.SALES_EMPLOYEE },
  { id: '4', name: 'سارة إبراهيم', email: 'sales2@enjaz.com', role: Role.SALES_EMPLOYEE },
  { id: '5', name: 'حسن المحمود', email: 'accounts.manager@enjaz.com', role: Role.ACCOUNTING_MANAGER },
  { id: '6', name: 'عميل تجريبي', email: 'client@company.com', role: Role.CLIENT },
];

export const mockQuotations: Quotation[] = [
  {
    id: 1,
    quotationNumber: 'ENJ-2024-001',
    clientName: 'شركة المشاريع الحديثة',
    company: 'المشاريع الحديثة القابضة',
    project: 'تجديد الهوية البصرية',
    quotationType: 'خدمات تسويقية',
    date: '2024-07-20',
    currency: Currency.SAR,
    items: [
      { id: 101, description: 'تصميم هوية بصرية كاملة', quantity: 1, unitPrice: 5000, total: 5000 },
      { id: 102, description: 'تطوير موقع إلكتروني', quantity: 1, unitPrice: 12000, total: 12000 },
    ],
    totalAmount: 17000,
    createdBy: '3', // Khaled
    // FIX: Add missing properties to satisfy the Quotation type.
    taxIncluded: false,
    discount: 0,
  },
  {
    id: 2,
    quotationNumber: 'ENJ-2024-002',
    clientName: 'مؤسسة البناء المتحدة',
    company: 'مؤسسة البناء المتحدة',
    project: 'حملة إعلانية',
    quotationType: 'إعلانات',
    date: '2024-07-22',
    currency: Currency.SAR,
    items: [
      { id: 103, description: 'حملة تسويق رقمي (3 أشهر)', quantity: 1, unitPrice: 9000, total: 9000 },
    ],
    totalAmount: 9000,
    createdBy: '4', // Sara
    // FIX: Add missing properties to satisfy the Quotation type.
    taxIncluded: false,
    discount: 0,
  },
  {
    id: 3,
    quotationNumber: 'ENJ-2024-003',
    clientName: 'متجر الأزياء العصرية',
    company: 'الأزياء العصرية',
    project: 'تصوير مجموعة الصيف',
    quotationType: 'خدمات إعلامية',
    date: '2024-07-25',
    currency: Currency.SAR,
    items: [
      { id: 104, description: 'تصوير منتجات احترافي', quantity: 50, unitPrice: 100, total: 5000 },
      { id: 105, description: 'إدارة حسابات التواصل الاجتماعي', quantity: 1, unitPrice: 3000, total: 3000 },
    ],
    totalAmount: 8000,
    createdBy: '3', // Khaled
    // FIX: Add missing properties to satisfy the Quotation type.
    taxIncluded: false,
    discount: 0,
  },
];

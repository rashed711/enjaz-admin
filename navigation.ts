import { Role } from './types';
import DocumentTextIcon from './components/icons/DocumentTextIcon';
import UsersIcon from './components/icons/UsersIcon';
import CubeIcon from './components/icons/CubeIcon';
import BanknotesIcon from './components/icons/BanknotesIcon';
import ArchiveBoxIcon from './components/icons/ArchiveBoxIcon';
import DocumentDuplicateIcon from './components/icons/DocumentDuplicateIcon';

// --- ملاحظة: الأيقونات التالية لم تكن موجودة، فاستخدمت أيقونات بديلة ---
// --- قد تحتاج إلى استبدالها بالأيقونات الصحيحة لديك ---
const HomeIcon = DocumentDuplicateIcon;
const BriefcaseIcon = DocumentDuplicateIcon;
const CogIcon = DocumentDuplicateIcon;
const KeyIcon = DocumentDuplicateIcon;
const UserCircleIcon = UsersIcon;
// --- نهاية الأيقونات البديلة ---

export interface NavItem {
    label: string;
    path: string;
    Icon: React.FC<any>;
    title: string;
    inSidebar: boolean;
    roles?: Role[];
    inSubMenu?: boolean;
    children?: NavItem[];
}

export const navigationConfig: NavItem[] = [
    {
        label: 'لوحة التحكم',
        path: '/',
        Icon: HomeIcon,
        title: 'لوحة التحكم الرئيسية',
        inSidebar: true,
        roles: [Role.CEO, Role.SALES_MANAGER, Role.SALES_EMPLOYEE, Role.ACCOUNTING_MANAGER, Role.ACCOUNTANT],
    },
    {
        label: 'المحاسبة',
        path: '/accounting',
        Icon: BanknotesIcon,
        title: 'إدارة المحاسبة والمالية',
        inSidebar: true,
        roles: [Role.CEO, Role.ACCOUNTING_MANAGER, Role.ACCOUNTANT],
        children: [
            {
                label: 'دليل الحسابات',
                path: '/accounts/chart-of-accounts',
                inSubMenu: true,
                Icon: DocumentDuplicateIcon,
                title: 'عرض وتعديل شجرة الحسابات',
                roles: [Role.CEO, Role.ACCOUNTING_MANAGER],
            },
            {
                label: 'القيود اليومية',
                path: '/accounts/journal-entries',
                inSubMenu: true,
                Icon: DocumentTextIcon,
                title: 'عرض وإدارة القيود اليومية',
                roles: [Role.CEO, Role.ACCOUNTING_MANAGER, Role.ACCOUNTANT],
            },
            {
                label: 'سندات القبض',
                path: '/accounts/receipts',
                inSubMenu: true,
                Icon: ArchiveBoxIcon,
                title: 'إدارة سندات القبض',
                roles: [Role.CEO, Role.ACCOUNTING_MANAGER, Role.ACCOUNTANT],
            },
            {
                label: 'قائمة العملاء',
                path: '/accounts/customers',
                inSubMenu: true,
                Icon: UsersIcon,
                title: 'عرض وإدارة العملاء',
                roles: [Role.CEO, Role.ACCOUNTING_MANAGER, Role.ACCOUNTANT, Role.SALES_MANAGER, Role.SALES_EMPLOYEE],
            },
            {
                label: 'قائمة الموردين',
                path: '/accounts/suppliers',
                inSubMenu: true,
                Icon: UsersIcon,
                title: 'عرض وإدارة الموردين',
                roles: [Role.CEO, Role.ACCOUNTING_MANAGER, Role.ACCOUNTANT],
            },
        ],
    },
    {
        label: 'عروض الأسعار',
        path: '/quotations',
        Icon: DocumentTextIcon,
        title: 'عروض الأسعار',
        inSidebar: true,
        roles: [Role.CEO, Role.SALES_MANAGER, Role.SALES_EMPLOYEE],
    },
    {
        label: 'الفواتير',
        path: '/invoices-hub',
        Icon: BriefcaseIcon,
        title: 'إدارة الفواتير',
        inSidebar: true,
        roles: [Role.CEO, Role.SALES_MANAGER, Role.SALES_EMPLOYEE, Role.ACCOUNTING_MANAGER, Role.ACCOUNTANT],
        children: [
            {
                label: 'فواتير المبيعات',
                path: '/sales-invoices',
                inSubMenu: true,
                Icon: DocumentTextIcon,
                title: 'عرض وإدارة فواتير المبيعات',
                roles: [Role.CEO, Role.SALES_MANAGER, Role.SALES_EMPLOYEE, Role.ACCOUNTING_MANAGER, Role.ACCOUNTANT],
            },
            {
                label: 'فواتير المشتريات',
                path: '/purchase-invoices',
                inSubMenu: true,
                Icon: DocumentTextIcon,
                title: 'عرض وإدارة فواتير المشتريات',
                roles: [Role.CEO, Role.ACCOUNTING_MANAGER, Role.ACCOUNTANT],
            },
        ]
    },
    {
        label: 'الإدارة',
        path: '/management',
        Icon: CogIcon,
        title: 'إدارة النظام',
        inSidebar: true,
        roles: [Role.CEO],
        children: [
            { label: 'المنتجات والخدمات', path: '/products', inSubMenu: true, Icon: CubeIcon, title: 'إدارة المنتجات والخدمات', roles: [Role.CEO, Role.SALES_MANAGER] },
            { label: 'المستخدمين', path: '/users', inSubMenu: true, Icon: UsersIcon, title: 'إدارة المستخدمين', roles: [Role.CEO] },
            { label: 'الصلاحيات', path: '/permissions', inSubMenu: true, Icon: KeyIcon, title: 'إدارة صلاحيات الأدوار', roles: [Role.CEO] },
        ]
    },
    {
        label: 'الملف الشخصي',
        path: '/profile',
        Icon: UserCircleIcon,
        title: 'الملف الشخصي',
        inSidebar: false,
        roles: [Role.CEO, Role.SALES_MANAGER, Role.SALES_EMPLOYEE, Role.ACCOUNTING_MANAGER, Role.ACCOUNTANT, Role.CLIENT],
    },
];
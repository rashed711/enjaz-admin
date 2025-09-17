import { Role, NavLink, PermissionModule } from './types';
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

export const navigationConfig: NavLink[] = [
    {
        label: 'لوحة التحكم',
        path: '/',
        Icon: HomeIcon,
        title: 'لوحة التحكم الرئيسية',
        inSidebar: true,
        roles: [Role.CEO, Role.SALES_MANAGER, Role.SALES_EMPLOYEE, Role.ACCOUNTING_MANAGER, Role.ACCOUNTING_EMPLOYEE],
        inBottomNav: true,
        bottomNavLabel: 'الرئيسية',
    },
    {
        label: 'المحاسبة',
        path: '/accounting',
        Icon: BanknotesIcon,
        title: 'إدارة المحاسبة والمالية',
        inSidebar: true,
        roles: [Role.CEO, Role.ACCOUNTING_MANAGER, Role.ACCOUNTING_EMPLOYEE],
        inBottomNav: false,
        bottomNavLabel: 'المحاسبة',
        children: [
            {
                label: 'دليل الحسابات',
                path: '/accounts/chart-of-accounts',
                inSubMenu: true,
                Icon: DocumentDuplicateIcon,
                title: 'عرض وتعديل شجرة الحسابات',
                roles: [Role.CEO, Role.ACCOUNTING_MANAGER],
                permission: [PermissionModule.ACCOUNTS, 'VIEW_ANY'],
            },
            {
                label: 'القيود اليومية',
                path: '/accounts/journal-entries',
                inSubMenu: true,
                Icon: DocumentTextIcon,
                title: 'عرض وإدارة القيود اليومية',
                roles: [Role.CEO, Role.ACCOUNTING_MANAGER, Role.ACCOUNTING_EMPLOYEE],
                permission: [PermissionModule.JOURNAL_ENTRIES, 'VIEW_ANY'],
            },
            {
                label: 'سندات القبض',
                path: '/accounts/receipts',
                inSubMenu: true,
                Icon: ArchiveBoxIcon,
                title: 'إدارة سندات القبض',
                roles: [Role.CEO, Role.ACCOUNTING_MANAGER, Role.ACCOUNTING_EMPLOYEE],
                permission: [PermissionModule.RECEIPTS, 'VIEW_ANY'],
            },
            {
                label: 'سندات الصرف',
                path: '/accounts/payment-vouchers',
                inSubMenu: true,
                Icon: ArchiveBoxIcon,
                title: 'إدارة سندات الصرف',
                roles: [Role.CEO, Role.ACCOUNTING_MANAGER, Role.ACCOUNTING_EMPLOYEE],
                permission: [PermissionModule.PAYMENT_VOUCHERS, 'VIEW_ANY'],
            },
            {
                label: 'قائمة العملاء',
                path: '/accounts/customers',
                inSubMenu: true,
                Icon: UsersIcon,
                title: 'عرض وإدارة العملاء',
                roles: [Role.CEO, Role.ACCOUNTING_MANAGER, Role.ACCOUNTING_EMPLOYEE, Role.SALES_MANAGER, Role.SALES_EMPLOYEE],
                permission: [PermissionModule.CUSTOMERS, 'VIEW_ANY'],
            },
            {
                label: 'قائمة الموردين',
                path: '/accounts/suppliers',
                inSubMenu: true,
                Icon: UsersIcon,
                title: 'عرض وإدارة الموردين',
                roles: [Role.CEO, Role.ACCOUNTING_MANAGER, Role.ACCOUNTING_EMPLOYEE],
                permission: [PermissionModule.SUPPLIERS, 'VIEW_ANY'],
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
        inBottomNav: true,
        bottomNavLabel: 'العروض',
        permission: [PermissionModule.QUOTATIONS, 'VIEW_ANY'],
    },
    {
        label: 'الفواتير',
        path: '/invoices-hub',
        Icon: BriefcaseIcon,
        title: 'إدارة الفواتير',
        inSidebar: true,
        roles: [Role.CEO, Role.SALES_MANAGER, Role.SALES_EMPLOYEE, Role.ACCOUNTING_MANAGER, Role.ACCOUNTING_EMPLOYEE],
        inBottomNav: false,
        bottomNavLabel: 'الفواتير',
        children: [
            {
                label: 'فواتير المبيعات',
                path: '/sales-invoices',
                inSubMenu: true,
                Icon: DocumentTextIcon,
                title: 'عرض وإدارة فواتير المبيعات',
                roles: [Role.CEO, Role.SALES_MANAGER, Role.SALES_EMPLOYEE, Role.ACCOUNTING_MANAGER, Role.ACCOUNTING_EMPLOYEE],
                permission: [PermissionModule.SALES_INVOICES, 'VIEW_ANY'],
            },
            {
                label: 'فواتير المشتريات',
                path: '/purchase-invoices',
                inSubMenu: true,
                Icon: DocumentTextIcon,
                title: 'عرض وإدارة فواتير المشتريات',
                roles: [Role.CEO, Role.ACCOUNTING_MANAGER, Role.ACCOUNTING_EMPLOYEE],
                permission: [PermissionModule.PURCHASE_INVOICES, 'VIEW_ANY'],
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
        inBottomNav: true,
        bottomNavLabel: 'الإدارة',
        children: [
            { 
                label: 'المنتجات والخدمات', 
                path: '/products', 
                inSubMenu: true, 
                Icon: CubeIcon, 
                title: 'إدارة المنتجات والخدمات', 
                roles: [Role.CEO, Role.SALES_MANAGER],
                permission: [PermissionModule.PRODUCTS, 'VIEW_ANY'],
            },
            { 
                label: 'المستخدمين', 
                path: '/users', 
                inSubMenu: true, 
                Icon: UsersIcon, 
                title: 'إدارة المستخدمين', 
                roles: [Role.CEO],
                permission: [PermissionModule.USERS, 'MANAGE'],
            },
            { 
                label: 'الجلسات النشطة', 
                path: '/sessions', 
                inSubMenu: true, 
                Icon: KeyIcon, 
                title: 'مراقبة الجلسات النشطة', 
                roles: [Role.CEO] 
            }, // No specific permission module for this, CEO role is enough
            { label: 'الصلاحيات', path: '/permissions', inSubMenu: true, Icon: KeyIcon, title: 'إدارة صلاحيات الأدوار', roles: [Role.CEO], permission: [PermissionModule.PERMISSIONS, 'MANAGE'] },
        ]
    },
    {
        label: 'الملف الشخصي',
        path: '/profile',
        Icon: UserCircleIcon,
        title: 'الملف الشخصي',
        inSidebar: false,
        roles: [Role.CEO, Role.SALES_MANAGER, Role.SALES_EMPLOYEE, Role.ACCOUNTING_MANAGER, Role.ACCOUNTING_EMPLOYEE, Role.CLIENT],
        inBottomNav: true,
        bottomNavLabel: 'حسابي',
    },
];
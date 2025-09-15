import { Role, PermissionModule, PermissionAction, NavLink } from './types';
import HomeIcon from './components/icons/HomeIcon';
import DocumentTextIcon from './components/icons/DocumentTextIcon';
import CubeIcon from './components/icons/CubeIcon';
import UsersIcon from './components/icons/UsersIcon';
import UserCircleIcon from './components/icons/UserCircleIcon';
import ShieldCheckIcon from './components/icons/ShieldCheckIcon';
import CogIcon from './components/icons/CogIcon'; // New Icon
import BanknotesIcon from './components/icons/BanknotesIcon';
import ReceiptIcon from './components/icons/ReceiptIcon';
import ArchiveBoxIcon from './components/icons/ArchiveBoxIcon';

export const navigationConfig: NavLink[] = [
  {
    path: '/',
    label: 'الرئيسية',
    Icon: HomeIcon,
    roles: Object.values(Role),
    inSidebar: true,
    inBottomNav: true,
    title: 'لوحة التحكم',
  },
  {
    path: '/accounting',
    label: 'الحسابات',
    Icon: BanknotesIcon,
    roles: [],
    inSidebar: true,
    inBottomNav: false,
    title: 'إدارة الحسابات',
    children: [
      {
        path: '/accounts/chart-of-accounts',
        label: 'دليل الحسابات',
        Icon: BanknotesIcon,
        roles: [],
        permission: [PermissionModule.ACCOUNTS, PermissionAction.VIEW_ALL],
        title: 'دليل الحسابات',
        inSubMenu: true,
      },
      {
        path: '/accounts/journal-entries',
        label: 'القيود اليومية',
        Icon: DocumentTextIcon,
        roles: [],
        permission: [PermissionModule.JOURNAL_ENTRIES, PermissionAction.VIEW_ALL],
        title: 'القيود اليومية',
        inSubMenu: true,
      },
      {
        path: '/accounts/receipts',
        label: 'سندات القبض',
        Icon: ArchiveBoxIcon,
        roles: [],
        permission: [PermissionModule.RECEIPTS, PermissionAction.VIEW_ALL],
        title: 'سندات القبض',
        inSubMenu: true,
      },
      {
        path: '/accounts/receipts/:id/:mode?',
        label: 'محرر سندات القبض',
        Icon: ArchiveBoxIcon,
        roles: [],
        permission: [PermissionModule.RECEIPTS, PermissionAction.CREATE],
        title: 'عرض / تعديل سند قبض',
      },
    ],
  },
  {
    path: '/quotations',
    label: 'عروض الأسعار',
    Icon: DocumentTextIcon,
    roles: [Role.SALES_EMPLOYEE, Role.SALES_MANAGER, Role.CEO],
    inSidebar: true,
    inBottomNav: true,
    title: 'عروض الأسعار',
    children: [
      {
        path: '/quotations/:id/:mode?',
        label: 'محرر عروض الأسعار',
        Icon: DocumentTextIcon,
        roles: [Role.SALES_EMPLOYEE, Role.SALES_MANAGER, Role.CEO],
        title: 'عرض / تعديل عرض السعر',
      },
    ],
  },
  {
    path: '/invoices-hub',
    label: 'الفواتير',
    Icon: ReceiptIcon,
    roles: [],
    inSidebar: true,
    inBottomNav: true,
    title: 'الفواتير',
    children: [
      {
        path: '/sales-invoices',
        label: 'فواتير المبيعات',
        Icon: DocumentTextIcon,
        roles: [
          Role.SALES_EMPLOYEE,
          Role.SALES_MANAGER,
          Role.ACCOUNTING_MANAGER,
          Role.CEO,
        ],
        title: 'فواتير المبيعات',
        inSubMenu: true,
      },
      {
        path: '/purchase-invoices',
        label: 'فواتير المشتريات',
        Icon: DocumentTextIcon,
        roles: [
          Role.ACCOUNTING_EMPLOYEE,
          Role.ACCOUNTING_MANAGER,
          Role.CEO,
        ],
        title: 'فواتير المشتريات',
        inSubMenu: true,
      },
      {
        path: '/sales-invoices/:id/:mode?',
        label: 'محرر فواتير المبيعات',
        Icon: DocumentTextIcon,
        roles: [
          Role.SALES_EMPLOYEE,
          Role.SALES_MANAGER,
          Role.ACCOUNTING_MANAGER,
          Role.CEO,
        ],
        title: 'عرض / تعديل فاتورة مبيعات',
      },
      {
        path: '/purchase-invoices/:id/:mode?',
        label: 'محرر فواتير المشتريات',
        Icon: ReceiptIcon,
        roles: [
          Role.ACCOUNTING_EMPLOYEE,
          Role.ACCOUNTING_MANAGER,
          Role.CEO,
        ],
        title: 'عرض / تعديل فاتورة مشتريات',
      },
    ],
  },
  {
    path: '/management',
    label: 'الإدارة',
    Icon: CogIcon,
    roles: [],
    inSidebar: true,
    inBottomNav: true,
    title: 'الإدارة',
    children: [
      {
        path: '/products',
        label: 'المنتجات',
        Icon: CubeIcon,
        roles: [
          Role.SALES_EMPLOYEE,
          Role.SALES_MANAGER,
          Role.ACCOUNTING_MANAGER,
          Role.CEO,
        ],
        title: 'المنتجات',
        inSubMenu: true,
      },
      {
        path: '/users',
        label: 'إدارة المستخدمين',
        Icon: UsersIcon,
        roles: [Role.CEO, Role.ACCOUNTING_MANAGER],
        title: 'إدارة المستخدمين',
        inSubMenu: true,
      },
      {
        path: '/permissions',
        label: 'إدارة الصلاحيات',
        Icon: ShieldCheckIcon,
        roles: [Role.CEO],
        title: 'إدارة الصلاحيات',
        inSubMenu: true,
      },
    ],
  },
  {
    path: '/profile',
    label: 'الملف الشخصي',
    Icon: UserCircleIcon,
    roles: Object.values(Role),
    inSidebar: false,
    inBottomNav: true,
    title: 'الملف الشخصي',
  },
];
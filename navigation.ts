import { Role } from './types';
import HomeIcon from './components/icons/HomeIcon';
import DocumentTextIcon from './components/icons/DocumentTextIcon';
import CubeIcon from './components/icons/CubeIcon';
import UsersIcon from './components/icons/UsersIcon';
import UserCircleIcon from './components/icons/UserCircleIcon';
import ShieldCheckIcon from './components/icons/ShieldCheckIcon';
import CogIcon from './components/icons/CogIcon'; // New Icon
import BanknotesIcon from './components/icons/BanknotesIcon';
import DashboardPage from './pages/DashboardPage';
import QuotationsListPage from './pages/QuotationsListPage';
import QuotationEditorPage from './pages/QuotationEditorPage';
import ProductsListPage from './pages/ProductsListPage';
import UserManagementPage from './pages/UserManagementPage';
import ProfilePage from './pages/ProfilePage';
import PurchaseInvoicesListPage from './pages/PurchaseInvoicesListPage';
import PurchaseInvoiceEditorPage from './pages/PurchaseInvoiceEditorPage';
import SalesInvoicesListPage from './pages/SalesInvoicesListPage';
import SalesInvoiceEditorPage from './pages/SalesInvoiceEditorPage';
import ReceiptIcon from './components/icons/ReceiptIcon';
import PermissionsPage from './pages/PermissionsPage';
import ManagementPage from './pages/ManagementPage'; // New Page
import AccountsListPage from './pages/AccountsListPage';
import React from 'react';

interface NavLink {
    path: string;
    label: string;
    Icon: React.FC<React.SVGProps<SVGSVGElement>>;
    roles: Role[];
    inSidebar: boolean;
    inBottomNav: boolean;
    bottomNavLabel?: string;
    component: React.ComponentType;
    title: string;
    children?: NavLinkChild[];
}

// A more flexible type for children that includes properties needed by sub-menus and hub pages.
interface NavLinkChild {
    path: string;
    label: string;
    Icon: React.FC<React.SVGProps<SVGSVGElement>>;
    roles: Role[];
    component: React.ComponentType;
    title: string;
    inSubMenu?: boolean;
}

export const navigationConfig: NavLink[] = [
  {
    path: '/',
    label: 'الرئيسية',
    Icon: HomeIcon,
    roles: Object.values(Role),
    inSidebar: true,
    inBottomNav: true,
    component: DashboardPage,
    title: 'لوحة التحكم',
  },
  {
    path: '/accounts',
    label: 'الحسابات',
    Icon: BanknotesIcon,
    roles: Object.values(Role), // Accessible to all authenticated users for now
    inSidebar: true,
    inBottomNav: false,
    component: AccountsListPage,
    title: 'دليل الحسابات',
  },
  {
    path: '/quotations',
    label: 'عروض الأسعار',
    Icon: DocumentTextIcon,
    roles: [Role.SALES_EMPLOYEE, Role.SALES_MANAGER, Role.CEO],
    inSidebar: true,
    inBottomNav: true,
    component: QuotationsListPage,
    title: 'عروض الأسعار',
    children: [
      {
        path: '/quotations/:id/:mode?',
        label: 'محرر عروض الأسعار',
        Icon: DocumentTextIcon,
        roles: [Role.SALES_EMPLOYEE, Role.SALES_MANAGER, Role.CEO],
        component: QuotationEditorPage,
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
    component: ManagementPage,
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
        component: SalesInvoicesListPage,
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
        component: PurchaseInvoicesListPage,
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
        component: SalesInvoiceEditorPage,
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
        component: PurchaseInvoiceEditorPage,
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
    component: ManagementPage,
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
        component: ProductsListPage,
        title: 'المنتجات',
        inSubMenu: true,
      },
      {
        path: '/users',
        label: 'إدارة المستخدمين',
        Icon: UsersIcon,
        roles: [Role.CEO, Role.ACCOUNTING_MANAGER],
        component: UserManagementPage,
        title: 'إدارة المستخدمين',
        inSubMenu: true,
      },
      {
        path: '/permissions',
        label: 'إدارة الصلاحيات',
        Icon: ShieldCheckIcon,
        roles: [Role.CEO],
        component: PermissionsPage,
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
    component: ProfilePage,
    title: 'الملف الشخصي',
  },
];
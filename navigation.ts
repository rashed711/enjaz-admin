import { Role } from './types';
import HomeIcon from './components/icons/HomeIcon';
import DocumentTextIcon from './components/icons/DocumentTextIcon';
import CubeIcon from './components/icons/CubeIcon';
import UsersIcon from './components/icons/UsersIcon';
import UserCircleIcon from './components/icons/UserCircleIcon';
import ReceiptIcon from './components/icons/ReceiptIcon';
import ShieldCheckIcon from './components/icons/ShieldCheckIcon';
import CogIcon from './components/icons/CogIcon'; // New Icon
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
import PermissionsPage from './pages/PermissionsPage';
import ManagementPage from './pages/ManagementPage'; // New Page
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
}

export const navigationConfig: NavLink[] = [
  {
    path: '/',
    label: 'الرئيسية',
    Icon: HomeIcon,
    roles: Object.values(Role), // All roles can see the dashboard
    inSidebar: true,
    inBottomNav: true,
    component: DashboardPage,
    title: 'لوحة التحكم',
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
            title: 'عرض / تعديل السعر'
        }
    ]
  },
  {
    path: '/sales-invoices',
    label: 'فواتير المبيعات',
    Icon: DocumentTextIcon,
    roles: [Role.SALES_EMPLOYEE, Role.SALES_MANAGER, Role.ACCOUNTING_MANAGER, Role.CEO],
    inSidebar: true,
    inBottomNav: true,
    component: SalesInvoicesListPage,
    title: 'فواتير المبيعات',
    children: [
        {
            path: '/sales-invoices/:id/:mode?',
            label: 'محرر فواتير المبيعات',
            Icon: DocumentTextIcon,
            roles: [Role.SALES_EMPLOYEE, Role.SALES_MANAGER, Role.ACCOUNTING_MANAGER, Role.CEO],
            component: SalesInvoiceEditorPage,
            title: 'عرض / تعديل فاتورة مبيعات'
        }
    ]
  },
  {
    path: '/invoices',
    label: 'فواتير المشتريات',
    Icon: ReceiptIcon,
    roles: [Role.ACCOUNTING_EMPLOYEE, Role.ACCOUNTING_MANAGER, Role.CEO],
    inSidebar: true,
    inBottomNav: true,
    component: PurchaseInvoicesListPage,
    title: 'فواتير المشتريات',
    children: [
        {
            path: '/invoices/:id/:mode?',
            label: 'محرر فواتير المشتريات',
            Icon: ReceiptIcon,
            roles: [Role.ACCOUNTING_EMPLOYEE, Role.ACCOUNTING_MANAGER, Role.CEO],
            component: PurchaseInvoiceEditorPage,
            title: 'عرض / تعديل فاتورة'
        }
    ]
  },
  {
    path: '/management',
    label: 'الإدارة',
    Icon: CogIcon,
    roles: Object.values(Role), // Accessible to anyone who can access at least one child
    inSidebar: true,
    inBottomNav: true,
    component: ManagementPage,
    title: 'الإدارة',
    children: [
      {
        path: '/products',
        label: 'المنتجات',
        Icon: CubeIcon,
        roles: [Role.SALES_EMPLOYEE, Role.SALES_MANAGER, Role.ACCOUNTING_MANAGER, Role.CEO],
        component: ProductsListPage,
        title: 'المنتجات'
      },
      {
        path: '/users',
        label: 'إدارة المستخدمين',
        Icon: UsersIcon,
        roles: [Role.CEO, Role.ACCOUNTING_MANAGER],
        component: UserManagementPage,
        title: 'إدارة المستخدمين'
      },
      {
        path: '/permissions',
        label: 'إدارة الصلاحيات',
        Icon: ShieldCheckIcon,
        roles: [Role.CEO],
        component: PermissionsPage,
        title: 'إدارة الصلاحيات'
      },
      {
        path: '/profile',
        label: 'الملف الشخصي',
        Icon: UserCircleIcon,
        roles: Object.values(Role),
        component: ProfilePage,
        title: 'الملف الشخصي'
      },
    ]
  },
];
import { Role } from './types';
import HomeIcon from './components/icons/HomeIcon';
import DocumentTextIcon from './components/icons/DocumentTextIcon';
import CubeIcon from './components/icons/CubeIcon';
import UsersIcon from './components/icons/UsersIcon';
import UserCircleIcon from './components/icons/UserCircleIcon';
import ReceiptIcon from './components/icons/ReceiptIcon'; // New Icon
import DashboardPage from './pages/DashboardPage';
import QuotationsListPage from './pages/QuotationsListPage';
import QuotationEditorPage from './pages/QuotationEditorPage';
import ProductsListPage from './pages/ProductsListPage';
import UserManagementPage from './pages/UserManagementPage';
import ProfilePage from './pages/ProfilePage';
import PurchaseInvoicesListPage from './pages/PurchaseInvoicesListPage'; // New Page
import PurchaseInvoiceEditorPage from './pages/PurchaseInvoiceEditorPage'; // New Page
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
    children?: Omit<NavLink, 'inSidebar'| 'inBottomNav' | 'Icon' | 'label'>[];
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
            roles: [Role.SALES_EMPLOYEE, Role.SALES_MANAGER, Role.CEO],
            component: QuotationEditorPage,
            title: 'عرض / تعديل السعر'
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
            roles: [Role.ACCOUNTING_EMPLOYEE, Role.ACCOUNTING_MANAGER, Role.CEO],
            component: PurchaseInvoiceEditorPage,
            title: 'عرض / تعديل فاتورة'
        }
    ]
  },
  {
    path: '/products',
    label: 'المنتجات',
    Icon: CubeIcon,
    roles: [Role.SALES_EMPLOYEE, Role.SALES_MANAGER, Role.CEO],
    inSidebar: true,
    inBottomNav: true,
    component: ProductsListPage,
    title: 'المنتجات'
  },
  {
    path: '/users',
    label: 'إدارة المستخدمين',
    Icon: UsersIcon,
    roles: [Role.CEO, Role.ACCOUNTING_MANAGER],
    inSidebar: true,
    inBottomNav: true,
    component: UserManagementPage,
    title: 'إدارة المستخدمين'
  },
  {
    path: '/profile',
    label: 'الملف الشخصي',
    Icon: UserCircleIcon,
    roles: Object.values(Role),
    inSidebar: false, // Hidden from sidebar as user info is now at the top
    inBottomNav: true,
    bottomNavLabel: 'ملفي',
    component: ProfilePage,
    title: 'الملف الشخصي'
  },
];

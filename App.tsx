import React, { Suspense, lazy } from 'react';
import { HashRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import { ProductProvider } from './contexts/ProductContext';
import { AccountProvider } from './contexts/AccountContext';
import { PermissionsProvider } from './contexts/PermissionsContext';
import LoginPage from './pages/LoginPage';
import NotFoundPage from './pages/NotFoundPage';
import SessionManager from './components/SessionManager'; // إضافة جديدة
import ProtectedRoute from './components/ProtectedRoute';
import Layout from './components/Layout';
import Spinner from './components/Spinner';
import { useAuth } from './hooks/useAuth';
import { navigationConfig } from './navigation';
import { usePermissions } from './hooks/usePermissions';
import { PermissionModule, PermissionAction, Role } from './types';

const DashboardPage = lazy(() => import('./pages/DashboardPage'));
const ActiveSessionsPage = lazy(() => import('./pages/ActiveSessionsPage')); // إضافة جديدة
const QuotationsListPage = lazy(() => import('./pages/QuotationsListPage'));
const QuotationEditorPage = lazy(() => import('./pages/QuotationEditorPage'));
const ProductsListPage = lazy(() => import('./pages/ProductsListPage'));
const UserManagementPage = lazy(() => import('./pages/UserManagementPage'));
const ProfilePage = lazy(() => import('./pages/ProfilePage'));
const PurchaseInvoicesListPage = lazy(() => import('./pages/PurchaseInvoicesListPage'));
const PurchaseInvoiceEditorPage = lazy(() => import('./pages/PurchaseInvoiceEditorPage'));
const SalesInvoicesListPage = lazy(() => import('./pages/SalesInvoicesListPage'));
const SalesInvoiceEditorPage = lazy(() => import('./pages/SalesInvoiceEditorPage'));
const PermissionsPage = lazy(() => import('./pages/PermissionsPage'));
const ManagementPage = lazy(() => import('./pages/ManagementPage'));
const AccountsListPage = lazy(() => import('./pages/AccountsListPage'));
const JournalEntriesListPage = lazy(() => import('./pages/JournalEntriesListPage'));
const ReceiptsListPage = lazy(() => import('./pages/ReceiptsListPage'));
const ReceiptEditorPage = lazy(() => import('./pages/ReceiptEditorPage'));
const PaymentVouchersListPage = lazy(() => import('./pages/PaymentVouchersListPage'));
const PaymentVoucherEditorPage = lazy(() => import('./pages/PaymentVoucherEditorPage'));
const PartyListPage = lazy(() => import('./pages/PartyListPage'));
const PartyStatementPage = lazy(() => import('./pages/PartyStatementPage'));

const componentMap: { [key: string]: React.ComponentType<any> } = {
    '/': DashboardPage,
    '/accounting': ManagementPage,
    '/accounts/chart-of-accounts': AccountsListPage,
    '/accounts/journal-entries': JournalEntriesListPage,
    '/accounts/receipts': ReceiptsListPage,
    '/accounts/receipts/:id/:mode?': ReceiptEditorPage,
    '/accounts/payment-vouchers': PaymentVouchersListPage,
    '/accounts/payment-vouchers/:id/:mode?': PaymentVoucherEditorPage,
    '/accounts/customers': (props: any) => <PartyListPage {...props} partyType="Customer" />,
    '/accounts/suppliers': (props: any) => <PartyListPage {...props} partyType="Supplier" />,
    '/accounts/customers/:id/statement': (props: any) => <PartyStatementPage {...props} partyType="Customer" />,
    '/accounts/suppliers/:id/statement': (props: any) => <PartyStatementPage {...props} partyType="Supplier" />,
    '/quotations': QuotationsListPage,
    '/quotations/:id/:mode?': QuotationEditorPage,
    '/invoices-hub': ManagementPage,
    '/sales-invoices': SalesInvoicesListPage,
    '/purchase-invoices': PurchaseInvoicesListPage,
    '/sales-invoices/:id/:mode?': SalesInvoiceEditorPage,
    '/purchase-invoices/:id/:mode?': PurchaseInvoiceEditorPage,
    '/management': ManagementPage,
    '/products': ProductsListPage,
    '/users': UserManagementPage,
    '/permissions': PermissionsPage,
    '/sessions': ActiveSessionsPage, // إضافة جديدة
    '/profile': ProfilePage,
};

const AppRoutes: React.FC = () => {
  const { currentUser, loading: authLoading } = useAuth();
  const { canAccessRoute } = usePermissions();

  if (authLoading) {
    return (
      <div className="flex h-screen w-full items-center justify-center"><Spinner /></div>
    );
  }
  
  // Create a set of all paths defined in the navigation config for quick lookup.
  const navConfigPaths = new Set<string>();
  navigationConfig.forEach(item => {
    navConfigPaths.add(item.path);
    if (item.children) {
      item.children.forEach(child => navConfigPaths.add(child.path));
    }
  });

  // Filter out routes that are already handled by the navigation config logic.
  // This is to add routes for editor/viewer pages that don't have a direct sidebar link.
  const detailPagePaths = Object.keys(componentMap).filter(path => !navConfigPaths.has(path));

  // This logic creates routes for main pages and sub-pages defined in navigationConfig.
  // It respects the permissions defined in the config.
  const allRoutes = navigationConfig.flatMap((navItem) => {
      const { path, title, children } = navItem;
      const Component = componentMap[path];

      // A user can access a parent route if they have the role for the parent itself,
      // OR if they have access to at least one of its children. This logic must match the Sidebar's logic.
      const hasChildAccess = children?.some(child => canAccessRoute(child)) ?? false;
      const canAccessParent = canAccessRoute(navItem) || hasChildAccess;

      const parentRoute = canAccessParent ? [
          <Route
              key={path}
              path={path}
              element={
                  <ProtectedRoute>
                      <Suspense fallback={<div className="flex h-full w-full items-center justify-center"><Spinner /></div>}>
                          <Layout title={title}>
                              {Component ? <Component /> : <NotFoundPage />}
                          </Layout>
                      </Suspense>
                  </ProtectedRoute>
              }
          />
      ] : [];

      const childRoutes = children?.flatMap((child) => {
          const { path: childPath, title: childTitle } = child;
          const ChildComponent = componentMap[childPath];
          return canAccessRoute(child) ? [
              <Route
                  key={childPath}
                  path={childPath}
                  element={
                      <ProtectedRoute>
                          <Suspense fallback={<div className="flex h-full w-full items-center justify-center"><Spinner /></div>}>
                              <Layout title={childTitle}>
                                  {ChildComponent ? <ChildComponent /> : <NotFoundPage />}
                              </Layout>
                          </Suspense>
                      </ProtectedRoute>
                  }
              />
          ] : [];
      }) ?? [];

      return [...parentRoute, ...childRoutes];
  });

  // This logic creates routes for the remaining pages (like editors, viewers, etc.).
  // It assumes they are protected and uses a generic title or derives it if possible.
  const otherRoutes = detailPagePaths.map(path => {
    const Component = componentMap[path];
    // A simple way to get a title: use the title of the parent section.
    const parentNavItem = navigationConfig.find(item => path.startsWith(item.path + '/') && item.path !== '/');
    const title = parentNavItem ? parentNavItem.title : "تفاصيل";

    return (
      <Route
        key={path}
        path={path}
        element={
          <ProtectedRoute>
            <Suspense fallback={<div className="flex h-full w-full items-center justify-center"><Spinner /></div>}>
              <Layout title={title}>
                {Component ? <Component /> : <NotFoundPage />}
              </Layout>
            </Suspense>
          </ProtectedRoute>
        }
      />
    );
  });

  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      {allRoutes}
      {otherRoutes}
      <Route path="/404" element={<NotFoundPage />} />
      <Route path="*" element={<Navigate to={currentUser ? "/" : "/login"} />} />
    </Routes>
  );
}


const App: React.FC = () => {
  return (
    <AuthProvider>
      <PermissionsProvider>
        <ProductProvider>
          <AccountProvider>
            <HashRouter>
              <SessionManager>
                <AppRoutes />
              </SessionManager>
            </HashRouter>
          </AccountProvider>
        </ProductProvider>
      </PermissionsProvider>
    </AuthProvider>
  );
};

export default App;
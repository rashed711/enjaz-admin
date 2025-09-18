import React, { Suspense, lazy } from 'react';
import { HashRouter, Routes, Route, Navigate, useLocation } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import { ProductProvider } from './contexts/ProductContext';
import { AccountProvider } from './contexts/AccountContext';
import { PermissionsProvider } from './contexts/PermissionsContext';
import LoginPage from './pages/LoginPage';
import NotFoundPage from './pages/NotFoundPage';
import SessionManager from './components/SessionManager';
import ProtectedRoute from './components/ProtectedRoute';
import Layout from './components/Layout';
import Spinner from './components/Spinner';
import { useAuth } from './hooks/useAuth';
import { navigationConfig } from './navigation';
import { usePermissions } from './hooks/usePermissions';

// The new AuthGuard component will centralize all authentication checks.
const AuthGuard: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { currentUser, loading } = useAuth();

  console.log('[AuthGuard] State update:', { loading, currentUser });

  // 1. While AuthProvider is resolving the session and profile, show a global spinner.
  if (loading) {
    console.log('[AuthGuard] Decision: Waiting for auth. Showing global spinner.');
    return <div className="flex h-screen w-full items-center justify-center bg-background"><Spinner /></div>;
  }

  // 2. If loading is done, but there's no user or the profile is incomplete (no role),
  // then we render a very simple router that ONLY shows the login page.
  if (!currentUser || !currentUser.role) {
    console.log('[AuthGuard] Decision: No user or incomplete profile. Rendering Login page.');
    return (
      <Routes>
        <Route path="/login" element={<LoginPage />} />
        {/* Any other path redirects to login */}
        <Route path="*" element={<Navigate to="/login" replace />} />
      </Routes>
    );
  }

  // 3. If we reach here, the user is fully authenticated. Render the main app.
  console.log('[AuthGuard] Decision: User is fully authenticated. Rendering main application.');
  return <>{children}</>;
};

const DashboardPage = lazy(() => import('./pages/DashboardPage'));
const ActiveSessionsPage = lazy(() => import('./pages/ActiveSessionsPage'));
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
    '/sessions': ActiveSessionsPage,
    '/profile': ProfilePage,
};

const MainAppRoutes: React.FC = () => {
  const { canAccessRoute } = usePermissions();
  const { currentUser } = useAuth(); // We can safely use this now.
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
      {/* Redirect away from login if already authenticated */}
      <Route path="/login" element={<Navigate to="/" replace />} />
      {allRoutes}
      {otherRoutes}
      <Route path="/404" element={<NotFoundPage />} />
      {/* Catch-all for authenticated users, redirects to dashboard */}
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
};

const App: React.FC = () => {
  return (
    <HashRouter>
      <AuthProvider>
        <SessionManager>
          {/*
            AuthGuard is the key to solving the reload problem.
            It waits for AuthProvider to finish loading the user and their profile.
            Only when the user is fully authenticated does it render its children.
            This prevents any other provider or component (like PermissionsProvider)
            from running with incomplete user data and crashing the app.
          */}
          <AuthGuard>
            <PermissionsProvider>
              <ProductProvider>
                <AccountProvider>
                  <MainAppRoutes />
                </AccountProvider>
              </ProductProvider>
            </PermissionsProvider>
          </AuthGuard>
        </SessionManager>
      </AuthProvider>
    </HashRouter>
  );
};

export default App;
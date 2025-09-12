
import React from 'react';
import { HashRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import { ProductProvider } from './contexts/ProductContext';
import LoginPage from './pages/LoginPage';
import DashboardPage from './pages/DashboardPage';
import QuotationsListPage from './pages/QuotationsListPage';
import QuotationEditorPage from './pages/QuotationEditorPage';
import UserManagementPage from './pages/UserManagementPage';
import ProductsListPage from './pages/ProductsListPage';
import ProfilePage from './pages/ProfilePage';
import NotFoundPage from './pages/NotFoundPage';
import ProtectedRoute from './components/ProtectedRoute';
import Layout from './components/Layout';
import { useAuth } from './hooks/useAuth';
import { ROLES } from './constants';

const AppRoutes: React.FC = () => {
  const { currentUser } = useAuth();

  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route 
        path="/" 
        element={
          <ProtectedRoute>
            <Layout title="لوحة التحكم">
              <DashboardPage />
            </Layout>
          </ProtectedRoute>
        } 
      />
       <Route 
        path="/profile"
        element={
          <ProtectedRoute>
            <Layout title="الملف الشخصي">
              <ProfilePage />
            </Layout>
          </ProtectedRoute>
        }
      />
      {currentUser && [ROLES.SALES_EMPLOYEE, ROLES.SALES_MANAGER, ROLES.CEO].includes(currentUser.role) && (
        <>
          <Route 
            path="/quotations" 
            element={
              <ProtectedRoute>
                <Layout title="عروض الأسعار">
                  <QuotationsListPage />
                </Layout>
              </ProtectedRoute>
            } 
          />
          <Route 
            path="/quotations/:id" 
            element={
              <ProtectedRoute>
                {/* Title will be set inside the component */}
                <Layout title=""> 
                  <QuotationEditorPage />
                </Layout>
              </ProtectedRoute>
            } 
          />
          <Route 
            path="/products" 
            element={
              <ProtectedRoute>
                <Layout title="المنتجات">
                  <ProductsListPage />
                </Layout>
              </ProtectedRoute>
            } 
          />
        </>
      )}
      
      {currentUser && [ROLES.CEO, ROLES.ACCOUNTING_MANAGER].includes(currentUser.role) && (
         <Route 
            path="/users" 
            element={
              <ProtectedRoute>
                <Layout title="إدارة المستخدمين">
                  <UserManagementPage />
                </Layout>
              </ProtectedRoute>
            } 
          />
      )}

      <Route path="/404" element={<NotFoundPage />} />
      <Route path="*" element={<Navigate to={currentUser ? "/" : "/login"} />} />
    </Routes>
  );
}


const App: React.FC = () => {
  return (
    <AuthProvider>
      <ProductProvider>
        <HashRouter>
          <AppRoutes />
        </HashRouter>
      </ProductProvider>
    </AuthProvider>
  );
};

export default App;
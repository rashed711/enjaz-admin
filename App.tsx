import React from 'react';
import { HashRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import { ProductProvider } from './contexts/ProductContext';
import LoginPage from './pages/LoginPage';
import NotFoundPage from './pages/NotFoundPage';
import ProtectedRoute from './components/ProtectedRoute';
import Layout from './components/Layout';
import { useAuth } from './hooks/useAuth';
import { navigationConfig } from './navigation';

const AppRoutes: React.FC = () => {
  const { currentUser } = useAuth();

  const userCanAccess = (roles: string[]) => {
      if (!currentUser) return false;
      return roles.includes(currentUser.role);
  }

  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      
      {navigationConfig.map(({ path, component: Component, roles, title, children }) => (
          userCanAccess(roles) && (
              <React.Fragment key={path}>
                  <Route 
                      path={path}
                      element={
                          <ProtectedRoute>
                              <Layout title={title}>
                                  <Component />
                              </Layout>
                          </ProtectedRoute>
                      }
                  />
                  {children?.map(({ path: childPath, component: ChildComponent, roles: childRoles, title: childTitle }) => (
                      userCanAccess(childRoles) && (
                           <Route 
                              key={childPath}
                              path={childPath}
                              element={
                                  <ProtectedRoute>
                                      <Layout title={childTitle}>
                                          <ChildComponent />
                                      </Layout>
                                  </ProtectedRoute>
                              }
                          />
                      )
                  ))}
              </React.Fragment>
          )
      ))}

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

import React from 'react';
import Sidebar from './Sidebar';
import Header from './Header';
import BottomNav from './BottomNav';

interface LayoutProps {
  children: React.ReactNode;
  title: string;
}

const Layout: React.FC<LayoutProps> = ({ children, title }) => {
  return (
    <div className="flex h-screen bg-light-bg text-dark-text">
      <Sidebar />
      <div className="flex-1 md:mr-64 flex flex-col h-screen">
        <Header title={title} />
        <main className="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 pb-20 md:pb-8">
          {children}
        </main>
        <BottomNav />
      </div>
    </div>
  );
};

export default Layout;

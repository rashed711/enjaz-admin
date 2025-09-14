import React, { useState } from 'react';
import Sidebar from './Sidebar';
import Header from './Header';

interface LayoutProps {
  children: React.ReactNode;
  title: string;
}

const Layout: React.FC<LayoutProps> = ({ children, title }) => {
  const [isMobileSidebarOpen, setMobileSidebarOpen] = useState(false);

  const toggleMobileSidebar = () => {
    setMobileSidebarOpen(!isMobileSidebarOpen);
  };

  const closeMobileSidebar = () => {
    setMobileSidebarOpen(false);
  };

  return (
    <div className="flex h-screen bg-background text-text-primary">
      <Sidebar isOpen={isMobileSidebarOpen} onClose={closeMobileSidebar} />
      {isMobileSidebarOpen && (
        <div 
          className="fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden"
          onClick={closeMobileSidebar}
        ></div>
      )}
      <div className="flex-1 md:mr-48 flex flex-col h-screen">
        <Header title={title} onMenuClick={toggleMobileSidebar} />
        <main className="flex-1 overflow-y-auto p-[5px]">
          {children}
        </main>
      </div>
    </div>
  );
};

export default Layout;

import React from 'react';
import { NavLink } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { canManageUsers } from '../utils/permissions';
import HomeIcon from './icons/HomeIcon';
import DocumentTextIcon from './icons/DocumentTextIcon';
import UsersIcon from './icons/UsersIcon';
import CubeIcon from './icons/CubeIcon';
import UserCircleIcon from './icons/UserCircleIcon';
import { ROLES } from '../constants';

const Sidebar: React.FC = () => {
  const { currentUser } = useAuth();

  const navLinkClasses = ({ isActive }: { isActive: boolean }) =>
    `flex items-center px-4 py-3 text-lg rounded-lg transition-colors duration-200 ${
      isActive
        ? 'bg-[#10B981] text-white'
        : 'text-dark-text hover:bg-gray-100'
    }`;

  return (
    <aside className="hidden md:flex w-64 bg-white p-6 flex-col fixed top-0 right-0 h-screen shadow-lg z-30 border-l border-border">
      <div className="flex justify-between items-center mb-10">
        <div className="text-center">
            <h1 className="text-2xl font-bold text-primary">انجاز</h1>
            <p className="text-muted-text text-sm">للتكنولوجيا والمقاولات</p>
        </div>
      </div>
      <nav className="flex flex-col space-y-4 flex-grow">
        <NavLink to="/" className={navLinkClasses}>
          <HomeIcon className="ml-3" />
          <span>لوحة التحكم</span>
        </NavLink>
        
        {currentUser && [ROLES.SALES_EMPLOYEE, ROLES.SALES_MANAGER, ROLES.CEO].includes(currentUser.role) && (
            <NavLink to="/quotations" className={navLinkClasses}>
                <DocumentTextIcon className="ml-3" />
                <span>عروض الأسعار</span>
            </NavLink>
        )}

        {currentUser && [ROLES.SALES_EMPLOYEE, ROLES.SALES_MANAGER, ROLES.CEO].includes(currentUser.role) && (
            <NavLink to="/products" className={navLinkClasses}>
                <CubeIcon className="ml-3" />
                <span>المنتجات</span>
            </NavLink>
        )}

        {canManageUsers(currentUser) && (
          <NavLink to="/users" className={navLinkClasses}>
            <UsersIcon className="ml-3" />
            <span>إدارة المستخدمين</span>
          </NavLink>
        )}
      </nav>
       <div className="pt-4 border-t border-border">
        <NavLink to="/profile" className={navLinkClasses}>
          <UserCircleIcon className="ml-3 h-5 w-5" />
          <span>الملف الشخصي</span>
        </NavLink>
      </div>
    </aside>
  );
};

export default Sidebar;
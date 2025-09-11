import React from 'react';
import { NavLink } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { canManageUsers } from '../utils/permissions';
import HomeIcon from './icons/HomeIcon';
import DocumentTextIcon from './icons/DocumentTextIcon';
import UsersIcon from './icons/UsersIcon';
import CubeIcon from './icons/CubeIcon';
import { ROLES } from '../constants';

const BottomNav: React.FC = () => {
  const { currentUser } = useAuth();

  const navLinkClasses = ({ isActive }: { isActive: boolean }) =>
    `flex flex-col items-center justify-center text-center px-2 py-1 rounded-md transition-colors duration-200 w-full ${
      isActive
        ? 'text-primary'
        : 'text-muted-text hover:text-dark-text'
    }`;
    
  if (!currentUser) return null;

  return (
    <nav className="md:hidden fixed bottom-0 left-0 right-0 bg-white shadow-[0_-2px_5px_rgba(0,0,0,0.05)] z-40">
      <div className="flex justify-around items-center h-16">
        <NavLink to="/" className={navLinkClasses}>
          <HomeIcon className="h-6 w-6 mb-1" />
          <span className="text-xs">الرئيسية</span>
        </NavLink>
        
        {currentUser && [ROLES.SALES_EMPLOYEE, ROLES.SALES_MANAGER, ROLES.CEO].includes(currentUser.role) && (
            <NavLink to="/quotations" className={navLinkClasses}>
                <DocumentTextIcon className="h-6 w-6 mb-1" />
                <span className="text-xs">العروض</span>
            </NavLink>
        )}

        {currentUser && [ROLES.SALES_EMPLOYEE, ROLES.SALES_MANAGER, ROLES.CEO].includes(currentUser.role) && (
            <NavLink to="/products" className={navLinkClasses}>
                <CubeIcon className="h-6 w-6 mb-1" />
                <span className="text-xs">المنتجات</span>
            </NavLink>
        )}

        {canManageUsers(currentUser) && (
          <NavLink to="/users" className={navLinkClasses}>
            <UsersIcon className="h-6 w-6 mb-1" />
            <span className="text-xs">المستخدمون</span>
          </NavLink>
        )}
      </div>
    </nav>
  );
};

export default BottomNav;

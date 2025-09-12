import React from 'react';
import { NavLink } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { navigationConfig } from '../navigation';
import UserCircleIcon from './icons/UserCircleIcon';

const Sidebar: React.FC = () => {
  const { currentUser, logout } = useAuth();

  const navLinkClasses = ({ isActive }: { isActive: boolean }) =>
    `flex items-center px-3 py-2.5 text-base rounded-lg transition-colors duration-200 group ${
      isActive
        ? 'bg-primary/10 text-primary font-semibold'
        : 'text-text-secondary hover:bg-gray-100 hover:text-text-primary'
    }`;

  const canShowLink = (roles: string[]) => {
    if (!currentUser) return false;
    return roles.includes(currentUser.role);
  }

  return (
    <aside className="hidden md:flex w-64 bg-sidebar p-4 flex-col fixed top-0 right-0 h-screen shadow-lg z-30 border-l border-border">
      <div className="flex items-center mb-10 pt-4 px-2">
         <UserCircleIcon className="w-10 h-10 text-text-secondary" />
        <div className="text-right mr-3">
            <h1 className="font-semibold text-text-primary">
                {currentUser?.name}
            </h1>
            <p className="text-text-secondary text-sm">{currentUser?.role}</p>
        </div>
      </div>
      <nav className="flex flex-col space-y-2 flex-grow">
        {navigationConfig.map(({ path, label, Icon, roles, inSidebar }) => (
          inSidebar && canShowLink(roles) && (
            <NavLink key={path} to={path} className={navLinkClasses}>
              <Icon className="ml-3 h-5 w-5" />
              <span>{label}</span>
            </NavLink>
          )
        ))}
      </nav>
       <div className="pt-4 border-t border-border">
          <button onClick={logout} className="flex items-center w-full px-3 py-2.5 text-base rounded-lg text-text-secondary hover:bg-gray-100 hover:text-text-primary">
            <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span>تسجيل الخروج</span>
          </button>
      </div>
    </aside>
  );
};

export default Sidebar;
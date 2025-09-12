import React from 'react';
import { NavLink } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { navigationConfig } from '../navigation';

const BottomNav: React.FC = () => {
  const { currentUser } = useAuth();

  const navLinkClasses = ({ isActive }: { isActive: boolean }) =>
    `flex flex-col items-center justify-center text-center px-2 py-1 rounded-md transition-colors duration-200 w-full ${
      isActive
        ? 'text-primary'
        : 'text-muted-text hover:text-dark-text'
    }`;
    
  if (!currentUser) return null;

  const canShowLink = (roles: string[]) => {
    return roles.includes(currentUser.role);
  }

  return (
    <nav className="md:hidden fixed bottom-0 left-0 right-0 bg-white shadow-[0_-2px_5px_rgba(0,0,0,0.05)] z-40">
      <div className="flex justify-around items-center h-16">
        {navigationConfig.map(({ path, label, Icon, roles, inBottomNav, bottomNavLabel }) => (
          inBottomNav && canShowLink(roles) && (
            <NavLink key={path} to={path} className={navLinkClasses}>
              <Icon className="h-6 w-6 mb-1" />
              <span className="text-xs">{bottomNavLabel || label}</span>
            </NavLink>
          )
        ))}
      </div>
    </nav>
  );
};

export default BottomNav;
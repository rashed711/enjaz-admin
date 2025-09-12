import React from 'react';
import { NavLink } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { navigationConfig } from '../navigation';

const Sidebar: React.FC = () => {
  const { currentUser } = useAuth();

  const navLinkClasses = ({ isActive }: { isActive: boolean }) =>
    `flex items-center px-4 py-3 text-lg rounded-lg transition-colors duration-200 ${
      isActive
        ? 'bg-[#10B981] text-white'
        : 'text-dark-text hover:bg-gray-100'
    }`;

  const canShowLink = (roles: string[]) => {
    if (!currentUser) return false;
    return roles.includes(currentUser.role);
  }

  return (
    <aside className="hidden md:flex w-64 bg-white p-6 flex-col fixed top-0 right-0 h-screen shadow-lg z-30 border-l border-border">
      <div className="flex justify-between items-center mb-10">
        <div className="text-center">
            <h1 className="text-2xl font-bold text-primary">انجاز</h1>
            <p className="text-muted-text text-sm">للتكنولوجيا والمقاولات</p>
        </div>
      </div>
      <nav className="flex flex-col space-y-4 flex-grow">
        {navigationConfig.map(({ path, label, Icon, roles, inSidebar }) => (
          inSidebar && canShowLink(roles) && (
            <NavLink key={path} to={path} className={navLinkClasses}>
              <Icon className="ml-3" />
              <span>{label}</span>
            </NavLink>
          )
        ))}
      </nav>
       <div className="pt-4 border-t border-border">
        {/* Profile link is handled separately in navigationConfig, so we filter for it */}
        {navigationConfig.filter(l => l.path === '/profile').map(({ path, label, Icon, roles }) => (
            canShowLink(roles) && (
                 <NavLink key={path} to={path} className={navLinkClasses}>
                    <Icon className="ml-3 h-5 w-5" />
                    <span>{label}</span>
                </NavLink>
            )
        ))}
      </div>
    </aside>
  );
};

export default Sidebar;

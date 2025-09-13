import React, { useState, useEffect } from 'react';
import { NavLink, Link, useLocation } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { navigationConfig } from '../navigation';
import UserCircleIcon from './icons/UserCircleIcon';
import ChevronDownIcon from './icons/ChevronDownIcon';

const Sidebar: React.FC = () => {
  const { currentUser, logout } = useAuth();
  const location = useLocation();
  const [openSubMenu, setOpenSubMenu] = useState<string | null>(null);

  useEffect(() => {
    const parent = navigationConfig.find(item => 
      item.children?.some(child => location.pathname.startsWith(child.path))
    );
    if (parent) {
      setOpenSubMenu(parent.path);
    }
  }, [location.pathname]);

  const toggleSubMenu = (path: string) => {
    setOpenSubMenu(openSubMenu === path ? null : path);
  };

  const navLinkClasses = "flex items-center px-3 py-2.5 text-base rounded-lg transition-colors duration-200 group text-text-secondary hover:bg-gray-100 hover:text-text-primary";
  const activeNavLinkClasses = "bg-primary/10 text-primary font-semibold";

  const canShowLink = (roles: string[]) => {
    if (!currentUser) return false;
    return roles.includes(currentUser.role);
  }

  return (
    <aside className="hidden md:flex w-64 bg-sidebar p-4 flex-col fixed top-0 right-0 h-screen shadow-lg z-30 border-l border-border">
      <Link
        to="/profile"
        className="flex items-center mb-10 pt-4 px-2 rounded-lg hover:bg-gray-100 transition-colors duration-200"
        aria-label="الانتقال للملف الشخصي"
      >
         <UserCircleIcon className="w-10 h-10 text-text-secondary" />
        <div className="text-right mr-3">
            <h1 className="font-semibold text-text-primary">
                {currentUser?.name}
            </h1>
            <p className="text-text-secondary text-sm">{currentUser?.role}</p>
        </div>
      </Link>
      <nav className="flex flex-col space-y-2 flex-grow">
        {navigationConfig.map((item) => {
            const hasChildAccess = item.children?.some(child => canShowLink(child.roles)) ?? false;
            if (!item.inSidebar || !(canShowLink(item.roles) || hasChildAccess)) {
                return null;
            }

            // Any item with children that are meant for a sub-menu is a collapsible group.
            if (item.children && item.children.some(c => c.inSubMenu)) {
                return (
                <div key={item.path}>
                    <button 
                        onClick={() => toggleSubMenu(item.path)} 
                        className={`${navLinkClasses} w-full justify-between ${openSubMenu === item.path ? 'bg-gray-100' : ''}`}
                        aria-expanded={openSubMenu === item.path}
                        aria-controls={`submenu-${item.path}`}
                    >
                        <div className="flex items-center">
                            <item.Icon className="ml-3 h-5 w-5" />
                            <span>{item.label}</span>
                        </div>
                        <ChevronDownIcon className={`w-4 h-4 transition-transform ${openSubMenu === item.path ? 'rotate-180' : ''}`} />
                    </button>
                    {openSubMenu === item.path && (
                        <div id={`submenu-${item.path}`} className="mr-4 mt-2 space-y-2 border-r-2 border-border pr-3 animate-fade-in-scale origin-top">
                            {item.children.filter(child => child.inSubMenu && canShowLink(child.roles)).map(child => (
                            <NavLink
                                key={child.path}
                                to={child.path}
                                className={({ isActive }) => `${navLinkClasses} ${isActive ? activeNavLinkClasses : ''}`}
                            >
                                <span className="text-sm">{child.label}</span>
                            </NavLink>
                            ))}
                        </div>
                    )}
                </div>
                );
            }
            
            // All other items, even those with children for routing purposes, are direct links.
            return (
                <div key={item.path}>
                    <NavLink 
                        to={item.path} 
                        className={({ isActive }) => `${navLinkClasses} ${isActive ? activeNavLinkClasses : ''}`}
                        end={item.path === '/'}
                    >
                        <item.Icon className="ml-3 h-5 w-5" />
                        <span>{item.label}</span>
                    </NavLink>
                </div>
            );
        })}
      </nav>
       <div className="pt-4 border-t border-border">
          <button onClick={logout} className="flex items-center w-full px-3 py-2.5 text-base rounded-lg text-red-600 bg-red-50 hover:bg-red-100 font-semibold">
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

import React, { useState, useEffect } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { navigationConfig } from '../navigation';
import { Role } from '../types';
import ChevronDownIcon from './icons/ChevronDownIcon';

const Sidebar: React.FC = () => {
    const { currentUser } = useAuth();
    const location = useLocation();

    // Find the parent path of the current active route to keep the submenu open on page load/refresh
    const getActiveParentPath = () => {
        return navigationConfig.find(item => 
            item.children?.some(child => location.pathname.startsWith(child.path.split('/:')[0]))
        )?.path || null;
    };

    const [openSubmenus, setOpenSubmenus] = useState<Record<string, boolean>>({});

    useEffect(() => {
        const activeParent = getActiveParentPath();
        if (activeParent) {
            setOpenSubmenus(prev => ({ ...prev, [activeParent]: true }));
        }
    }, [location.pathname]);


    const toggleSubmenu = (path: string) => {
        setOpenSubmenus(prev => ({ ...prev, [path]: !prev[path] }));
    };

    const userCanAccess = (roles: Role[]) => {
        if (!currentUser) return false;
        // An empty roles array means the link's visibility is determined by its children.
        if (roles.length === 0) return true;
        return roles.includes(currentUser.role);
    };

    const baseLinkClasses = "flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-semibold transition-colors w-full";
    const activeLinkClasses = "bg-primary text-white";
    const inactiveLinkClasses = "text-text-secondary hover:bg-slate-100 hover:text-text-primary";
    
    const subNavLinkClasses = "flex items-center gap-3 pr-11 pl-4 py-2 rounded-lg text-sm transition-colors";
    const activeSubNavLinkClasses = "bg-slate-200 text-primary font-semibold";
    const inactiveSubNavLinkClasses = "text-text-secondary hover:bg-slate-100";

    return (
        <aside className="hidden md:flex w-56 bg-white border-l border-border p-4 flex-col flex-shrink-0">
            <div className="mb-8">
                <h1 className="text-2xl font-bold text-center text-primary">Enjaz</h1>
            </div>
            <nav className="flex-grow space-y-1">
                {navigationConfig.map((item) => {
                    if (!item.inSidebar) return null;

                    const hasChildren = item.children && item.children.some(child => child.inSubMenu);
                    const accessibleChildren = item.children?.filter(child => child.inSubMenu && userCanAccess(child.roles)) ?? [];
                    
                    if (!userCanAccess(item.roles) && accessibleChildren.length === 0) {
                        return null;
                    }

                    if (hasChildren) {
                        const isSubmenuOpen = openSubmenus[item.path];
                        const isActiveParent = getActiveParentPath() === item.path;
                        return (
                            <div key={item.path}>
                                <button
                                    onClick={() => toggleSubmenu(item.path)}
                                    className={`${baseLinkClasses} justify-between ${isActiveParent ? activeLinkClasses : inactiveLinkClasses}`}
                                >
                                    <div className="flex items-center gap-3">
                                        <item.Icon className="w-5 h-5" />
                                        <span>{item.label}</span>
                                    </div>
                                    <ChevronDownIcon className={`w-4 h-4 transition-transform ${isSubmenuOpen ? 'rotate-180' : ''}`} />
                                </button>
                                {isSubmenuOpen && (
                                    <div className="mt-1 space-y-1">
                                        {accessibleChildren.map(child => (
                                            <NavLink
                                                key={child.path}
                                                to={child.path}
                                                className={({ isActive }) => `${subNavLinkClasses} ${isActive ? activeSubNavLinkClasses : inactiveSubNavLinkClasses}`}
                                            >
                                                {child.label}
                                            </NavLink>
                                        ))}
                                    </div>
                                )}
                            </div>
                        );
                    }

                    return (
                        <NavLink
                            key={item.path}
                            to={item.path}
                            end={item.path === '/'}
                            className={({ isActive }) => `${baseLinkClasses} ${isActive ? activeLinkClasses : inactiveLinkClasses}`}
                        >
                            <item.Icon className="w-5 h-5" />
                            <span>{item.label}</span>
                        </NavLink>
                    );
                })}
            </nav>
        </aside>
    );
};

export default Sidebar;
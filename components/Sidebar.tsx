import React, { useState, useEffect } from 'react';
import { Link, useLocation, matchPath } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { navigationConfig } from '../navigation';
import { Role } from '../types';
import ChevronDownIcon from './icons/ChevronDownIcon';
import XIcon from './icons/XIcon';

interface SidebarProps {
    isOpen: boolean;
    onClose: () => void;
}

const Sidebar: React.FC<SidebarProps> = ({ isOpen, onClose }) => {
    const { currentUser } = useAuth();
    const location = useLocation();

    const [openSubmenus, setOpenSubmenus] = useState<Record<string, boolean>>({});

    useEffect(() => {
        const activeParentPath = navigationConfig.find(item => 
            item.children?.some(child => matchPath({ path: child.path, end: false }, location.pathname))
        )?.path;
        if (activeParentPath && !openSubmenus[activeParentPath]) {
            setOpenSubmenus(prev => ({ ...prev, [activeParentPath]: true }));
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

    // Define style objects for active state to ensure highest specificity
    const activeStyle: React.CSSProperties = {
        backgroundColor: '#10B981', // This is your theme's 'primary' color
        color: 'white',
    };

    const activeSubStyle: React.CSSProperties = {
        backgroundColor: '#e2e8f0', // slate-200
        color: '#10B981', // primary
        fontWeight: 600, // font-semibold
    };

    const baseLinkClasses = "flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-semibold transition-colors w-full";
    const inactiveLinkClasses = "text-text-secondary hover:bg-slate-100 hover:text-text-primary";
    const subNavLinkClasses = "flex items-center gap-3 pr-11 pl-4 py-2 rounded-lg text-sm transition-colors";
    const inactiveSubNavLinkClasses = "text-text-secondary hover:bg-slate-100";

    return (
        <aside className={`bg-white border-l border-border p-4 flex flex-col flex-shrink-0 transition-transform duration-300 ease-in-out fixed inset-y-0 right-0 z-50 w-64 transform md:relative md:w-48 md:translate-x-0 ${isOpen ? 'translate-x-0' : 'translate-x-full'}`}>
            <div className="mb-8 flex items-center justify-between">
                <h1 className="text-2xl font-bold text-primary">Enjaz</h1>
                <button onClick={onClose} className="md:hidden text-text-secondary p-1 rounded-full hover:bg-slate-100" aria-label="Close menu">
                    <XIcon className="w-6 h-6" />
                </button>
            </div>
            <nav className="flex-grow space-y-1 overflow-y-auto">
                {navigationConfig.map((item) => {
                    if (!item.inSidebar) return null;

                    // A more robust way to determine if a link or its children are active.
                    const isItemOrChildActive = !!(
                        // Check if the item's own path is active. `end: false` makes it match prefixes.
                        // For the dashboard ('/'), we want an exact match.
                        matchPath({ path: item.path, end: item.path === '/' }, location.pathname) ||
                        // Check if any of the item's children's paths are active.
                        item.children?.some(child => matchPath({ path: child.path, end: false }, location.pathname))
                    );

                    const hasChildren = item.children && item.children.some(child => child.inSubMenu);
                    const accessibleChildren = item.children?.filter(child => child.inSubMenu && userCanAccess(child.roles)) ?? [];
                    
                    if (!userCanAccess(item.roles) && accessibleChildren.length === 0) {
                        return null;
                    }

                    if (hasChildren) {
                        const isSubmenuOpen = openSubmenus[item.path] || isItemOrChildActive;
                        return (
                            <div key={item.path}>
                                <button
                                    onClick={() => toggleSubmenu(item.path)}
                                    className={`${baseLinkClasses} justify-between ${!isItemOrChildActive ? inactiveLinkClasses : ''}`}
                                    style={isItemOrChildActive ? activeStyle : {}}
                                >
                                    <div className="flex items-center gap-3">
                                        <item.Icon className="w-5 h-5" />
                                        <span>{item.label}</span>
                                    </div>
                                    <ChevronDownIcon className={`w-4 h-4 transition-transform ${isSubmenuOpen ? 'rotate-180' : ''}`} />
                                </button>
                                {isSubmenuOpen && (
                                    <div className="mt-1 space-y-1">
                                        {accessibleChildren.map(child => {
                                            const subIsActive = !!matchPath({ path: child.path, end: false }, location.pathname);
                                            return (
                                                <Link
                                                    key={child.path}
                                                    to={child.path}
                                                    onClick={onClose}
                                                    className={`${subNavLinkClasses} ${!subIsActive ? inactiveSubNavLinkClasses : ''}`}
                                                    style={subIsActive ? activeSubStyle : {}}
                                                >
                                                    {child.label}
                                                </Link>
                                            );
                                        })}
                                    </div>
                                )}
                            </div>
                        );
                    }

                    return (
                        <Link
                            key={item.path}
                            to={item.path}
                            onClick={onClose}
                            className={`${baseLinkClasses} ${!isItemOrChildActive ? inactiveLinkClasses : ''}`}
                            style={isItemOrChildActive ? activeStyle : {}}
                        >
                            <item.Icon className="w-5 h-5" />
                            <span>{item.label}</span>
                        </Link>
                    );
                })}
            </nav>
        </aside>
    );
};

export default Sidebar;
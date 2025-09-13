import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { navigationConfig } from '../navigation';
import { Role } from '../types';

const ManagementPage: React.FC = () => {
    const { currentUser } = useAuth();
    const location = useLocation();
    const managementNav = navigationConfig.find(item => item.path === location.pathname);
    
    if (!currentUser || !managementNav || !managementNav.children) {
        return <p>لا يمكن الوصول لهذه الصفحة.</p>;
    }

    const canShowLink = (roles: Role[]) => {
        if (!currentUser) return false;
        return roles.includes(currentUser.role);
    };

    const accessibleLinks = managementNav.children.filter(child => child.inSubMenu && canShowLink(child.roles));

    return (
        <div className="space-y-6">
            <div className="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                {accessibleLinks.map(({ path, label, Icon, title }) => (
                    <Link
                        key={path}
                        to={path}
                        className="bg-card p-4 rounded-lg shadow-sm border border-border flex flex-col items-center justify-center text-center hover:shadow-md hover:border-primary transition-all duration-200 group"
                    >
                        <div className="bg-primary/10 text-primary p-3 rounded-full mb-3 transition-transform group-hover:scale-110">
                            <Icon className="h-7 w-7" />
                        </div>
                        <h3 className="text-base font-bold text-text-primary group-hover:text-primary transition-colors">
                            {label}
                        </h3>
                        <p className="text-text-secondary text-xs mt-1">{title}</p>
                    </Link>
                ))}
            </div>
        </div>
    );
};

export default ManagementPage;
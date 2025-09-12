import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import UserCircleIcon from './icons/UserCircleIcon';

interface HeaderProps {
  title: string;
}

const Header: React.FC<HeaderProps> = ({ title }) => {
  const { currentUser } = useAuth();
  const location = useLocation();

  const profileLinkDestination = location.pathname === '/profile' ? '/' : '/profile';

  return (
    <header className="bg-white px-6 py-4 flex justify-between items-center flex-shrink-0 sticky top-0 z-10 border-b border-border">
      <h2 className="text-xl sm:text-2xl font-bold text-text-primary">{title}</h2>
      <Link 
        to={profileLinkDestination} 
        className="flex items-center p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200 cursor-pointer"
        aria-label={location.pathname === '/profile' ? 'العودة للوحة التحكم' : 'الانتقال للملف الشخصي'}
      >
        <div className="text-right mr-3 hidden sm:block">
          <p className="font-semibold text-text-primary">{currentUser?.name}</p>
          <p className="text-sm text-text-secondary">{currentUser?.role}</p>
        </div>
        <UserCircleIcon className="w-8 h-8 text-text-secondary" />
      </Link>
    </header>
  );
};

export default Header;
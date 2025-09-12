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
    <header className="bg-white p-4 flex justify-between items-center flex-shrink-0 border-b border-border sticky top-0 z-10">
      <h2 className="text-xl sm:text-2xl font-bold text-dark-text">{title}</h2>
      <Link 
        to={profileLinkDestination} 
        className="flex items-center p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200 cursor-pointer"
        aria-label={location.pathname === '/profile' ? 'العودة للوحة التحكم' : 'الانتقال للملف الشخصي'}
      >
        <div className="text-left ml-4">
          <p className="font-semibold text-dark-text hidden sm:block">{currentUser?.name}</p>
          <p className="text-sm text-muted-text hidden sm:block">{currentUser?.role}</p>
        </div>
        <UserCircleIcon className="w-8 h-8 text-gray-500" />
      </Link>
    </header>
  );
};

export default Header;

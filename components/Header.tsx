import React from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';

interface HeaderProps {
  title: string;
}

const Header: React.FC<HeaderProps> = ({ title }) => {
  const { currentUser, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  return (
    <header className="bg-white p-4 flex justify-between items-center flex-shrink-0 border-b border-border sticky top-0 z-10">
      <h2 className="text-xl sm:text-2xl font-bold text-dark-text">{title}</h2>
      <div className="flex items-center">
        <div className="text-left ml-4">
          <p className="font-semibold text-dark-text hidden sm:block">{currentUser?.name}</p>
          <p className="text-sm text-muted-text hidden sm:block">{currentUser?.role}</p>
        </div>
        <button
          onClick={handleLogout}
          className="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-red-500 transition-all duration-200"
        >
          تسجيل الخروج
        </button>
      </div>
    </header>
  );
};

export default Header;

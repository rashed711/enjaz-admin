import React, { useState, useRef, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import UserCircleIcon from './icons/UserCircleIcon';
import ChevronDownIcon from './icons/ChevronDownIcon';
import MenuIcon from './icons/MenuIcon';

interface HeaderProps {
  title: string;
  onMenuClick: () => void;
}

const Header: React.FC<HeaderProps> = ({ title, onMenuClick }) => {
  const { currentUser, logout } = useAuth();
  const navigate = useNavigate();
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const menuRef = useRef<HTMLDivElement>(null);

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
        setIsMenuOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, []);

  return (
    <header className="bg-white px-6 py-4 flex justify-between items-center flex-shrink-0 sticky top-0 z-30 border-b border-border">
      <div className="flex items-center gap-4">
        <button onClick={onMenuClick} className="text-text-secondary p-1 -mr-2 md:hidden" aria-label="Open menu">
          <MenuIcon className="w-6 h-6" />
        </button>
        <h2 className="text-xl sm:text-2xl font-bold text-text-primary">{title}</h2>
      </div>

      {currentUser && (
        <div className="relative" ref={menuRef}>
          <button 
            onClick={() => setIsMenuOpen(!isMenuOpen)}
            className="flex items-center gap-2 text-text-secondary hover:text-primary transition-colors"
          >
            <UserCircleIcon className="w-8 h-8" />
            <span className="hidden md:inline font-semibold">{currentUser.name}</span>
            <ChevronDownIcon className={`w-4 h-4 transition-transform ${isMenuOpen ? 'rotate-180' : ''}`} />
          </button>

          {isMenuOpen && (
            <div className="absolute left-0 mt-2 w-48 bg-card rounded-md shadow-lg py-1 z-40 border border-border">
              <Link
                to="/profile"
                onClick={() => setIsMenuOpen(false)}
                className="block px-4 py-2 text-sm text-text-primary hover:bg-slate-100 text-right"
              >
                الملف الشخصي
              </Link>
              <button
                onClick={handleLogout}
                className="block w-full text-right px-4 py-2 text-sm text-red-600 hover:bg-slate-100"
              >
                تسجيل الخروج
              </button>
            </div>
          )}
        </div>
      )}
    </header>
  );
};

export default Header;
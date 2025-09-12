import React from 'react';

interface HeaderProps {
  title: string;
}

const Header: React.FC<HeaderProps> = ({ title }) => {
  return (
    <header className="bg-white px-6 py-4 flex justify-between items-center flex-shrink-0 sticky top-0 z-10 border-b border-border">
      <h2 className="text-xl sm:text-2xl font-bold text-text-primary">{title}</h2>
    </header>
  );
};

export default Header;
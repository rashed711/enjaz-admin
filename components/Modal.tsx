import React, { useEffect } from 'react';
import XIcon from './icons/XIcon';

interface ModalProps {
  isOpen: boolean;
  onClose: () => void;
  title: string;
  children: React.ReactNode;
  footer?: React.ReactNode;
  size?: 'md' | 'lg' | 'xl';
}

const Modal: React.FC<ModalProps> = ({ isOpen, onClose, title, children, footer, size = 'lg' }) => {
  useEffect(() => {
    const handleEsc = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        onClose();
      }
    };
    if (isOpen) {
      window.addEventListener('keydown', handleEsc);
    }
    return () => {
      window.removeEventListener('keydown', handleEsc);
    };
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  const sizeClasses = {
      md: 'max-w-md',
      lg: 'max-w-lg',
      xl: 'max-w-xl',
  };

  return (
    <div 
        className="fixed inset-0 bg-gray-100 bg-opacity-75 backdrop-blur-sm flex justify-center items-center z-50 p-4 transition-opacity duration-300" 
        onClick={onClose} 
        role="dialog" 
        aria-modal="true"
        aria-labelledby="modal-title"
    >
      <div 
        className={`bg-card rounded-xl shadow-2xl w-full ${sizeClasses[size]} text-text-primary border border-border transform transition-all duration-300 scale-95 opacity-0 animate-fade-in-scale flex flex-col max-h-[90vh]`} 
        onClick={e => e.stopPropagation()}
      >
        <header className="flex justify-between items-center p-5 border-b border-border flex-shrink-0">
          <h2 id="modal-title" className="text-xl font-bold text-text-primary">{title}</h2>
          <button onClick={onClose} className="text-text-secondary hover:text-text-primary transition-colors p-1 rounded-full hover:bg-gray-100" aria-label="Close modal">
            <XIcon className="w-6 h-6" />
          </button>
        </header>

        <main className="p-6 overflow-y-auto">
            {children}
        </main>
        
        {footer && (
            <footer className="px-6 py-4 bg-slate-50/50 border-t border-border flex justify-end gap-4 rounded-b-xl flex-shrink-0">
                {footer}
            </footer>
        )}
      </div>
    </div>
  );
};

export default Modal;

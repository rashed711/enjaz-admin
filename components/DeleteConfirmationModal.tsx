import React from 'react';
import Spinner from './Spinner';

interface DeleteConfirmationModalProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: () => void;
  title: string;
  message: React.ReactNode;
  isProcessing: boolean;
  error: string | null;
}

const DeleteConfirmationModal: React.FC<DeleteConfirmationModalProps> = ({ isOpen, onClose, onConfirm, title, message, isProcessing, error }) => {
    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-60 flex justify-center items-center z-50 p-4" aria-modal="true" role="dialog">
            <div className="bg-white p-8 rounded-lg shadow-xl w-full max-w-md text-dark-text">
                <h2 className="text-2xl font-bold mb-4 text-dark-text text-center">{title}</h2>
                <div className="text-center text-muted-text mb-6">{message}</div>
                {error && <p className="text-red-500 text-xs mb-4 text-center">{error}</p>}
                <div className="mt-8 flex justify-center gap-4">
                    <button onClick={onClose} className="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-gray-400 transition-colors font-semibold" disabled={isProcessing}>
                        إلغاء
                    </button>
                    <button onClick={onConfirm} className="bg-red-600 text-white font-semibold px-6 py-2 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-red-500 transition-colors flex items-center justify-center gap-2 w-36" disabled={isProcessing}>
                        {isProcessing ? (
                            <>
                                <Spinner />
                                <span>جاري الحذف...</span>
                            </>
                        ) : 'تأكيد الحذف'}
                    </button>
                </div>
            </div>
        </div>
    );
};

export default DeleteConfirmationModal;

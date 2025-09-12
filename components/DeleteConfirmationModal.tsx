
import React from 'react';
import Spinner from './Spinner';
import Modal from './Modal';

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
    
    const footer = (
        <>
            <button onClick={onClose} className="bg-gray-200 text-gray-800 px-6 py-2 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card focus:ring-gray-500 font-semibold" disabled={isProcessing}>
                إلغاء
            </button>
            <button onClick={onConfirm} className="bg-red-600 text-white font-semibold px-6 py-2 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card focus:ring-red-500 flex items-center justify-center gap-2 w-36" disabled={isProcessing}>
                {isProcessing ? (
                    <>
                        <Spinner />
                        <span>جاري الحذف...</span>
                    </>
                ) : 'تأكيد الحذف'}
            </button>
        </>
    );

    return (
        <Modal
            isOpen={isOpen}
            onClose={onClose}
            title={title}
            footer={footer}
        >
            <div className="text-center text-text-secondary">{message}</div>
            {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
        </Modal>
    );
};

export default DeleteConfirmationModal;
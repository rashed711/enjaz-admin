

import React, { useState, useEffect } from 'react';
import { Role } from '../types';
import Spinner from './Spinner';
import Modal from './Modal';

export type UserFormData = {
  id?: string;
  name: string;
  email: string;
  role: Role;
  password?: string;
};

interface UserModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSave: (data: UserFormData) => void;
  initialData: UserFormData | null;
  isSaving: boolean;
  error: string | null;
}

const UserModal: React.FC<UserModalProps> = ({ isOpen, onClose, onSave, initialData, isSaving, error }) => {
    const [formData, setFormData] = useState<UserFormData>({
        name: '', email: '', role: Role.CLIENT, password: ''
    });

    useEffect(() => {
        if (initialData) {
            setFormData(initialData);
        } else {
            setFormData({ name: '', email: '', role: Role.CLIENT, password: '' });
        }
    }, [initialData, isOpen]);


    const isNewUser = !initialData?.id;

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleSaveClick = () => {
        onSave(formData);
    };
    
    const inputClasses = "border border-border bg-white text-text-primary p-2 px-3 rounded-lg w-full text-right focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors";
    const labelClasses = "block text-sm font-medium mb-2 text-right text-text-secondary";

    const footer = (
        <>
            <button onClick={onClose} className="bg-gray-200 text-gray-800 px-6 py-2 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card focus:ring-gray-500 font-semibold" disabled={isSaving}>إلغاء</button>
            <button onClick={handleSaveClick} className="bg-primary text-white font-semibold px-6 py-2 rounded-lg hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card focus:ring-primary flex items-center justify-center gap-2 w-24" disabled={isSaving}>
                {isSaving && <Spinner />}
                {isSaving ? 'جاري الحفظ...' : 'حفظ'}
            </button>
        </>
    );

    return (
        <Modal
            isOpen={isOpen}
            onClose={onClose}
            title={isNewUser ? 'إضافة مستخدم جديد' : 'تعديل المستخدم'}
            footer={footer}
        >
            <div className="space-y-5">
                <div>
                    <label htmlFor="name" className={labelClasses}>الاسم الكامل</label>
                    <input type="text" id="name" name="name" value={formData.name} onChange={handleChange} className={inputClasses}/>
                </div>
                <div>
                    <label htmlFor="email" className={labelClasses}>البريد الإلكتروني</label>
                    <input type="email" id="email" name="email" value={formData.email} onChange={handleChange} className={inputClasses} />
                </div>
                {isNewUser && (
                    <div>
                        <label htmlFor="password" className={labelClasses}>كلمة المرور</label>
                        <input type="password" id="password" name="password" value={formData.password || ''} onChange={handleChange} className={inputClasses}/>
                    </div>
                )}
                <div>
                     <label htmlFor="role" className={labelClasses}>الدور</label>
                    <select id="role" name="role" value={formData.role} onChange={handleChange} className={inputClasses}>
                        {Object.values(Role).map(role => (
                            <option key={role} value={role}>{role}</option>
                        ))}
                    </select>
                </div>
            </div>
            {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
        </Modal>
    );
};

export default UserModal;
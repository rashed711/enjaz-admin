import React, { useState, useEffect } from 'react';
import { Role } from '../types';
import Spinner from './Spinner';

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

    if (!isOpen) return null;

    const isNewUser = !initialData?.id;

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleSaveClick = () => {
        onSave(formData);
    };
    
    const inputClasses = "border border-border bg-background text-text-primary p-3 rounded w-full text-right focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors";

    return (
        <div className="fixed inset-0 bg-black bg-opacity-70 flex justify-center items-center z-50 p-4">
            <div className="bg-card p-8 rounded-lg shadow-xl w-full max-w-md text-text-primary border border-border">
                <h2 className="text-2xl font-bold mb-6 text-text-primary text-center">
                    {isNewUser ? 'إضافة مستخدم جديد' : 'تعديل المستخدم'}
                </h2>
                <div className="space-y-4">
                    <input type="text" name="name" placeholder="الاسم الكامل" value={formData.name} onChange={handleChange} className={inputClasses}/>
                    <input type="email" name="email" placeholder="البريد الإلكتروني" value={formData.email} onChange={handleChange} className={inputClasses} />
                    {isNewUser && (
                        <input type="password" name="password" placeholder="كلمة المرور" value={formData.password || ''} onChange={handleChange} className={inputClasses}/>
                    )}
                     <select name="role" value={formData.role} onChange={handleChange} className={inputClasses}>
                        {Object.values(Role).map(role => (
                            <option key={role} value={role}>{role}</option>
                        ))}
                    </select>
                </div>
                {error && <p className="text-red-500 text-xs mt-4 text-center">{error}</p>}
                <div className="mt-8 flex justify-end gap-4">
                    <button onClick={onClose} className="bg-gray-200 text-gray-800 px-6 py-2 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card focus:ring-gray-500 transition-colors font-semibold" disabled={isSaving}>إلغاء</button>
                    <button onClick={handleSaveClick} className="bg-primary text-white font-semibold px-6 py-2 rounded-lg hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card focus:ring-primary transition-colors flex items-center justify-center gap-2 w-24" disabled={isSaving}>
                        {isSaving && <Spinner />}
                        {isSaving ? 'جاري الحفظ...' : 'حفظ'}
                    </button>
                </div>
            </div>
        </div>
    );
};

export default UserModal;


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
  confirmPassword?: string;
};

interface UserModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSave: (data: UserFormData) => void;
  initialData: UserFormData | null;
  isSaving: boolean;
  error: string | null;
  onResetPassword: (email: string) => void;
  isSendingResetLink: boolean;
}

const UserModal: React.FC<UserModalProps> = ({ isOpen, onClose, onSave, initialData, isSaving, error, onResetPassword, isSendingResetLink }) => {
    const [formData, setFormData] = useState<UserFormData>({
        name: '', email: '', role: Role.CLIENT, password: '', confirmPassword: ''
    });

    useEffect(() => {
        if (initialData) {
            setFormData({ ...initialData, password: '', confirmPassword: '' });
        } else {
            setFormData({ name: '', email: '', role: Role.CLIENT, password: '', confirmPassword: '' });
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
    
    const handleResetPasswordClick = () => {
        if (formData.email) {
            onResetPassword(formData.email);
        }
    };

    const inputClasses = "border border-border bg-white text-text-primary p-2 px-3 rounded-lg w-full text-right focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors";
    const labelClasses = "block text-sm font-medium mb-2 text-right text-text-secondary";

    const footer = (
        <>
            <button onClick={onClose} className="bg-gray-200 text-gray-800 px-6 py-2 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card focus:ring-gray-500 font-semibold" disabled={isSaving}>إلغاء</button>
            <button onClick={handleSaveClick} className="bg-green-600 text-white font-semibold px-6 py-2 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card focus:ring-green-600 flex items-center justify-center gap-2 w-32 shadow-md hover:shadow-lg" disabled={isSaving}>
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
            <div className="space-y-6">
                <div>
                    <label htmlFor="name" className={labelClasses}>الاسم الكامل</label>
                    <input type="text" id="name" name="name" value={formData.name} onChange={handleChange} className={inputClasses}/>
                </div>
                <div>
                    <label htmlFor="email" className={labelClasses}>البريد الإلكتروني</label>
                    <input type="email" id="email" name="email" value={formData.email} onChange={handleChange} className={inputClasses} disabled={!isNewUser} />
                </div>
                {isNewUser ? (
                    <div className="border-t border-border pt-5">
                        <p className="text-sm text-text-secondary text-center mb-4">يجب أن تكون كلمة المرور 6 أحرف على الأقل.</p>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="password" className={labelClasses}>كلمة المرور</label>
                                <input type="password" id="password" name="password" value={formData.password || ''} onChange={handleChange} className={inputClasses}/>
                            </div>
                            <div>
                                <label htmlFor="confirmPassword" className={labelClasses}>تأكيد كلمة المرور</label>
                                <input type="password" id="confirmPassword" name="confirmPassword" value={formData.confirmPassword || ''} onChange={handleChange} className={inputClasses}/>
                            </div>
                        </div>
                    </div>
                ) : (
                    <div className="border-t border-border pt-5">
                        <label className={labelClasses}>إدارة كلمة المرور</label>
                        <button onClick={handleResetPasswordClick} disabled={isSendingResetLink || isSaving} className="w-full bg-amber-100 text-amber-800 font-semibold px-5 py-2.5 rounded-lg hover:bg-amber-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card focus:ring-amber-500 flex items-center justify-center gap-2 disabled:opacity-60">
                            {isSendingResetLink ? <Spinner /> : null}
                            {isSendingResetLink ? 'جاري الإرسال...' : 'إرسال رابط إعادة تعيين كلمة المرور'}
                        </button>
                        <p className="text-xs text-text-secondary text-center mt-2">سيتم إرسال بريد إلكتروني للمستخدم يحتوي على رابط لتعيين كلمة مرور جديدة.</p>
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
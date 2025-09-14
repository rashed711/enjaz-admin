
import React from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';

const ProfilePage: React.FC = () => {
  const { currentUser, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  if (!currentUser) {
    return <p>Loading user data...</p>;
  }

  return (
    <div className="max-w-5xl mx-auto">
        <div className="bg-card p-6 sm:p-8 rounded-lg shadow-sm text-center border border-border">
            <h2 className="text-2xl sm:text-3xl font-bold text-text-primary mb-2">
                ملفك الشخصي
            </h2>
            <p className="text-text-secondary mb-8">
                هنا يمكنك عرض تفاصيل حسابك وتسجيل الخروج.
            </p>
            
            <div className="space-y-4 text-right border-t border-border pt-6">
                 <div className="flex justify-between items-center bg-slate-100 p-3 rounded-md">
                    <span className="font-bold text-text-primary">{currentUser.name}</span>
                    <span className="text-text-secondary">الاسم الكامل</span>
                </div>
                 <div className="flex justify-between items-center bg-slate-100 p-3 rounded-md">
                    <span className="font-bold text-text-primary">{currentUser.email}</span>
                     <span className="text-text-secondary">البريد الإلكتروني</span>
                </div>
                 <div className="flex justify-between items-center bg-slate-100 p-3 rounded-md">
                    <span className="font-bold text-text-primary">{currentUser.role}</span>
                     <span className="text-text-secondary">الدور</span>
                </div>
            </div>

            <div className="mt-8">
                <button
                onClick={handleLogout}
                className="w-full sm:w-auto bg-red-600 text-white px-8 py-3 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card focus:ring-red-500 transition-all duration-200 font-semibold"
                >
                تسجيل الخروج
                </button>
            </div>
        </div>
    </div>
  );
};

export default ProfilePage;

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
    <div className="max-w-2xl mx-auto">
        <div className="bg-white p-6 sm:p-8 rounded-lg shadow-md text-center">
            <h2 className="text-2xl sm:text-3xl font-bold text-dark-text mb-2">
                ملفك الشخصي
            </h2>
            <p className="text-muted-text mb-8">
                هنا يمكنك عرض تفاصيل حسابك وتسجيل الخروج.
            </p>
            
            <div className="space-y-4 text-right border-t border-border pt-6">
                 <div className="flex justify-between items-center bg-gray-50 p-3 rounded-md">
                    <span className="font-bold text-dark-text">{currentUser.name}</span>
                    <span className="text-muted-text">الاسم الكامل</span>
                </div>
                 <div className="flex justify-between items-center bg-gray-50 p-3 rounded-md">
                    <span className="font-bold text-dark-text">{currentUser.email}</span>
                     <span className="text-muted-text">البريد الإلكتروني</span>
                </div>
                 <div className="flex justify-between items-center bg-gray-50 p-3 rounded-md">
                    <span className="font-bold text-dark-text">{currentUser.role}</span>
                     <span className="text-muted-text">الدور</span>
                </div>
            </div>

            <div className="mt-8">
                <button
                onClick={handleLogout}
                className="w-full sm:w-auto bg-red-600 text-white px-8 py-3 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-red-500 transition-all duration-200 font-semibold"
                >
                تسجيل الخروج
                </button>
            </div>
        </div>
    </div>
  );
};

export default ProfilePage;

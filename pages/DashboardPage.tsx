import React from 'react';
import { useAuth } from '../hooks/useAuth';

const DashboardPage: React.FC = () => {
  const { currentUser } = useAuth();

  return (
    <>
      <div className="bg-white p-6 rounded-lg shadow-md">
        <h2 className="text-2xl sm:text-3xl font-bold text-dark-text mb-2">
          أهلاً بك، {currentUser?.name}!
        </h2>
        <p className="text-muted-text">
          هنا يمكنك الوصول إلى جميع الأدوات والمعلومات الخاصة بك. استخدم القائمة الجانبية للتنقل.
        </p>
      </div>
      
      <div className="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div className="bg-white p-6 rounded-lg shadow-md">
          <h3 className="font-bold text-lg text-[#10B981]">دورك الحالي</h3>
          <p className="text-2xl font-semibold text-dark-text mt-2">{currentUser?.role}</p>
        </div>
        <div className="bg-white p-6 rounded-lg shadow-md">
          <h3 className="font-bold text-lg text-[#10B981]">الوصول السريع</h3>
          <p className="text-muted-text mt-2">روابط سريعة لأهم المهام الخاصة بك.</p>
        </div>
        <div className="bg-white p-6 rounded-lg shadow-md">
          <h3 className="font-bold text-lg text-[#10B981]">آخر التحديثات</h3>
          <p className="text-muted-text mt-2">لا توجد تحديثات جديدة في الوقت الحالي.</p>
        </div>
      </div>
    </>
  );
};

export default DashboardPage;
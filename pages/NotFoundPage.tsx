import React from 'react';
import { Link } from 'react-router-dom';

const NotFoundPage: React.FC = () => {
  return (
    <div className="min-h-screen flex flex-col items-center justify-center bg-light-bg text-center p-4">
      <h1 className="text-6xl font-bold text-[#10B981]">404</h1>
      <h2 className="text-2xl font-semibold text-dark-text mt-4">الصفحة غير موجودة</h2>
      <p className="text-muted-text mt-2">عذراً، لم نتمكن من العثور على الصفحة التي تبحث عنها.</p>
      <Link
        to="/"
        className="mt-8 bg-[#10B981] text-white px-6 py-3 rounded-lg hover:bg-[#059669] transition-colors duration-300"
      >
        العودة إلى لوحة التحكم
      </Link>
    </div>
  );
};

export default NotFoundPage;
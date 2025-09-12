import React from 'react';
import { Link } from 'react-router-dom';

const NotFoundPage: React.FC = () => {
  return (
    <div className="min-h-screen flex flex-col items-center justify-center bg-background text-center p-4">
      <h1 className="text-6xl font-bold text-primary">404</h1>
      <h2 className="text-2xl font-semibold text-text-primary mt-4">الصفحة غير موجودة</h2>
      <p className="text-text-secondary mt-2">عذراً، لم نتمكن من العثور على الصفحة التي تبحث عنها.</p>
      <Link
        to="/"
        className="mt-8 bg-primary text-white px-6 py-3 rounded-lg hover:bg-primary-hover transition-colors duration-300"
      >
        العودة إلى لوحة التحكم
      </Link>
    </div>
  );
};

export default NotFoundPage;
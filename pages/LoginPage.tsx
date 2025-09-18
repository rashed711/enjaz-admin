import React, { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import Spinner from '../components/Spinner';
import { supabase } from '../services/supabaseClient'; // إضافة جديدة

const LoginPage: React.FC = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [message, setMessage] = useState<{ text: string; isError: boolean } | null>(null);
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();
  const location = useLocation();
  // نحصل على المستخدم الحالي وحالة التحميل من useAuth
  const { login, currentUser, loading: authLoading } = useAuth();

  const from = (location.state as any)?.from?.pathname || '/';

  useEffect(() => {
    // إذا انتهى التحقق من المصادقة ووجدنا مستخدمًا، أعد توجيهه
    if (!authLoading && currentUser) {
      navigate(from, { replace: true });
    }
  }, [currentUser, authLoading, navigate, from]);

  useEffect(() => {
    if (location.state?.message) {
      setMessage({ text: location.state.message, isError: location.state.isError ?? false });
    }
  }, [location.state]);

  // أثناء التحقق من وجود جلسة، نعرض شاشة تحميل لمنع ظهور وميض لصفحة تسجيل الدخول
  if (authLoading) {
    return <div className="min-h-screen flex items-center justify-center bg-background"><Spinner /></div>;
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setMessage(null);
    setLoading(true);

    // تم تعديل هذا السطر ليحصل على بيانات المستخدم أيضاً
    const { user, success, error: authError } = await login(email, password);
    
    if (success && user) {
      // --- إضافة جديدة: تسجيل الجلسة فوراً عند تسجيل الدخول ---
      try {
        await supabase.from('user_sessions').upsert({
          user_id: user.id,
          last_seen_at: new Date().toISOString(),
          // سيتم تحديث user_agent بواسطة أول heartbeat
        }, { onConflict: 'user_id' });
      } catch (sessionError) {
        // لا توقف عملية تسجيل الدخول بسبب هذا الخطأ، فقط قم بتسجيله
        console.error('Failed to create user session on login:', sessionError);
      }
      // --------------------------------------------------------
      navigate(from, { replace: true });
    } else {
      setMessage({ text: authError || 'البريد الإلكتروني أو كلمة المرور غير صحيحة.', isError: true });
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-background text-text-primary px-4">
      <div className="max-w-md w-full bg-card rounded-xl shadow-lg p-8 sm:p-10 m-4 border border-border">
        <div className="text-center mb-8">
          <h1 className="text-4xl font-bold text-green-600">أهلاً بك في انجاز</h1>
          <p className="text-text-secondary mt-2">قم بتسجيل الدخول للوصول إلى بوابتك</p>
        </div>
        <form onSubmit={handleSubmit}>
          <div className="mb-4">
            <label htmlFor="email" className="block text-text-primary text-sm font-bold mb-2 text-right">
              البريد الإلكتروني
            </label>
            <input
              type="email"
              id="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="example@enjaz.com"
              className="shadow-sm appearance-none border border-border bg-white rounded-lg w-full py-3 px-4 text-text-primary leading-tight focus:outline-none focus:ring-2 focus:ring-green-500 text-right"
              required
              autoComplete="email"
            />
          </div>
          <div className="mb-6">
            <label htmlFor="password" className="block text-text-primary text-sm font-bold mb-2 text-right">
              كلمة المرور
            </label>
            <input
              type="password"
              id="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="********"
              className="shadow-sm appearance-none border border-border bg-white rounded-lg w-full py-3 px-4 text-text-primary mb-3 leading-tight focus:outline-none focus:ring-2 focus:ring-green-500 text-right"
              required
              autoComplete="current-password"
            />
          </div>
          {message && (
            <p className={`text-sm mb-4 text-center ${message.isError ? 'text-red-500' : 'text-blue-500'}`}>
              {message.text}
            </p>
          )}
          <div className="flex items-center justify-between">
            <button
              type="submit"
              disabled={loading}
              className="w-full bg-gradient-to-br from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold py-3 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background focus:ring-green-600 disabled:bg-green-600/70 flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition-all"
            >
              {loading && <Spinner />}
              {loading ? 'جاري التحقق...' : 'تسجيل الدخول'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default LoginPage;
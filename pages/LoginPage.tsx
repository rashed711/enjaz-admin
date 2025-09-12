import React, { useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import Spinner from '../components/Spinner';

const LoginPage: React.FC = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();
  const location = useLocation();
  const { login } = useAuth();

  const from = (location.state as any)?.from?.pathname || '/';

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    const { success, error: authError } = await login(email, password);
    
    if (success) {
      navigate(from, { replace: true });
    } else {
      setError(authError || 'البريد الإلكتروني أو كلمة المرور غير صحيحة.');
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
          {error && <p className="text-red-500 text-sm mb-4 text-center">{error}</p>}
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
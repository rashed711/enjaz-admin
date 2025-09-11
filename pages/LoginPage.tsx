import React, { useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';

const LoginPage: React.FC = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState(''); // Add state for password
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();
  const location = useLocation();
  const { login } = useAuth();

  const from = location.state?.from?.pathname || '/';

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
    <div className="min-h-screen flex items-center justify-center bg-light-bg text-dark-text">
      <div className="max-w-md w-full bg-white rounded-xl shadow-lg p-8 m-4">
        <div className="text-center mb-8">
          <h1 className="text-4xl font-bold text-[#10B981]">أهلاً بك في انجاز</h1>
          <p className="text-muted-text mt-2">قم بتسجيل الدخول للوصول إلى بوابتك</p>
        </div>
        <form onSubmit={handleSubmit}>
          <div className="mb-4">
            <label htmlFor="email" className="block text-dark-text text-sm font-bold mb-2 text-right">
              البريد الإلكتروني
            </label>
            <input
              type="email"
              id="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="example@enjaz.com"
              className="shadow-sm appearance-none border border-border bg-gray-50 rounded-lg w-full py-3 px-4 text-dark-text leading-tight focus:outline-none focus:ring-2 focus:ring-[#10B981] text-right"
              required
            />
          </div>
          <div className="mb-6">
            <label htmlFor="password" className="block text-dark-text text-sm font-bold mb-2 text-right">
              كلمة المرور
            </label>
            <input
              type="password"
              id="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="********"
              className="shadow-sm appearance-none border border-border bg-gray-50 rounded-lg w-full py-3 px-4 text-dark-text mb-3 leading-tight focus:outline-none focus:ring-2 focus:ring-[#10B981] text-right"
              required
            />
          </div>
          {error && <p className="text-red-500 text-xs italic mb-4 text-center">{error}</p>}
          <div className="flex items-center justify-between">
            <button
              type="submit"
              disabled={loading}
              className="w-full bg-[#10B981] hover:bg-[#059669] text-white font-bold py-3 px-4 rounded-lg focus:outline-none focus:shadow-outline transition-colors duration-300 disabled:bg-[#10B981]/70"
            >
              {loading ? 'جاري التحقق...' : 'تسجيل الدخول'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default LoginPage;
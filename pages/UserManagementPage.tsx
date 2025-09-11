import React, { useState, useEffect } from 'react';
import { supabase } from '../services/supabaseClient';
import { User, Role } from '../types';

// We can't fetch emails for all users from the client-side for security reasons.
// So we'll define a type for what we can display.
type DisplayUser = Omit<User, 'email'>;

// A type for the form data, including password for new users.
type UserFormData = {
  id?: string;
  name: string;
  email: string;
  role: Role;
  password?: string;
};

const UserManagementPage: React.FC = () => {
  const [users, setUsers] = useState<DisplayUser[]>([]);
  const [loading, setLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingUser, setEditingUser] = useState<UserFormData | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [isSaving, setIsSaving] = useState(false);


  const fetchUsers = async () => {
    setLoading(true);
    const { data, error } = await supabase
      .from('profiles')
      .select('id, name, role');
    
    if (error) {
      console.error('Error fetching users:', error);
      setError('Failed to load users.');
    } else {
      setUsers(data as DisplayUser[]);
    }
    setLoading(false);
  };

  useEffect(() => {
    fetchUsers();
  }, []);

  const openModal = (user: DisplayUser | null) => {
    if (user) {
      // For editing, we don't have the email, which is fine as we won't allow editing it.
      setEditingUser({ id: user.id, name: user.name, role: user.role, email: '' });
    } else {
      // For adding new user
      setEditingUser({ name: '', email: '', role: Role.CLIENT, password: '' });
    }
    setIsModalOpen(true);
  };

  const closeModal = () => {
    setIsModalOpen(false);
    setEditingUser(null);
    setError(null);
  };

  const handleSave = async () => {
    if (!editingUser) return;
    setIsSaving(true);
    setError(null);

    try {
      if (editingUser.id) {
        // Update user
        const { error: updateError } = await supabase
          .from('profiles')
          .update({ name: editingUser.name, role: editingUser.role })
          .eq('id', editingUser.id);

        if (updateError) throw updateError;
        
      } else {
        // Create new user
        if (!editingUser.email || !editingUser.password) {
            throw new Error('Email and password are required for new users.');
        }

        // 1. Create the user in auth.users
        const { data: authData, error: signUpError } = await supabase.auth.signUp({
            email: editingUser.email,
            password: editingUser.password,
        });

        if (signUpError) throw signUpError;
        if (!authData.user) throw new Error("User creation failed.");

        // 2. The trigger creates the profile, we just update it with name and role
        const { error: profileError } = await supabase
            .from('profiles')
            .update({ name: editingUser.name, role: editingUser.role })
            .eq('id', authData.user.id);
        
        if (profileError) {
          // This part is tricky. If this fails, we have an auth user without a proper profile.
          // For now, we just log the error. In a real app, this needs more robust handling.
          console.error("Failed to update profile for new user:", profileError);
          throw new Error("User was created, but setting the profile failed.");
        }
      }

      await fetchUsers(); // Refresh the list
      closeModal();

    } catch (e: any) {
        console.error('Save operation failed:', e);
        setError(e.message || 'An unexpected error occurred.');
    } finally {
        setIsSaving(false);
    }
  };
  
  const UserModal = () => {
    if (!isModalOpen || !editingUser) return null;

    const isNewUser = !editingUser.id;

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setEditingUser(prev => prev ? { ...prev, [name]: value } : null);
    };

    const inputClasses = "border border-border bg-gray-50 text-dark-text p-3 rounded w-full text-right focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors";

    return (
        <div className="fixed inset-0 bg-black bg-opacity-60 flex justify-center items-center z-50 p-4">
            <div className="bg-white p-8 rounded-lg shadow-xl w-full max-w-md text-dark-text">
                <h2 className="text-2xl font-bold mb-6 text-dark-text text-center">
                    {isNewUser ? 'إضافة مستخدم جديد' : 'تعديل المستخدم'}
                </h2>
                <div className="space-y-4">
                    <input type="text" name="name" placeholder="الاسم الكامل" value={editingUser.name} onChange={handleChange} className={inputClasses}/>
                    <input type="email" name="email" placeholder="البريد الإلكتروني" value={editingUser.email} onChange={handleChange} className={`${inputClasses} disabled:bg-gray-200 disabled:cursor-not-allowed`} disabled={!isNewUser} />
                    {isNewUser && (
                        <input type="password" name="password" placeholder="كلمة المرور" value={editingUser.password || ''} onChange={handleChange} className={inputClasses}/>
                    )}
                     <select name="role" value={editingUser.role} onChange={handleChange} className={inputClasses}>
                        {Object.values(Role).map(role => (
                            <option key={role} value={role}>{role}</option>
                        ))}
                    </select>
                </div>
                {error && <p className="text-red-500 text-xs mt-4 text-center">{error}</p>}
                <div className="mt-8 flex justify-end gap-4">
                    <button onClick={closeModal} className="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-gray-400 transition-colors font-semibold" disabled={isSaving}>إلغاء</button>
                    <button onClick={handleSave} className="bg-primary text-white font-semibold px-6 py-2 rounded-lg hover:bg-[#059669] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-primary transition-colors" disabled={isSaving}>
                        {isSaving ? 'جاري الحفظ...' : 'حفظ'}
                    </button>
                </div>
            </div>
        </div>
    );
  };


  return (
    <>
      {isModalOpen && <UserModal />}
      <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
          <h2 className="text-2xl font-bold text-dark-text">قائمة المستخدمين</h2>
          <button 
              onClick={() => openModal(null)}
              className="w-full sm:w-auto bg-primary text-white font-semibold px-5 py-2 rounded-lg hover:bg-[#059669] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-light-bg focus:ring-primary transition-all duration-200"
          >
              + إضافة مستخدم جديد
          </button>
      </div>

      {loading ? (
          <p className="p-4 text-center text-muted-text">جاري تحميل المستخدمين...</p>
      ) : users.length === 0 ? (
          <p className="p-4 text-center text-muted-text">لا يوجد مستخدمين لعرضهم.</p>
      ) : (
          <>
            {/* Desktop Table View */}
            <div className="bg-white rounded-lg shadow-md overflow-x-auto hidden md:block">
                <table className="w-full text-right min-w-[640px]">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="p-4 font-bold text-muted-text">الاسم</th>
                            <th className="p-4 font-bold text-muted-text">الدور</th>
                            <th className="p-4 font-bold text-muted-text"></th>
                        </tr>
                    </thead>
                    <tbody className="text-dark-text">
                        {users.map((user) => (
                            <tr key={user.id} className="border-b border-border hover:bg-gray-50 transition-colors duration-200">
                                <td className="p-4">{user.name}</td>
                                <td className="p-4">{user.role}</td>
                                <td className="p-4 text-center">
                                    <button onClick={() => openModal(user)} className="text-primary hover:underline font-semibold">
                                        تعديل
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Mobile Card View */}
            <div className="md:hidden space-y-4">
                {users.map(user => (
                    <div key={user.id} className="bg-white rounded-lg shadow p-4 flex justify-between items-center">
                        <div>
                            <p className="font-bold text-dark-text">{user.name}</p>
                            <p className="text-sm text-muted-text">{user.role}</p>
                        </div>
                        <button onClick={() => openModal(user)} className="bg-primary/10 text-primary px-4 py-1.5 rounded-md font-semibold text-sm hover:bg-primary/20 transition-colors">تعديل</button>
                    </div>
                ))}
            </div>
          </>
      )}
    </>
  );
};

export default UserManagementPage;

import React, { useState, useEffect } from 'react';
import { supabase } from '../services/supabaseClient';
import { User, Role } from '../types';
import UserModal, { UserFormData } from '../components/UserModal';
import DeleteConfirmationModal from '../components/DeleteConfirmationModal';

// We can't fetch emails for all users from the client-side for security reasons.
// So we'll define a type for what we can display.
type DisplayUser = Omit<User, 'email'>;

// --- UserManagementPage Component ---
const UserManagementPage: React.FC = () => {
  const [users, setUsers] = useState<DisplayUser[]>([]);
  const [loading, setLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingUser, setEditingUser] = useState<UserFormData | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [isSaving, setIsSaving] = useState(false);
  const [userToDelete, setUserToDelete] = useState<DisplayUser | null>(null);
  const [isDeleting, setIsDeleting] = useState(false);


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
      // For adding new user, prepare a blank form object
      setEditingUser({ name: '', email: '', role: Role.CLIENT, password: '' });
    }
    setIsModalOpen(true);
  };

  const closeModal = () => {
    setIsModalOpen(false);
    setEditingUser(null);
    setError(null);
  };

  const handleSave = async (userData: UserFormData) => {
    if (!userData) return;
    setIsSaving(true);
    setError(null);

    try {
        if (userData.id) {
            // --- UPDATE EXISTING USER ---
            const { error: updateError } = await supabase
                .from('profiles')
                .update({ name: userData.name, role: userData.role })
                .eq('id', userData.id);

            if (updateError) throw updateError;
            
            setUsers(currentUsers =>
                currentUsers.map(u =>
                    u.id === userData.id ? { ...u, name: userData.name, role: userData.role } : u
                )
            );
            closeModal();

        } else {
            // --- CREATE NEW USER ---
            if (!userData.email || !userData.password) {
                throw new Error('البريد الإلكتروني وكلمة المرور مطلوبان للمستخدمين الجدد.');
            }
            
            if (userData.password.length < 6) {
                throw new Error('يجب أن تكون كلمة المرور 6 أحرف على الأقل.');
            }

            const { data: signUpData, error: signUpError } = await supabase.auth.signUp({
                email: userData.email,
                password: userData.password,
                options: {
                    data: {
                        name: userData.name,
                        role: userData.role,
                    },
                },
            });

            if (signUpError) throw signUpError;
            
            if (signUpData.user) {
                const newUserForDisplay: DisplayUser = {
                    id: signUpData.user.id,
                    name: userData.name,
                    role: userData.role,
                };
                setUsers(currentUsers => [newUserForDisplay, ...currentUsers]);
            }
            
            closeModal();
        }

    } catch (e: any) {
        console.error('Save operation failed:', e);
        setError(e?.message || 'حدث خطأ غير متوقع.');

    } finally {
        setIsSaving(false);
    }
  };

  const handleConfirmDelete = async () => {
    if (!userToDelete) return;
    setIsDeleting(true);
    setError(null);

    const { error: functionError } = await supabase.functions.invoke('delete-user', {
        body: { userId: userToDelete.id },
    });

    if (functionError) {
        console.error('Error deleting user:', functionError);
        setError(functionError.message || 'فشل حذف المستخدم. قد لا تملك الصلاحيات الكافية أو أن المستخدم مرتبط ببيانات أخرى.');
        setIsDeleting(false); 
    } else {
        setUsers(currentUsers => currentUsers.filter(u => u.id !== userToDelete.id));
        setUserToDelete(null); 
        setIsDeleting(false);
        setError(null);
    }
  };

  return (
    <>
      <UserModal 
        isOpen={isModalOpen}
        onClose={closeModal}
        onSave={handleSave}
        initialData={editingUser}
        isSaving={isSaving}
        error={error}
      />
       <DeleteConfirmationModal
        isOpen={!!userToDelete}
        onClose={() => {
            setUserToDelete(null);
            setError(null);
        }}
        onConfirm={handleConfirmDelete}
        title="تأكيد الحذف"
        message={
            <>
                هل أنت متأكد أنك تريد حذف المستخدم <span className="font-bold text-dark-text">{userToDelete?.name}</span>؟ لا يمكن التراجع عن هذا الإجراء.
            </>
        }
        isProcessing={isDeleting}
        error={error}
      />
      <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
          <h2 className="text-2xl font-bold text-dark-text">قائمة المستخدمين</h2>
          <button 
              onClick={() => openModal(null)}
              className="w-full sm:w-auto bg-[#10B981] text-white font-semibold px-5 py-2 rounded-lg hover:bg-[#059669] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-light-bg focus:ring-[#10B981] transition-all duration-200"
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
                                     <button onClick={() => setUserToDelete(user)} className="text-red-500 hover:underline font-semibold mr-4">
                                        حذف
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
                        <div className="flex gap-2">
                             <button onClick={() => openModal(user)} className="bg-primary/10 text-primary px-4 py-1.5 rounded-md font-semibold text-sm hover:bg-primary/20 transition-colors">تعديل</button>
                             <button onClick={() => setUserToDelete(user)} className="bg-red-500/10 text-red-500 px-4 py-1.5 rounded-md font-semibold text-sm hover:bg-red-500/20 transition-colors">حذف</button>
                        </div>
                    </div>
                ))}
            </div>
          </>
      )}
    </>
  );
};

export default UserManagementPage;
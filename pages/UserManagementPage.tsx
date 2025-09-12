import React, { useState, useEffect } from 'react';
import { supabase } from '../services/supabaseClient';
import { User, Role } from '../types';
import UserModal, { UserFormData } from '../components/UserModal';
import DeleteConfirmationModal from '../components/DeleteConfirmationModal';
import UserCircleIcon from '../components/icons/UserCircleIcon';
import Spinner from '../components/Spinner';
import PencilIcon from '../components/icons/PencilIcon';
import TrashIcon from '../components/icons/TrashIcon';

// --- UserManagementPage Component ---
const UserManagementPage: React.FC = () => {
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingUser, setEditingUser] = useState<UserFormData | null>(null);
  const [pageError, setPageError] = useState<string | null>(null);
  const [modalError, setModalError] = useState<string | null>(null);
  const [notification, setNotification] = useState<string | null>(null);
  const [isSaving, setIsSaving] = useState(false);
  const [userToDelete, setUserToDelete] = useState<User | null>(null);
  const [isDeleting, setIsDeleting] = useState(false);


  const fetchUsers = async () => {
    setLoading(true);
    setPageError(null);

    try {
        const { data, error: profilesError } = await supabase
            .from('profiles')
            .select('id, name, role, email');

        if (profilesError) {
            throw profilesError;
        }

        if (data) {
            setUsers(data as User[]);
        }

    } catch (err: any) {
        console.error("Failed to load user list:", err);
        setPageError(`فشل تحميل قائمة المستخدمين. قد تكون هناك مشكلة في صلاحيات الوصول (RLS) على جدول 'profiles'.\n\nالخطأ الفني: ${err.message}`);
        setUsers([]);
    } finally {
        setLoading(false);
    }
  };


  useEffect(() => {
    fetchUsers();
  }, []);
  
  const showNotification = (message: string) => {
    setNotification(message);
    setTimeout(() => setNotification(null), 4000);
  };

  const openModal = (user: User | null) => {
    if (user) {
      setEditingUser({ id: user.id, name: user.name, role: user.role, email: user.email });
    } else {
      setEditingUser({ name: '', email: '', role: Role.CLIENT, password: '' });
    }
    setModalError(null); // Clear previous errors
    setIsModalOpen(true);
  };

  const closeModal = () => {
    setIsModalOpen(false);
    setEditingUser(null);
  };

  const createUser = async (userData: UserFormData) => {
    if (!userData.email || !userData.password) {
        throw new Error('البريد الإلكتروني وكلمة المرور مطلوبان للمستخدمين الجدد.');
    }
    
    if (userData.password.length < 6) {
        throw new Error('يجب أن تكون كلمة المرور 6 أحرف على الأقل.');
    }

    // `signUp` is correct for v2
    const { error: signUpError } = await supabase.auth.signUp({
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
    
    await fetchUsers();
  };

  const updateUser = async (userData: UserFormData) => {
    const originalUser = users.find(u => u.id === userData.id);
    if (!originalUser) throw new Error("المستخدم غير موجود.");

    const payload: { name?: string; role?: Role; email?: string } = {};
    if (originalUser.name !== userData.name && userData.name) payload.name = userData.name;
    if (originalUser.role !== userData.role) payload.role = userData.role;
    if (originalUser.email !== userData.email && userData.email) payload.email = userData.email;

    if (Object.keys(payload).length === 0) {
        return; // Nothing changed
    }

    if ('email' in payload) {
        // Use `getSession` which is the async method in supabase-js v2
        const { data: { session }, error: sessionError } = await supabase.auth.getSession();
        if (sessionError || !session) {
            throw new Error("المستخدم غير مسجل الدخول. يرجى إعادة تحميل الصفحة.");
        }

        const { error: functionError } = await supabase.functions.invoke('update-user', {
            headers: { 'Authorization': `Bearer ${session.access_token}` },
            body: { userId: userData.id, ...payload },
        });
        if (functionError) throw functionError;
    
    } else {
        const { error: updateError } = await supabase
            .from('profiles')
            .update(payload)
            .eq('id', userData.id);

        if (updateError) throw updateError;
    }
    
    await fetchUsers();
  };

  const handleSave = async (userData: UserFormData) => {
    if (!userData) return;
    setIsSaving(true);
    setModalError(null);

    try {
        if (userData.id) {
            await updateUser(userData);
            showNotification(`تم تحديث بيانات ${userData.name} بنجاح.`);
        } else {
            await createUser(userData);
            showNotification(`تم إنشاء المستخدم ${userData.name} بنجاح.`);
        }
        closeModal();
    } catch (e: any) {
        console.error('Save operation failed:', e);
        setModalError(e?.message || 'حدث خطأ غير متوقع.');
    } finally {
        setIsSaving(false);
    }
  };

  const handleConfirmDelete = async () => {
    if (!userToDelete) return;
    setIsDeleting(true);
    setPageError(null);

    try {
        // Use `getSession` which is the async method in supabase-js v2
        const { data: { session }, error: sessionError } = await supabase.auth.getSession();
        if (sessionError || !session) {
            throw new Error("المستخدم غير مسجل الدخول. يرجى إعادة تحميل الصفحة.");
        }

        const { error: functionError } = await supabase.functions.invoke('delete-user', {
            headers: {
                'Authorization': `Bearer ${session.access_token}`,
            },
            body: { userId: userToDelete.id },
        });

        if (functionError) {
            throw functionError;
        }
        
        setUsers(currentUsers => currentUsers.filter(u => u.id !== userToDelete.id));
        showNotification(`تم حذف المستخدم ${userToDelete.name} بنجاح.`);
        setUserToDelete(null); 

    } catch (e: any) {
        console.error('Error deleting user:', e);
        showNotification(`فشل حذف المستخدم: ${e.message || "حدث خطأ غير متوقع."}`);
    } finally {
        setIsDeleting(false);
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
        error={modalError}
      />
       <DeleteConfirmationModal
        isOpen={!!userToDelete}
        onClose={() => setUserToDelete(null)}
        onConfirm={handleConfirmDelete}
        title="تأكيد الحذف"
        message={
            <>
                هل أنت متأكد أنك تريد حذف المستخدم <span className="font-bold text-text-primary">{userToDelete?.name}</span>؟ لا يمكن التراجع عن هذا الإجراء.
            </>
        }
        isProcessing={isDeleting}
        error={null}
      />
      <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
          <h2 className="text-2xl font-bold text-text-primary">قائمة المستخدمين</h2>
          <button 
              onClick={() => openModal(null)}
              className="w-full sm:w-auto bg-[#4F46E5] text-white font-semibold px-5 py-2 rounded-lg hover:bg-[#4338CA] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background focus:ring-[#4F46E5] transition-all duration-200"
          >
              + إضافة مستخدم جديد
          </button>
      </div>

      {notification && (
        <div className="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 fixed top-24 right-1/2 translate-x-1/2 z-50 shadow-lg" role="alert">
            {notification}
        </div>
      )}

      {loading ? (
          <div className="flex justify-center items-center p-10">
              <Spinner />
          </div>
      ) : pageError ? (
           <p className="p-4 text-left text-red-500 bg-red-50/10 border border-red-500/30 rounded-lg whitespace-pre-wrap font-mono">{pageError}</p>
      ) : users.length === 0 ? (
          <div className="bg-card rounded-lg shadow-sm border border-border p-8 text-center">
            <p className="text-text-secondary">لا يوجد مستخدمين لعرضهم.</p>
          </div>
      ) : (
          <>
            {/* Desktop Table View */}
            <div className="bg-card rounded-lg shadow-sm overflow-x-auto hidden md:block border border-border">
                <table className="w-full text-right min-w-[640px]">
                    <thead className="border-b border-border bg-slate-50">
                        <tr>
                            <th className="p-5 font-bold text-text-secondary text-sm uppercase tracking-wider">الاسم</th>
                            <th className="p-5 font-bold text-text-secondary text-sm uppercase tracking-wider">البريد الإلكتروني</th>
                            <th className="p-5 font-bold text-text-secondary text-sm uppercase tracking-wider">الدور</th>
                            <th className="p-5 font-bold text-text-secondary text-sm uppercase tracking-wider text-center">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-border">
                        {users.map((user) => (
                            <tr key={user.id} className="hover:bg-gray-50 transition-colors duration-200">
                                <td className="p-5 whitespace-nowrap">
                                    <div className="font-semibold text-text-primary">{user.name}</div>
                                </td>
                                <td className="p-5 whitespace-nowrap text-text-secondary">{user.email}</td>
                                <td className="p-5 whitespace-nowrap text-text-secondary">{user.role}</td>
                                <td className="p-5 whitespace-nowrap text-center">
                                    <div className="flex items-center justify-center gap-2">
                                        <button onClick={() => openModal(user)} title="تعديل" className="p-2 text-primary rounded-full hover:bg-primary/10 transition-colors">
                                            <PencilIcon className="w-5 h-5" />
                                        </button>
                                        <button onClick={() => setUserToDelete(user)} title="حذف" className="p-2 text-red-500 rounded-full hover:bg-red-100 transition-colors">
                                            <TrashIcon className="w-5 h-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Mobile Card View */}
            <div className="md:hidden space-y-4">
                {users.map(user => (
                   <div key={user.id} className="bg-card rounded-lg shadow-sm p-4 border border-border">
                        <div className="flex justify-between items-start gap-2">
                            <div className="flex items-center gap-3">
                                <UserCircleIcon className="w-10 h-10 text-text-secondary" />
                                <div>
                                    <p className="font-bold text-text-primary">{user.name}</p>
                                    <p className="text-sm text-text-secondary">{user.role}</p>
                                </div>
                            </div>
                            <div className="flex gap-1">
                                <button onClick={() => openModal(user)} title="تعديل" className="p-2 text-primary rounded-full hover:bg-primary/10 active:bg-primary/20 transition-colors">
                                    <PencilIcon className="w-6 h-6" />
                                </button>
                                <button onClick={() => setUserToDelete(user)} title="حذف" className="p-2 text-red-500 rounded-full hover:bg-red-100 active:bg-red-200 transition-colors">
                                    <TrashIcon className="w-6 h-6" />
                                </button>
                            </div>
                        </div>
                        <div className="mt-3 pt-3 border-t border-border">
                            <p className="text-sm text-text-secondary text-right">{user.email}</p>
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
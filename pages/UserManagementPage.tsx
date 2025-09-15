import React, { useState, useEffect } from 'react';
import { supabase } from '../services/supabaseClient';
import { useAuth } from '../hooks/useAuth';
import { User, Role } from '../types';
import UserModal, { UserFormData } from '../components/UserModal';
import DeleteConfirmationModal from '../components/DeleteConfirmationModal';
import EmptyState from '../components/EmptyState';
import UserCircleIcon from '../components/icons/UserCircleIcon';
import UsersIcon from '../components/icons/UsersIcon';
import Spinner from '../components/Spinner';
import PencilIcon from '../components/icons/PencilIcon';
import TrashIcon from '../components/icons/TrashIcon';

// --- UserManagementPage Component ---
const UserManagementPage: React.FC = () => {
    const { currentUser, loading: isAuthLoading } = useAuth();
    const [users, setUsers] = useState<User[]>([]);
    const [loading, setLoading] = useState(true);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<UserFormData | null>(null);
    const [pageError, setPageError] = useState<string | null>(null);
    const [modalError, setModalError] = useState<string | null>(null);
    const [notification, setNotification] = useState<{ message: string; isError?: boolean } | null>(null);
    const [isSaving, setIsSaving] = useState(false);
    const [userToDelete, setUserToDelete] = useState<User | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [isSendingResetLink, setIsSendingResetLink] = useState(false);


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
            console.error("Failed to load user list:", err.message);
            setPageError(`فشل تحميل قائمة المستخدمين. قد تكون هناك مشكلة في صلاحيات الوصول (RLS) على جدول 'profiles'.\n\nالخطأ الفني: ${err.message}`);
            setUsers([]);
        } finally {
            setLoading(false);
        }
    };


    useEffect(() => {
        // Don't fetch until the auth state is confirmed
        if (isAuthLoading) {
            return;
        }
        // If auth is resolved and there's no user, stop loading and show empty state.
        if (!currentUser) {
            setLoading(false);
            setUsers([]);
            return;
        }
        fetchUsers();
    }, [currentUser, isAuthLoading]);

    const showNotification = (message: string, isError = false) => {
        setNotification({ message, isError });
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

        if (userData.password !== userData.confirmPassword) {
            throw new Error('كلمتا المرور غير متطابقتين.');
        }

        if (userData.password.length < 6) {
            throw new Error('يجب أن تكون كلمة المرور 6 أحرف على الأقل.');
        }

        // Get the current session to authorize the Edge Function call
        const { data: { session }, error: sessionError } = await supabase.auth.getSession();
        if (sessionError || !session) {
            throw new Error("لا يمكن الحصول على جلسة المستخدم. يرجى إعادة تحميل الصفحة.");
        }

        // Invoke the 'create-user' Edge Function
        const { error: functionError } = await supabase.functions.invoke('create-user', {
            headers: { 'Authorization': `Bearer ${session.access_token}` },
            body: {
                email: userData.email,
                password: userData.password,
                name: userData.name,
                role: userData.role,
            },
        });

        if (functionError) {
            throw new Error(`فشل إنشاء المستخدم: ${functionError.message}`);
        }

        await fetchUsers();
    };

    const updateUser = async (userData: UserFormData) => {
        const originalUser = users.find(u => u.id === userData.id);
        if (!originalUser) throw new Error("المستخدم غير موجود.");

        // Always build a payload with name and role to ensure data is synced to auth.users metadata.
        // This is crucial for fixing accounts with missing metadata, making the "Edit -> Save" flow reliable.
        const payload: { name: string; role: Role; email?: string } = {
            name: userData.name,
            role: userData.role,
        };

        // Only include email in the payload if it has actually changed.
        if (originalUser.email !== userData.email && userData.email) payload.email = userData.email;

        // Always use the edge function to handle updates to ensure atomicity
        const { data: { session }, error: sessionError } = await supabase.auth.getSession();
        if (sessionError || !session) {
            throw new Error("لا يمكن الحصول على جلسة المستخدم. يرجى إعادة تحميل الصفحة.");
        }

        const { error: functionError } = await supabase.functions.invoke('update-user', {
            headers: { 'Authorization': `Bearer ${session.access_token}` },
            body: { userId: userData.id, ...payload },
        });
        if (functionError) {
            throw new Error(`فشل تحديث المستخدم: ${functionError.message}`);
        }

        await fetchUsers();
    };

    const handleSave = async (userData: UserFormData) => {
        if (!userData) return;
        setIsSaving(true);
        setModalError(null);

        // Client-side validation to prevent submitting empty names
        if (!userData.name || userData.name.trim() === '') {
            setModalError('اسم المستخدم لا يمكن أن يكون فارغاً.');
            setIsSaving(false);
            return;
        }

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
            console.error('Save operation failed:', e.message);
            setModalError(e?.message || 'حدث خطأ غير متوقع.');
        } finally {
            setIsSaving(false);
        }
    };

    const handleResetPassword = async (email: string) => {
        if (!email) {
            setModalError('لا يوجد بريد إلكتروني لهذا المستخدم.');
            return;
        }
        setIsSendingResetLink(true);
        setModalError(null);

        const { error } = await supabase.auth.resetPasswordForEmail(email);

        if (error) {
            console.error('Error sending password reset email:', error.message);
            setModalError(`فشل إرسال البريد: ${error.message}`);
        } else {
            showNotification(`تم إرسال رابط إعادة تعيين كلمة المرور إلى ${email} بنجاح.`);
            closeModal();
        }
        setIsSendingResetLink(false);
    };

    const handleConfirmDelete = async () => {
        if (!userToDelete) return;
        setIsDeleting(true);
        setPageError(null);

        try {
            // Use supabase-js v2 `getSession()` method which is asynchronous.
            const { data: { session }, error: sessionError } = await supabase.auth.getSession();
            if (sessionError || !session) {
                throw new Error("لا يمكن الحصول على جلسة المستخدم. يرجى إعادة تحميل الصفحة.");
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
            console.error('Error deleting user:', e.message);
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
                onResetPassword={handleResetPassword}
                isSendingResetLink={isSendingResetLink}
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
                    className="w-full sm:w-auto bg-green-600 text-white font-semibold px-5 py-2 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background focus:ring-green-600 shadow-md hover:shadow-lg"
                >
                    + إضافة مستخدم جديد
                </button>
            </div>

            {notification && (
                <div className={`p-4 mb-4 text-sm rounded-lg fixed top-24 right-1/2 translate-x-1/2 z-50 shadow-lg ${notification.isError ? 'bg-red-50 text-red-800' : 'bg-green-50 text-green-800'}`} role="alert">
                    {notification.message}
                </div>
            )}

            {loading ? (
                <div className="flex justify-center items-center p-10">
                    <Spinner />
                </div>
            ) : pageError ? (
                <p className="p-4 text-left text-red-500 bg-red-50/10 border border-red-500/30 rounded-lg whitespace-pre-wrap font-mono">{pageError}</p>
            ) : users.length === 0 ? (
                <EmptyState
                    Icon={UsersIcon}
                    title="لا يوجد مستخدمين"
                    message="ابدأ بإضافة مستخدمين جدد إلى النظام وتعيين أدوارهم."
                    action={{
                        label: '+ إضافة مستخدم جديد',
                        onClick: () => openModal(null)
                    }}
                />
            ) : (
                <div className="hidden md:block bg-card rounded-lg shadow-sm border border-border overflow-x-auto">
                    <table className="w-full text-right min-w-[640px] text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-3 py-2 font-bold text-text-secondary sticky right-0 bg-slate-50 border-l border-border">الاسم</th>
                                <th className="px-3 py-2 font-bold text-text-secondary">البريد الإلكتروني</th>
                                <th className="px-3 py-2 font-bold text-text-secondary">الدور</th>
                                <th className="px-3 py-2 font-bold text-text-secondary text-left sticky left-0 bg-slate-50 border-r border-border">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody className="text-text-primary divide-y divide-border">
                            {users.map((user) => (
                                <tr key={user.id} className="hover:bg-slate-100 even:bg-slate-50/50">
                                    <td className="px-3 py-2 font-semibold whitespace-nowrap sticky right-0 bg-inherit border-l border-border">{user.name}</td>
                                    <td className="px-3 py-2 whitespace-nowrap">{user.email}</td>
                                    <td className="px-3 py-2 whitespace-nowrap">{user.role}</td>
                                    <td className="px-3 py-2 text-left sticky left-0 bg-inherit border-r border-border">
                                        <div className="flex items-center justify-end gap-2">
                                            <button onClick={() => openModal(user)} title="تعديل" className="p-2 bg-indigo-100 text-indigo-700 rounded-full hover:bg-indigo-200">
                                                <PencilIcon className="w-5 h-5" />
                                            </button>
                                            <button onClick={() => setUserToDelete(user)} title="حذف" className="p-2 bg-red-100 text-red-700 rounded-full hover:bg-red-200">
                                                <TrashIcon className="w-5 h-5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {/* --- Mobile Card View --- */}
            {!loading && !pageError && users.length > 0 && (
                <div className="md:hidden space-y-4">
                    {users.map((user) => (
                        <div key={user.id} className="bg-card border border-border rounded-lg p-4 shadow-sm even:bg-slate-50/50">
                            <div className="flex justify-between items-start mb-3">
                                <div className="flex items-center gap-3">
                                    <UserCircleIcon className="w-10 h-10 text-text-secondary" />
                                    <div>
                                        <p className="font-bold text-lg text-text-primary">{user.name}</p>
                                        <p className="text-sm text-text-secondary">{user.email}</p>
                                    </div>
                                </div>
                                <span className="text-sm font-semibold text-primary">{user.role}</span>
                            </div>
                            <div className="flex items-center justify-end gap-2 mt-4 border-t border-border pt-3">
                                <button onClick={() => openModal(user)} title="تعديل" className="p-2 bg-indigo-100 text-indigo-700 rounded-full hover:bg-indigo-200">
                                    <PencilIcon className="w-5 h-5" />
                                </button>
                                <button onClick={() => setUserToDelete(user)} title="حذف" className="p-2 bg-red-100 text-red-700 rounded-full hover:bg-red-200">
                                    <TrashIcon className="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </>
    );
};

export default UserManagementPage;

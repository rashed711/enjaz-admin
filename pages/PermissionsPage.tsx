import React, { useState, useEffect } from 'react';
import { Role, PermissionModule, PermissionAction, PermissionsConfig } from '../types';
import { usePermissionsConfig } from '../contexts/PermissionsContext';
import Spinner from '../components/Spinner';

// A map to associate permission actions with their Arabic labels for better maintainability.
const permissionActionLabels: Record<PermissionAction, string> = {
  [PermissionAction.CREATE]: 'إنشاء',
  [PermissionAction.VIEW_ALL]: 'عرض الكل',
  [PermissionAction.VIEW_OWN]: 'عرض الخاص به',
  [PermissionAction.EDIT_ALL]: 'تعديل الكل',
  [PermissionAction.EDIT_OWN]: 'تعديل الخاص به',
  [PermissionAction.DELETE_ALL]: 'حذف الكل',
  [PermissionAction.DELETE_OWN]: 'حذف الخاص به',
  [PermissionAction.CHANGE_STATUS]: 'تغيير الحالة',
  [PermissionAction.MANAGE]: 'إدارة كاملة',
};

// This helper function is moved outside the component to prevent re-creation on every render.
// It generates the display text for a set of permissions.
const getPermissionDisplay = (permissions: PermissionAction[] = []): React.ReactNode => {
  if (permissions.includes(PermissionAction.MANAGE)) {
    return <span className="font-bold text-primary">{permissionActionLabels[PermissionAction.MANAGE]}</span>;
  }
  if (permissions.length === 0) {
    return <span className="text-text-secondary">-</span>;
  }

  const labels: string[] = [];
  if (permissions.includes(PermissionAction.CREATE)) labels.push(permissionActionLabels[PermissionAction.CREATE]);
  if (permissions.includes(PermissionAction.VIEW_ALL)) labels.push(permissionActionLabels[PermissionAction.VIEW_ALL]);
  else if (permissions.includes(PermissionAction.VIEW_OWN)) labels.push(permissionActionLabels[PermissionAction.VIEW_OWN]);

  if (permissions.includes(PermissionAction.EDIT_ALL)) labels.push(permissionActionLabels[PermissionAction.EDIT_ALL]);
  else if (permissions.includes(PermissionAction.EDIT_OWN)) labels.push(permissionActionLabels[PermissionAction.EDIT_OWN]);

  if (permissions.includes(PermissionAction.DELETE_ALL)) labels.push(permissionActionLabels[PermissionAction.DELETE_ALL]);
  else if (permissions.includes(PermissionAction.DELETE_OWN)) labels.push(permissionActionLabels[PermissionAction.DELETE_OWN]);

  if (permissions.includes(PermissionAction.CHANGE_STATUS)) labels.push(permissionActionLabels[PermissionAction.CHANGE_STATUS]);
  
  return labels.join('، ');
};

const PermissionsPage: React.FC = () => {
  const roles = Object.values(Role);
  const modules = Object.values(PermissionModule);
  const { config: initialPermissions, loading, updateConfig } = usePermissionsConfig();
  const [editablePermissions, setEditablePermissions] = useState<PermissionsConfig>(initialPermissions);
  const [isSaving, setIsSaving] = useState(false);
  const [notification, setNotification] = useState<{ message: string; isError: boolean } | null>(null);

  // When the config is fetched from the database, update our local editable state.
  useEffect(() => {
    setEditablePermissions(JSON.parse(JSON.stringify(initialPermissions)));
  }, [initialPermissions]);

  const showNotification = (message: string, isError = false) => {
    setNotification({ message, isError });
    setTimeout(() => setNotification(null), 4000);
  };

  const handlePermissionChange = (role: Role, module: PermissionModule, action: PermissionAction, checked: boolean) => {
    setEditablePermissions(prevConfig => {
        // Deep copy to avoid direct mutation of the state
        const newConfig = JSON.parse(JSON.stringify(prevConfig));
        const currentPermissions = newConfig[role]?.[module] || [];

        let newPermissions: PermissionAction[];

        if (checked) {
            // Add the permission
            if (action === PermissionAction.MANAGE) {
                // If MANAGE is checked, it's the only permission
                newPermissions = [PermissionAction.MANAGE];
            } else {
                // Remove MANAGE if any other action is selected, then add the new action
                const filtered = currentPermissions.filter(p => p !== PermissionAction.MANAGE);
                newPermissions = [...new Set([...filtered, action])];
            }
        } else {
            // Remove the permission
            newPermissions = currentPermissions.filter(p => p !== action);
        }

        if (!newConfig[role]) {
            newConfig[role] = {};
        }
        newConfig[role]![module] = newPermissions;
        return newConfig;
    });
  };

  const handleSave = async () => {
    setIsSaving(true);
    const { success, error } = await updateConfig(editablePermissions);
    if (success) {
      showNotification('تم حفظ الصلاحيات بنجاح!');
    } else {
      showNotification(error || 'حدث خطأ غير متوقع.', true);
    }
    setIsSaving(false);
  };

  const handleReset = () => {
    setEditablePermissions(JSON.parse(JSON.stringify(initialPermissions)));
    showNotification('تم إعادة تعيين الصلاحيات إلى الإعدادات الأصلية.');
  };

  if (loading) {
    return (
        <div className="flex h-full w-full items-center justify-center p-10">
            <Spinner />
        </div>
    );
  }

  return (
    <div className="bg-card rounded-lg shadow-sm border border-border p-6">
      {notification && (
        <div className={`p-4 mb-4 text-sm rounded-lg fixed top-24 right-1/2 translate-x-1/2 z-50 shadow-lg ${notification.isError ? 'bg-red-50 text-red-800' : 'bg-green-50 text-green-800'}`} role="alert">
            {notification.message}
        </div>
      )}
      <div className="mb-6 border-b border-border pb-4">
        <h2 className="text-2xl font-bold text-text-primary">إدارة الصلاحيات</h2>
        <p className="text-text-secondary mt-1">
          هنا يمكنك تعديل الصلاحيات الممنوحة لكل دور في النظام. سيتم تطبيق التغييرات على الفور بعد الحفظ.
        </p>
      </div>
      
      <div className="hidden lg:block overflow-x-auto">
        <table className="w-full min-w-[1000px] text-right text-sm">
          <thead className="bg-slate-50">
            <tr>
              <th className="px-3 py-3 font-bold text-text-secondary sticky right-0 bg-slate-50 border-l border-border z-10">الدور</th>
              {modules.map(moduleName => (
                <th key={moduleName} className="px-3 py-3 font-bold text-text-secondary text-center">{moduleName}</th>
              ))}
            </tr>
          </thead>
          <tbody className="text-text-primary divide-y divide-border">
            {roles.map(roleName => (
              <tr key={roleName} className="hover:bg-slate-100 even:bg-slate-50/50">
                <td className="px-3 py-4 font-semibold sticky right-0 bg-inherit border-l border-border z-10">{roleName}</td>
                {modules.map(moduleName => {
                  const currentPermissions = editablePermissions[roleName]?.[moduleName] || [];
                  const hasManage = currentPermissions.includes(PermissionAction.MANAGE);
                  return (
                    <td key={`${roleName}-${moduleName}`} className="px-3 py-4 align-top">
                      <div className="grid grid-cols-2 gap-x-4 gap-y-2">
                        {Object.values(PermissionAction).map(action => (
                            <div key={action} className="flex items-center">
                                <input
                                    type="checkbox"
                                    id={`${roleName}-${moduleName}-${action}`}
                                    checked={currentPermissions.includes(action)}
                                    disabled={hasManage && action !== PermissionAction.MANAGE}
                                    onChange={(e) => handlePermissionChange(roleName, moduleName, action, e.target.checked)}
                                    className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary disabled:bg-gray-200"
                                />
                                <label htmlFor={`${roleName}-${moduleName}-${action}`} className={`mr-2 text-sm ${hasManage && action !== PermissionAction.MANAGE ? 'text-gray-400' : 'text-text-primary'}`}>
                                    {permissionActionLabels[action]}
                                </label>
                            </div>
                        ))}
                      </div>
                    </td>
                  )
                })}
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* --- Mobile Card View --- */}
      <div className="lg:hidden space-y-4">
        {roles.map(roleName => (
            <div key={roleName} className="bg-card border border-border rounded-lg p-4 shadow-sm even:bg-slate-50/50">
                <h3 className="font-bold text-lg text-primary mb-3 pb-3 border-b border-border">{roleName}</h3>
                <div className="space-y-4">
                    {modules.map(moduleName => {
                        const currentPermissions = editablePermissions[roleName]?.[moduleName] || [];
                        const hasManage = currentPermissions.includes(PermissionAction.MANAGE);
                        return (
                            <div key={`${roleName}-${moduleName}-mobile`}>
                                <p className="font-semibold text-text-primary mb-2">{moduleName}</p>
                                <div className="bg-slate-50 p-3 rounded-md grid grid-cols-2 gap-x-4 gap-y-3">
                                    {Object.values(PermissionAction).map(action => (
                                        <div key={action} className="flex items-center">
                                            <input
                                                type="checkbox"
                                                id={`${roleName}-${moduleName}-${action}-mobile`}
                                                checked={currentPermissions.includes(action)}
                                                disabled={hasManage && action !== PermissionAction.MANAGE}
                                                onChange={(e) => handlePermissionChange(roleName, moduleName, action, e.target.checked)}
                                                className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary disabled:bg-gray-200"
                                            />
                                            <label htmlFor={`${roleName}-${moduleName}-${action}-mobile`} className={`mr-2 text-sm ${hasManage && action !== PermissionAction.MANAGE ? 'text-gray-400' : 'text-text-primary'}`}>
                                                {permissionActionLabels[action]}
                                            </label>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )
                    })}
                </div>
            </div>
        ))}
      </div>

       <div className="mt-8 flex justify-end gap-4">
          <button onClick={handleReset} className="bg-gray-200 text-gray-800 font-semibold px-6 py-2 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card focus:ring-gray-500" disabled={isSaving}>إعادة تعيين</button>
          <button onClick={handleSave} className="bg-green-600 text-white font-semibold px-6 py-2 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card focus:ring-green-600 disabled:bg-green-600/50" disabled={isSaving}>
              {isSaving ? <><Spinner /> جاري الحفظ...</> : 'حفظ التغييرات'}
          </button>
      </div>

    </div>
  );
};

export default PermissionsPage;

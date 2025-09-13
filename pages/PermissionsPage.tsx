import React from 'react';
import { Role, PermissionModule, PermissionAction } from '../types';
import { permissionsConfig } from '../utils/permissionsConfig';

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

  return (
    <div className="bg-card rounded-lg shadow-sm border border-border p-6">
      <div className="mb-6 border-b border-border pb-4">
        <h2 className="text-2xl font-bold text-text-primary">إدارة الصلاحيات</h2>
        <p className="text-text-secondary mt-1">
          هنا يمكنك عرض الصلاحيات الممنوحة لكل دور في النظام. هذه الإعدادات حاليًا للقراءة فقط ومُعرفة في الكود.
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
              <tr key={roleName} className="hover:bg-slate-50">
                <td className="px-3 py-4 font-semibold sticky right-0 bg-card hover:bg-slate-50 border-l border-border z-10">{roleName}</td>
                {modules.map(moduleName => (
                  <td key={`${roleName}-${moduleName}`} className="px-3 py-4 text-center">
                    {getPermissionDisplay(permissionsConfig[roleName]?.[moduleName])}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* --- Mobile Card View --- */}
      <div className="lg:hidden space-y-4">
        {roles.map(roleName => (
            <div key={roleName} className="bg-card border border-border rounded-lg p-4 shadow-sm">
                <h3 className="font-bold text-lg text-primary mb-3 pb-3 border-b border-border">{roleName}</h3>
                <div className="space-y-4">
                    {modules.map(moduleName => (
                        <div key={`${roleName}-${moduleName}-mobile`}>
                            <p className="font-semibold text-text-primary mb-1">{moduleName}</p>
                            <div className="text-text-secondary bg-slate-50 p-2 rounded-md text-sm">
                                {getPermissionDisplay(permissionsConfig[roleName]?.[moduleName])}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        ))}
      </div>

       <div className="mt-6 text-left">
          <button 
              disabled 
              className="bg-primary text-white font-semibold px-6 py-2 rounded-lg disabled:bg-primary/50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card focus:ring-primary"
              title="سيتم تفعيل الحفظ عند ربط الصلاحيات بقاعدة البيانات"
          >
              حفظ التغييرات
          </button>
      </div>

    </div>
  );
};

export default PermissionsPage;

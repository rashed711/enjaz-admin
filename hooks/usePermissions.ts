import { useAuth } from './useAuth';
import { permissionsConfig } from '../utils/permissionsConfig';
import { PermissionModule, PermissionAction } from '../types';
import { useCallback, useMemo } from 'react';

export const usePermissions = () => {
  const { currentUser } = useAuth();

  const can = useCallback((module: PermissionModule, action: 'create' | 'view' | 'edit' | 'delete' | 'change_status' | 'manage', resourceOwnerId?: string): boolean => {
    if (!currentUser) return false;

    const role = currentUser.role;
    const permissions = permissionsConfig[role]?.[module] || [];

    if (permissions.includes(PermissionAction.MANAGE)) {
      return true;
    }
    
    switch (action) {
      case 'create':
        return permissions.includes(PermissionAction.CREATE);
        
      case 'manage':
        return permissions.includes(PermissionAction.MANAGE);
        
      case 'change_status':
        return permissions.includes(PermissionAction.CHANGE_STATUS);

      case 'view':
        if (permissions.includes(PermissionAction.VIEW_ALL)) return true;
        if (permissions.includes(PermissionAction.VIEW_OWN)) {
          // If no ID is passed, it means we're checking for the general capability (e.g., for UI rendering).
          // If an ID is passed, we must check ownership.
          return resourceOwnerId === undefined ? true : resourceOwnerId === currentUser.id;
        }
        return false;
        
      case 'edit':
        if (permissions.includes(PermissionAction.EDIT_ALL)) return true;
        if (permissions.includes(PermissionAction.EDIT_OWN)) {
          return resourceOwnerId === undefined ? true : resourceOwnerId === currentUser.id;
        }
        return false;

      case 'delete':
        if (permissions.includes(PermissionAction.DELETE_ALL)) return true;
        if (permissions.includes(PermissionAction.DELETE_OWN)) {
          return resourceOwnerId === undefined ? true : resourceOwnerId === currentUser.id;
        }
        return false;

      default:
        return false;
    }
  }, [currentUser]);

  return useMemo(() => ({ can }), [can]);
};

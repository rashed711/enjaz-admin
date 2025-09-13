
import { useAuth } from './useAuth';
import { usePermissionsConfig } from '../contexts/PermissionsContext';
import { PermissionModule, PermissionAction, Role } from '../types';
import { useCallback, useMemo } from 'react';

export const usePermissions = () => {
  const { currentUser } = useAuth();
  const { config: permissionsConfig } = usePermissionsConfig();

  const can = useCallback((module: PermissionModule, action: PermissionAction, resourceOwnerId?: string): boolean => {
    if (!currentUser) return false;

    const role = currentUser.role as Role;
    const permissions = permissionsConfig[role]?.[module] || [];

    if (permissions.includes(PermissionAction.MANAGE)) {
      return true;
    }
    
    // Direct action check
    if (permissions.includes(action)) {
        return true;
    }

    // Ownership-based checks
    const isOwner = resourceOwnerId !== undefined && resourceOwnerId === currentUser.id;

    if (action === PermissionAction.VIEW_ALL || action === PermissionAction.VIEW_OWN) {
        if (permissions.includes(PermissionAction.VIEW_ALL)) return true;
        return permissions.includes(PermissionAction.VIEW_OWN) && (resourceOwnerId === undefined || isOwner);
    }
    
    if (action === PermissionAction.EDIT_ALL || action === PermissionAction.EDIT_OWN) {
        if (permissions.includes(PermissionAction.EDIT_ALL)) return true;
        return permissions.includes(PermissionAction.EDIT_OWN) && (resourceOwnerId === undefined || isOwner);
    }

    if (action === PermissionAction.DELETE_ALL || action === PermissionAction.DELETE_OWN) {
        if (permissions.includes(PermissionAction.DELETE_ALL)) return true;
        return permissions.includes(PermissionAction.DELETE_OWN) && (resourceOwnerId === undefined || isOwner);
    }

    return false;
  }, [currentUser, permissionsConfig]);

  return useMemo(() => ({ can }), [can]);
};
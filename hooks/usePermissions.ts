
import { useAuth } from './useAuth';
import { usePermissionsConfig } from '../contexts/PermissionsContext';
import { PermissionModule, PermissionAction, Role, NavLink, NavLinkChild } from '../types';
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

  /**
   * Checks if the current user can access a given navigation route.
   * This centralizes the logic used by the Sidebar and App router.
   */
  const canAccessRoute = useCallback((item: NavLink | NavLinkChild): boolean => {
    if (!currentUser) return false;

    // 1. Check for specific permission requirements on the route item
    if (item.permission) {
        const [module, action] = item.permission;
        if (action === 'VIEW_ANY') {
            return can(module, PermissionAction.VIEW_ALL) || can(module, PermissionAction.VIEW_OWN);
        }
        return can(module, action);
    }

    // 2. Fallback to role-based check if no permission is specified
    // An empty roles array means the link is public to authenticated users or its visibility is determined by its children.
    if (item.roles.length === 0) return true;
    return item.roles.includes(currentUser.role);
  }, [currentUser, can]);

  return useMemo(() => ({ can, canAccessRoute }), [can, canAccessRoute]);
};
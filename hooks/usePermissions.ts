
import { useAuth } from './useAuth';
import { usePermissionsConfig } from '../contexts/PermissionsContext';
import { PermissionModule, PermissionAction, Role, NavLink, NavLinkChild } from '../types';
import { useCallback, useMemo } from 'react';

export const usePermissions = () => {
  const { currentUser } = useAuth();
  const { config: permissionsConfig } = usePermissionsConfig();

  const can = useCallback(
    (
      module: PermissionModule,
      action: PermissionAction,
      resourceOwnerId?: string,
    ): boolean => {
      if (!currentUser) return false;

      const role = currentUser.role as Role;
      const permissions = permissionsConfig[role]?.[module] || [];

      // Highest permission, always true
      if (permissions.includes(PermissionAction.MANAGE)) {
        return true;
      }

      // Check for blanket "ALL" permissions which grant "_OWN" variants too
      if (
        (action.startsWith('view') && permissions.includes(PermissionAction.VIEW_ALL)) ||
        (action.startsWith('edit') && permissions.includes(PermissionAction.EDIT_ALL)) ||
        (action.startsWith('delete') && permissions.includes(PermissionAction.DELETE_ALL))
      ) {
        return true;
      }

      // If we are checking for an "ALL" action and didn't pass above, it's a failure.
      if (action.endsWith('_all')) {
        return false;
      }

      // Now we are only checking for _OWN, CREATE, or CHANGE_STATUS actions.
      // Check if the role has the required permission.
      if (!permissions.includes(action)) {
        return false;
      }

      // If we are just checking for capability (no specific resource), it's true.
      if (resourceOwnerId === undefined) return true;

      // If we are checking a specific resource, verify ownership.
      return resourceOwnerId === currentUser.id;
    },
    [currentUser, permissionsConfig],
  );

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
        return can(module, action as PermissionAction);
    }

    // 2. Fallback to role-based check if no permission is specified
    // An empty roles array means the link is public to authenticated users or its visibility is determined by its children.
    if (item.roles.length === 0) return true;
    return item.roles.includes(currentUser.role);
  }, [currentUser, can]);

  return useMemo(() => ({ can, canAccessRoute }), [can, canAccessRoute]);
};
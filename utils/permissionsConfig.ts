import { Role, PermissionModule, PermissionAction, PermissionsConfig } from '../types';

export const permissionsConfig: PermissionsConfig = {
  [Role.CEO]: {
    [PermissionModule.QUOTATIONS]: [PermissionAction.VIEW_ALL, PermissionAction.EDIT_ALL, PermissionAction.CREATE, PermissionAction.DELETE_ALL],
    [PermissionModule.SALES_INVOICES]: [PermissionAction.VIEW_ALL, PermissionAction.EDIT_ALL, PermissionAction.CREATE, PermissionAction.DELETE_ALL, PermissionAction.CHANGE_STATUS],
    [PermissionModule.PURCHASE_INVOICES]: [PermissionAction.VIEW_ALL, PermissionAction.EDIT_ALL, PermissionAction.CREATE, PermissionAction.DELETE_ALL, PermissionAction.CHANGE_STATUS],
    [PermissionModule.PRODUCTS]: [PermissionAction.MANAGE],
    [PermissionModule.USERS]: [PermissionAction.MANAGE],
    [PermissionModule.PERMISSIONS]: [PermissionAction.MANAGE],
  },
  [Role.SALES_MANAGER]: {
    [PermissionModule.QUOTATIONS]: [PermissionAction.VIEW_ALL, PermissionAction.EDIT_ALL, PermissionAction.CREATE, PermissionAction.DELETE_ALL],
    [PermissionModule.SALES_INVOICES]: [PermissionAction.VIEW_ALL, PermissionAction.CREATE],
    [PermissionModule.PRODUCTS]: [PermissionAction.VIEW_ALL],
  },
  [Role.SALES_EMPLOYEE]: {
    [PermissionModule.QUOTATIONS]: [PermissionAction.VIEW_OWN, PermissionAction.EDIT_OWN, PermissionAction.CREATE, PermissionAction.DELETE_OWN],
    [PermissionModule.SALES_INVOICES]: [PermissionAction.VIEW_OWN, PermissionAction.CREATE],
    [PermissionModule.PRODUCTS]: [PermissionAction.VIEW_ALL],
  },
  [Role.ACCOUNTING_MANAGER]: {
    [PermissionModule.SALES_INVOICES]: [PermissionAction.VIEW_ALL, PermissionAction.CHANGE_STATUS],
    [PermissionModule.PURCHASE_INVOICES]: [PermissionAction.MANAGE],
    [PermissionModule.PRODUCTS]: [PermissionAction.MANAGE],
    [PermissionModule.USERS]: [PermissionAction.MANAGE],
  },
  [Role.ACCOUNTING_EMPLOYEE]: {
    [PermissionModule.PURCHASE_INVOICES]: [PermissionAction.VIEW_ALL, PermissionAction.EDIT_ALL, PermissionAction.CREATE],
  },
  [Role.CLIENT]: {
    // Clients have no permissions in this portal for now
  },
};

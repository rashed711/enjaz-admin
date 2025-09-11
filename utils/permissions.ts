
import { User, Role } from '../types';

export const canViewAllQuotations = (user: User | null): boolean => {
  if (!user) return false;
  return [Role.CEO, Role.SALES_MANAGER].includes(user.role);
};

export const canEditQuotations = (user: User | null): boolean => {
    if (!user) return false;
    return [Role.CEO, Role.SALES_MANAGER, Role.SALES_EMPLOYEE].includes(user.role);
};

export const canManageUsers = (user: User | null): boolean => {
    if (!user) return false;
    return [Role.CEO, Role.ACCOUNTING_MANAGER].includes(user.role);
}

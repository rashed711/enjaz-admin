import React, { useState, useEffect } from 'react';
import { useAuth } from '../hooks/useAuth';
import { supabase } from '../services/supabaseClient';
import { usePermissions } from '../hooks/usePermissions';
import { PermissionModule, PermissionAction } from '../types';
import UsersIcon from '../components/icons/UsersIcon';
import DocumentTextIcon from '../components/icons/DocumentTextIcon';
import ReceiptIcon from '../components/icons/ReceiptIcon';
import BuildingOfficeIcon from '../components/icons/BuildingOfficeIcon';
import DocumentDuplicateIcon from '../components/icons/DocumentDuplicateIcon';

interface StatCardProps {
  title: string;
  value: string | number;
  icon: React.ReactElement<{ className?: string }>;
}

const StatCard: React.FC<StatCardProps> = ({ title, value, icon }) => (
  <div className="bg-card p-4 rounded-lg shadow-sm flex items-center gap-4 border border-border">
    {React.cloneElement(icon, { className: "h-8 w-8 text-primary" })}
    <div className="text-right">
      <p className="text-2xl font-bold text-text-primary">{value}</p>
      <p className="text-sm text-text-secondary font-semibold mt-1">{title}</p>
    </div>
  </div>
);

const DashboardPage: React.FC = () => {
  const { currentUser, loading: isAuthLoading } = useAuth();
  const permissions = usePermissions();
  const [stats, setStats] = useState({
    quotations: 0,
    salesInvoices: 0,
    users: 0,
    purchaseInvoices: 0,
    companies: 0, // Added companies count
  });

  const canViewUsers = permissions.can(PermissionModule.USERS, PermissionAction.MANAGE);
  const canViewPurchaseInvoices = permissions.can(PermissionModule.PURCHASE_INVOICES, PermissionAction.VIEW_ALL) || permissions.can(PermissionModule.PURCHASE_INVOICES, PermissionAction.VIEW_OWN);
  const canViewAllQuotations = permissions.can(PermissionModule.QUOTATIONS, PermissionAction.VIEW_ALL);
  const canViewAnyQuotations = canViewAllQuotations || permissions.can(PermissionModule.QUOTATIONS, PermissionAction.VIEW_OWN);
  const canViewSalesInvoices = permissions.can(PermissionModule.SALES_INVOICES, PermissionAction.VIEW_ALL) || permissions.can(PermissionModule.SALES_INVOICES, PermissionAction.VIEW_OWN);

  useEffect(() => {
    const fetchDashboardStats = async () => {
        if (!currentUser || isAuthLoading) return;

        try {
            // Helper to get count and handle errors gracefully
            const getCount = (res: { count: number | null, error: any } | undefined, tableName: string) => {
                if (!res) return 0;
                if (res.error) {
                    if (res.error.message.includes('does not exist') || res.error.message.includes('in the schema cache')) {
                        console.warn(`Dashboard: Table '${tableName}' not found. Defaulting count to 0.`);
                        return 0;
                    }
                    console.error(`Error fetching count for ${tableName}:`, res.error);
                    return 0;
                }
                return res.count ?? 0;
            };
            
            // Helper to get unique companies count
            const getCompanyCount = (res: { data: { company: string }[] | null, error: any } | undefined, tableName: string) => {
                if (!res) return 0;
                if (res.error) {
                    if (res.error.message.includes('does not exist') || res.error.message.includes('in the schema cache')) {
                        console.warn(`Dashboard: Table '${tableName}' for company count not found. Defaulting to 0.`);
                        return 0;
                    }
                    console.error(`Error fetching companies from ${tableName}:`, res.error);
                    return 0;
                }
                if (!res.data) return 0;
                const uniqueCompanies = new Set(res.data.map(q => q.company).filter(Boolean)); // Filter out null/empty company names
                return uniqueCompanies.size;
            };

            // --- Define queries based on permissions ---

            // Quotations
            const quotationsQuery = canViewAnyQuotations
                ? supabase.from('quotations').select('*', { count: 'exact', head: true })
                : Promise.resolve(undefined);

            // Sales Invoices
            const salesInvoicesQuery = canViewSalesInvoices
                ? supabase.from('sales_invoices').select('*', { count: 'exact', head: true })
                : Promise.resolve(undefined);

            // Users
            const usersQuery = canViewUsers 
                ? supabase.from('profiles').select('*', { count: 'exact', head: true }) 
                : Promise.resolve(undefined);

            // Purchase Invoices
            const purchaseInvoicesQuery = canViewPurchaseInvoices
                ? supabase.from('purchase_invoices').select('*', { count: 'exact', head: true })
                : Promise.resolve(undefined);

            // Companies
            const companiesQuery = canViewAllQuotations 
                ? supabase.from('quotations').select('company') 
                : Promise.resolve(undefined);

            // Fetch all stats concurrently
            const [
                quotationsRes,
                salesInvoicesRes,
                usersRes,
                purchaseInvoicesRes,
                companiesRes
            ] = await Promise.all([
                quotationsQuery,
                salesInvoicesQuery,
                usersQuery,
                purchaseInvoicesQuery,
                companiesQuery
            ]);

            setStats({
                quotations: getCount(quotationsRes, 'quotations'),
                salesInvoices: getCount(salesInvoicesRes, 'sales_invoices'),
                users: getCount(usersRes, 'profiles'),
                purchaseInvoices: getCount(purchaseInvoicesRes, 'purchase_invoices'),
                companies: getCompanyCount(companiesRes, 'quotations'),
            });

        } catch (e) {
            console.error("An unexpected error occurred while fetching dashboard stats:", e);
        }
    };

    if (currentUser) {
        fetchDashboardStats();
    }
  }, [currentUser, isAuthLoading, canViewUsers, canViewPurchaseInvoices, canViewAllQuotations, canViewAnyQuotations, canViewSalesInvoices]);

  return (
    <div className="space-y-8">
      {/* Top Stat Cards */}
      <div className="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <StatCard title="عروض الأسعار" value={stats.quotations} icon={<DocumentTextIcon />} />
        <StatCard title="فواتير المبيعات" value={stats.salesInvoices} icon={<DocumentTextIcon />} />
        {canViewUsers && <StatCard title="المستخدمين" value={stats.users} icon={<UsersIcon />} />}
        {canViewPurchaseInvoices && <StatCard title="فواتير المشتريات" value={stats.purchaseInvoices} icon={<ReceiptIcon />} />}
        {canViewAllQuotations && <StatCard title="الشركات" value={stats.companies} icon={<BuildingOfficeIcon />} />}
      </div>
    </div>
  );
};

export default DashboardPage;
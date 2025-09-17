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
  loading?: boolean;
}

const StatCard: React.FC<StatCardProps> = ({ title, value, icon, loading }) => (
  <div className="bg-card p-4 rounded-lg shadow-sm flex items-center gap-4 border border-border">
    {React.cloneElement(icon, { className: "h-8 w-8 text-primary" })}
    <div className="text-right">
      {loading ? (
        <div className="h-7 w-12 bg-gray-200 rounded-md animate-pulse"></div>
      ) : (
        <p className="text-2xl font-bold text-text-primary">{value}</p>
      )}
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
  const [statsLoading, setStatsLoading] = useState(true);
  const [statsError, setStatsError] = useState<string | null>(null);

  const canViewUsers = permissions.can(PermissionModule.USERS, PermissionAction.MANAGE);
  const canViewPurchaseInvoices = permissions.can(PermissionModule.PURCHASE_INVOICES, PermissionAction.VIEW_ALL) || permissions.can(PermissionModule.PURCHASE_INVOICES, PermissionAction.VIEW_OWN);
  const canViewAllQuotations = permissions.can(PermissionModule.QUOTATIONS, PermissionAction.VIEW_ALL);
  const canViewAnyQuotations = canViewAllQuotations || permissions.can(PermissionModule.QUOTATIONS, PermissionAction.VIEW_OWN);
  const canViewSalesInvoices = permissions.can(PermissionModule.SALES_INVOICES, PermissionAction.VIEW_ALL) || permissions.can(PermissionModule.SALES_INVOICES, PermissionAction.VIEW_OWN);

    useEffect(() => {
        const fetchDashboardStats = async () => {
            if (!currentUser || isAuthLoading) return;

            setStatsLoading(true);
            setStatsError(null);

            try {
                // Call the RPC function to get all stats in one go.
                const { data, error } = await supabase.rpc('get_dashboard_stats');

                if (error) throw error;

                if (!data) {
                    throw new Error("لم يتم إرجاع أي بيانات من الدالة get_dashboard_stats.");
                }

                // Set stats based on permissions. Default to 0 if a stat is not returned or user lacks permission.
                setStats({
                    quotations: canViewAnyQuotations ? (data.quotations || 0) : 0,
                    salesInvoices: canViewSalesInvoices ? (data.salesInvoices || 0) : 0,
                    users: canViewUsers ? (data.users || 0) : 0,
                    purchaseInvoices: canViewPurchaseInvoices ? (data.purchaseInvoices || 0) : 0,
                    companies: canViewAllQuotations ? (data.companies || 0) : 0,
                });

            } catch (e) {
                console.error("An unexpected error occurred while fetching dashboard stats:", e);
                setStatsError(`فشل تحميل الإحصائيات. الخطأ: ${e.message}`);
            } finally {
                setStatsLoading(false);
            }
        };
        fetchDashboardStats();
    }, [currentUser, isAuthLoading, canViewUsers, canViewPurchaseInvoices, canViewAllQuotations, canViewAnyQuotations, canViewSalesInvoices]);

  return (
    <div className="space-y-8">
      {statsError && (
        <div className="p-4 text-sm text-red-800 rounded-lg bg-red-50 text-right" role="alert">
          <p className="font-bold">خطأ في تحميل لوحة التحكم:</p>
          <p className="font-mono text-left mt-2">{statsError}</p>
          <p className="mt-2">تأكد من أنك قمت بتنفيذ كود SQL الخاص بدالة `get_dashboard_stats` في قاعدة البيانات.</p>
        </div>
      )}
      {/* Top Stat Cards */}
      <div className="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <StatCard title="عروض الأسعار" value={stats.quotations} icon={<DocumentTextIcon />} loading={statsLoading} />
        <StatCard title="فواتير المبيعات" value={stats.salesInvoices} icon={<DocumentTextIcon />} loading={statsLoading} />
        {canViewUsers && <StatCard title="المستخدمين" value={stats.users} icon={<UsersIcon />} loading={statsLoading} />}
        {canViewPurchaseInvoices && <StatCard title="فواتير المشتريات" value={stats.purchaseInvoices} icon={<ReceiptIcon />} loading={statsLoading} />}
        {canViewAllQuotations && <StatCard title="الشركات" value={stats.companies} icon={<BuildingOfficeIcon />} loading={statsLoading} />}
      </div>
    </div>
  );
};

export default DashboardPage;
import React, { useState, useEffect } from 'react';
import { useAuth } from '../hooks/useAuth';
import { supabase } from '../services/supabaseClient';
import UsersIcon from '../components/icons/UsersIcon';
import DocumentTextIcon from '../components/icons/DocumentTextIcon';
import BellIcon from '../components/icons/BellIcon';
import MegaphoneIcon from '../components/icons/MegaphoneIcon';
import PhoneIcon from '../components/icons/PhoneIcon';
import DocumentDuplicateIcon from '../components/icons/DocumentDuplicateIcon';


interface StatCardProps {
  title: string;
  value: string | number;
  icon: React.ReactElement<{ className?: string }>;
}

const StatCard: React.FC<StatCardProps> = ({ title, value, icon }) => (
  <div className="bg-card p-5 rounded-lg shadow-sm flex items-center gap-5 border border-border">
    {React.cloneElement(icon, { className: "h-10 w-10 text-primary" })}
    <div className="text-right">
      <p className="text-3xl font-bold text-text-primary">{value}</p>
      <p className="text-text-secondary font-semibold mt-1">{title}</p>
    </div>
  </div>
);

const DashboardPage: React.FC = () => {
  const { currentUser } = useAuth();
  const [quotationCount, setQuotationCount] = useState(0);

  useEffect(() => {
    const fetchQuotationCount = async () => {
        if (!currentUser) return;

        try {
            const { count, error } = await supabase
                .from('quotations')
                .select('*', { count: 'exact', head: true })
                .eq('created_by', currentUser.id);
            
            if (error) {
                console.error('Error fetching quotation count:', error);
            } else {
                setQuotationCount(count ?? 0);
            }
        } catch (e) {
            console.error("An unexpected error occurred while fetching stats:", e);
        }
    };

    if (currentUser) {
        fetchQuotationCount();
    }
  }, [currentUser]);

  return (
    <div className="space-y-8">
      {/* Top Stat Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <StatCard title="عروض الأسعار" value={quotationCount} icon={<DocumentTextIcon />} />
        <StatCard title="الإشعارات" value={0} icon={<BellIcon />} />
        <StatCard title="المستخدمين" value={4} icon={<UsersIcon />} />
        <StatCard title="الإعلانات" value={0} icon={<MegaphoneIcon />} />
        <StatCard title="التواصل" value={0} icon={<PhoneIcon />} />
        <StatCard title="الصفحات" value={3} icon={<DocumentDuplicateIcon />} />
      </div>

      {/* Charts and Quick Actions */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Visitor Rate Chart */}
        <div className="lg:col-span-2 bg-card p-5 rounded-lg shadow-sm border border-border">
           <h3 className="font-bold text-lg text-text-primary mb-4">معدل الزوار</h3>
           <div className="h-64 flex items-end justify-center">
            {/* Simplified static representation of the chart */}
             <svg width="100%" height="100%" viewBox="0 0 500 200" preserveAspectRatio="none" className="text-primary">
                <path d="M 50 180 L 150 150 L 250 40 L 350 100 L 450 120" stroke="currentColor" strokeWidth="4" fill="url(#gradient)" />
                <defs>
                    <linearGradient id="gradient" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="0%" stopColor="currentColor" stopOpacity="0.4"/>
                        <stop offset="100%" stopColor="currentColor" stopOpacity="0"/>
                    </linearGradient>
                </defs>
                <g className="text-text-secondary" fontSize="12" fill="currentColor">
                    <text x="45" y="195" textAnchor="middle">10-10</text>
                    <text x="250" y="195" textAnchor="middle">10-11</text>
                    <text x="450" y="195" textAnchor="middle">10-12</text>
                </g>
             </svg>
           </div>
        </div>
        
        {/* Quick Actions */}
        <div className="bg-card p-5 rounded-lg shadow-sm border border-border">
          <h3 className="font-bold text-lg text-text-primary mb-4">إجراءات سريعة</h3>
          <div className="grid grid-cols-2 gap-4 text-center">
            {['تعديل ملفي', 'الموقع', 'الإعدادات', 'الإشعارات', 'ملفي', 'الإعلانات'].map(action => (
                <div key={action} className="bg-slate-100 p-4 rounded-md cursor-pointer hover:bg-slate-200">
                    <p className="font-semibold text-text-primary">{action}</p>
                </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};

export default DashboardPage;
import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Quotation, Currency } from '../types';
import { useAuth } from '../hooks/useAuth';
import { canViewAllQuotations } from '../utils/permissions';
import { supabase } from '../services/supabaseClient';

const QuotationsListPage: React.FC = () => {
    const [quotations, setQuotations] = useState<Quotation[]>([]);
    const [loading, setLoading] = useState(true);
    const { currentUser } = useAuth();
    const navigate = useNavigate();

    useEffect(() => {
        const fetchQuotations = async () => {
            if (!currentUser) return;
            
            setLoading(true);
            try {
                let query = supabase.from('quotations').select('*');

                if (!canViewAllQuotations(currentUser)) {
                    query = query.eq('created_by', currentUser.id);
                }
                
                const { data, error } = await query.order('date', { ascending: false });

                if (error) {
                    console.error('Error fetching quotations:', error);
                    setQuotations([]); // Clear quotations on error
                } else if (data) {
                    const formattedQuotations: Quotation[] = data.map(q => ({
                        id: q.id,
                        quotationNumber: q.quotation_number,
                        clientName: q.client_name,
                        company: q.company,
                        project: q.project,
                        quotationType: q.quotation_type,
                        date: q.date,
                        currency: q.currency as Currency,
                        items: [], // Items are not fetched in the list view.
                        totalAmount: q.total_amount,
                        createdBy: q.created_by,
                    }));
                    setQuotations(formattedQuotations);
                }
            } catch (e) {
                console.error("An unexpected error occurred while fetching quotations:", e);
                setQuotations([]); // Ensure data is cleared on unexpected errors
            } finally {
                setLoading(false);
            }
        };

        if (currentUser) {
            fetchQuotations();
        } else {
            // If user logs out or session expires, stop loading and clear data.
            setLoading(false);
            setQuotations([]);
        }
    }, [currentUser]);

    return (
        <>
            <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
                <h2 className="text-2xl font-bold text-dark-text">قائمة عروض الأسعار</h2>
                <button 
                    onClick={() => navigate('/quotations/new')}
                    className="w-full sm:w-auto bg-[#10B981] text-white font-semibold px-5 py-2 rounded-lg hover:bg-[#059669] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-light-bg focus:ring-[#10B981] transition-all duration-200"
                >
                    + إنشاء عرض سعر جديد
                </button>
            </div>

            {loading ? (
                <p className="p-4 text-center text-muted-text">جاري تحميل عروض الأسعار...</p>
            ) : quotations.length === 0 ? (
                <p className="p-4 text-center text-muted-text">لا توجد عروض أسعار لعرضها.</p>
            ) : (
                <>
                    {/* Desktop Table View */}
                    <div className="bg-white rounded-lg shadow-md overflow-x-auto hidden md:block">
                        <table className="w-full text-right min-w-[640px]">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="p-4 font-bold text-muted-text">رقم العرض</th>
                                    <th className="p-4 font-bold text-muted-text">اسم العميل</th>
                                    <th className="p-4 font-bold text-muted-text">المشروع</th>
                                    <th className="p-4 font-bold text-muted-text">التاريخ</th>
                                    <th className="p-4 font-bold text-muted-text">الإجمالي</th>
                                    <th className="p-4 font-bold text-muted-text"></th>
                                </tr>
                            </thead>
                            <tbody className="text-dark-text">
                                {quotations.map((q) => (
                                    <tr key={q.id} className="border-b border-border hover:bg-gray-50 transition-colors duration-200">
                                        <td className="p-4">{q.quotationNumber}</td>
                                        <td className="p-4">{q.clientName}</td>
                                        <td className="p-4">{q.project}</td>
                                        <td className="p-4">{q.date}</td>
                                        <td className="p-4">{q.totalAmount?.toLocaleString()} {q.currency}</td>
                                        <td className="p-4 text-center">
                                            <Link to={`/quotations/${q.id}`} className="text-primary hover:underline font-semibold">
                                                عرض / تعديل
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Mobile Card View */}
                    <div className="md:hidden space-y-4">
                        {quotations.map(q => (
                             <div key={q.id} className="bg-white rounded-lg shadow p-4">
                                <div className="flex justify-between items-start">
                                    <div>
                                        <p className="font-bold text-dark-text">{q.quotationNumber}</p>
                                        <p className="text-sm text-muted-text">{q.clientName}</p>
                                    </div>
                                    <p className="text-xs text-muted-text shrink-0 ml-2">{q.date}</p>
                                </div>
                                <p className="my-2 text-sm text-dark-text">{q.project}</p>
                                <div className="flex justify-between items-center mt-4 pt-4 border-t border-border">
                                    <p className="font-bold text-lg text-primary">{q.totalAmount?.toLocaleString()} {q.currency}</p>
                                    <Link to={`/quotations/${q.id}`} className="bg-primary/10 text-primary px-4 py-1.5 rounded-md font-semibold text-sm hover:bg-primary/20 transition-colors">عرض</Link>
                                </div>
                            </div>
                        ))}
                    </div>
                </>
            )}
        </>
    );
};

export default QuotationsListPage;
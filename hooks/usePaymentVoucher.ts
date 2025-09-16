import { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { supabase } from '../services/supabaseClient';
import { useAuth } from './useAuth';
import { useAccounts } from '../contexts/AccountContext';
import { PaymentVoucher } from '../types';

const formatFetchedPaymentVoucher = (data: any): PaymentVoucher => ({
    id: data.id,
    date: data.date,
    amount: data.amount,
    payment_method: data.payment_method,
    description: data.description,
    account_id: data.account_id,
    cash_account_id: data.cash_account_id,
    createdBy: data.created_by,
    creatorName: data.creator_name,
    account_name: data.account_name,
    cash_account_name: data.cash_account_name,
    createdAt: data.created_at,
});

interface UsePaymentVoucherProps {
  id?: string;
  preloadedData?: PaymentVoucher;
}

export const usePaymentVoucher = ({ id: idParam, preloadedData }: UsePaymentVoucherProps) => {
  const navigate = useNavigate();
  const { currentUser } = useAuth();
  const { accountsFlat } = useAccounts();
  const [voucher, setVoucher] = useState<PaymentVoucher | null>(preloadedData || null);
  const [loading, setLoading] = useState(!preloadedData);
  const [isSaving, setIsSaving] = useState(false);
  const [saveError, setSaveError] = useState<string | null>(null);

  const isNew = idParam === 'new';

  const getNewVoucherDefault = useCallback((): PaymentVoucher => {
    return {
      id: -1, // Temporary ID
      date: new Date().toISOString().split('T')[0],
      amount: 0,
      payment_method: 'Cash',
      description: '',
      account_id: 0,
      cash_account_id: 0,
      createdBy: currentUser?.id ?? null
    };
  }, [currentUser]);

  useEffect(() => {
    const fetchVoucher = async (voucherId: number) => {
      setLoading(true);
      try {
        const { data, error } = await supabase.from('payment_vouchers_with_names').select('*').eq('id', voucherId).single();
        if (error) throw error;
        setVoucher(formatFetchedPaymentVoucher(data));
      } catch (error: any) {
        console.error('Error fetching payment voucher:', error.message);
        navigate('/404');
      } finally {
        setLoading(false);
      }
    };

    // If data was passed via navigation state, we don't need to fetch.
    if (preloadedData) {
      setLoading(false);
      return;
    }

    if (isNew) {
      setVoucher(getNewVoucherDefault());
      setLoading(false);
    } else if (idParam) {
      const voucherId = parseInt(idParam, 10);
      if (isNaN(voucherId)) {
        console.error(`Invalid payment voucher ID in URL: "${idParam}"`);
        navigate('/404');
      } else {
        fetchVoucher(voucherId);
      }
    }
  }, [idParam, isNew, getNewVoucherDefault, navigate, preloadedData]);

  const handleSave = async () => {
    if (!voucher) return;

    if (voucher.amount <= 0) {
      setSaveError('المبلغ يجب أن يكون أكبر من صفر.');
      return;
    }
    if (!voucher.account_id || !voucher.cash_account_id) {
      setSaveError('يجب اختيار حساب المدين والدائن.');
      return;
    }

    setIsSaving(true);
    setSaveError(null);

    try {
      const { id, creatorName, account_name, cash_account_name, createdBy, ...voucherDataToSave } = voucher;

      let savedVoucherId: number;

      if (isNew) {
        // Create new voucher
        const { data: newVoucher, error: voucherError } = await supabase
          .from('payment_vouchers')
          .insert({ ...voucherDataToSave, created_by: currentUser?.id })
          .select('id')
          .single();
        if (voucherError) throw voucherError;
        savedVoucherId = newVoucher.id;
      } else {
        // Update existing voucher
        savedVoucherId = id;
        // 1. Delete old journal entries for this voucher
        const { error: deleteError } = await supabase
          .from('journal_entries')
          .delete()
          .like('description', `سند صرف رقم ${savedVoucherId}:%`);
        if (deleteError) throw new Error(`فشل تحديث القيود القديمة: ${deleteError.message}`);
        
        // 2. Update the voucher itself
        const { error: updateError } = await supabase
          .from('payment_vouchers')
          .update(voucherDataToSave)
          .eq('id', savedVoucherId);
        if (updateError) throw updateError;
      }

      // Create new journal entries for the voucher (for both create and update)
      const journalEntries = [
        // Debit the expense/supplier account
        {
          date: voucherDataToSave.date,
          description: `سند صرف رقم ${savedVoucherId}: (${voucherDataToSave.payment_method})`,
          debit: voucherDataToSave.amount,
          credit: 0,
          account_id: voucherDataToSave.account_id,
          created_by: currentUser?.id,
        },
        // Credit the cash/bank account
        {
          date: voucherDataToSave.date,
          description: `سند صرف رقم ${savedVoucherId}: (${voucherDataToSave.payment_method})`,
          debit: 0,
          credit: voucherDataToSave.amount,
          account_id: voucherDataToSave.cash_account_id,
          created_by: currentUser?.id,
        },
      ];

      const { error: journalError } = await supabase.from('journal_entries').insert(journalEntries);
      if (journalError) throw new Error(`فشل إنشاء القيود الجديدة: ${journalError.message}`);

      // Construct the full object to pass to the view page, avoiding a refetch race condition.
      if (!accountsFlat) {
        // This should ideally not happen if the form is usable, but it's a safeguard.
        throw new Error("قائمة الحسابات غير متاحة، لا يمكن إكمال العملية.");
      }
      const account = accountsFlat.find(a => a.id === voucher.account_id);
      const cashAccount = accountsFlat.find(a => a.id === voucher.cash_account_id);

      const fullVoucherObject: PaymentVoucher = {
        ...voucherDataToSave,
        id: savedVoucherId,
        createdBy: currentUser?.id ?? null,
        creatorName: currentUser?.name || 'غير معروف',
        createdAt: voucher.createdAt || new Date().toISOString(), // Ensure createdAt is present for preloading
        account_name: account?.name || 'حساب غير معروف',
        cash_account_name: cashAccount?.name || 'حساب غير معروف',
      };

      // Navigate with the complete data in state.
      navigate(`/accounts/payment-vouchers/${savedVoucherId}/view`, { state: { preloadedData: fullVoucherObject }, replace: true });
    } catch (error: any) {
      console.error('Failed to save payment voucher:', error.message);
      setSaveError(`حدث خطأ أثناء حفظ السند: ${error.message}`);
    } finally {
      setIsSaving(false);
    }
  };

  return { voucher, setVoucher, loading, isSaving, saveError, handleSave };
};
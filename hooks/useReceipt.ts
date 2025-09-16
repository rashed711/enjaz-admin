import { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { supabase } from '../services/supabaseClient';
import { useAuth } from './useAuth';
import { useAccounts } from '../contexts/AccountContext';
import { Receipt, User } from '../types';

const formatFetchedReceipt = (data: any): Receipt => ({
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

interface UseReceiptProps {
  id?: string;
  preloadedData?: Receipt;
}

export const useReceipt = ({ id: idParam, preloadedData }: UseReceiptProps) => {
  const navigate = useNavigate();
  const { currentUser } = useAuth();
  const { accountsFlat } = useAccounts();
  const [receipt, setReceipt] = useState<Receipt | null>(preloadedData || null);
  const [loading, setLoading] = useState(!preloadedData);
  const [isSaving, setIsSaving] = useState(false);
  const [saveError, setSaveError] = useState<string | null>(null);

  const isNew = idParam === 'new';

  const getNewReceiptDefault = useCallback((): Receipt => {
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
    const fetchReceipt = async (receiptId: number) => {
      setLoading(true);
      try {
        const { data, error } = await supabase.from('receipts_with_names').select('*').eq('id', receiptId).single();
        if (error) throw error;
        setReceipt(formatFetchedReceipt(data));
      } catch (error: any) {
        console.error('Error fetching receipt:', error.message);
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
      setReceipt(getNewReceiptDefault());
      setLoading(false);
    } else if (idParam) {
      const receiptId = parseInt(idParam, 10);
      if (isNaN(receiptId)) {
        console.error(`Invalid receipt ID in URL: "${idParam}"`);
        navigate('/404');
      } else {
        fetchReceipt(receiptId);
      }
    }
  }, [idParam, isNew, getNewReceiptDefault, navigate, preloadedData]);

  const handleSave = async () => {
    if (!receipt) return;

    if (receipt.amount <= 0) {
      setSaveError('المبلغ يجب أن يكون أكبر من صفر.');
      return;
    }
    if (!receipt.account_id || !receipt.cash_account_id) {
      setSaveError('يجب اختيار حساب المدين والدائن.');
      return;
    }

    setIsSaving(true);
    setSaveError(null);

    try {
      const { id, creatorName, account_name, cash_account_name, createdBy, ...receiptDataToSave } = receipt;

      let savedReceiptId: number;

      if (isNew) {
        // Create new receipt
        const { data: newReceipt, error: receiptError } = await supabase
          .from('receipts')
          .insert({ ...receiptDataToSave, created_by: currentUser?.id })
          .select('id')
          .single();
        if (receiptError) throw receiptError;
        savedReceiptId = newReceipt.id;
      } else {
        // Update existing receipt
        savedReceiptId = id;
        // 1. Delete old journal entries for this receipt
        const { error: deleteError } = await supabase
          .from('journal_entries')
          .delete()
          .like('description', `سند قبض رقم ${savedReceiptId}:%`);
        if (deleteError) throw new Error(`فشل تحديث القيود القديمة: ${deleteError.message}`);
        
        // 2. Update the receipt itself
        const { error: updateError } = await supabase
          .from('receipts')
          .update(receiptDataToSave)
          .eq('id', savedReceiptId);
        if (updateError) throw updateError;
      }

      // Create new journal entries for the receipt (for both create and update)
      const journalEntries = [
        {
          date: receiptDataToSave.date,
          description: `سند قبض رقم ${savedReceiptId}: (${receiptDataToSave.payment_method})`,
          debit: receiptDataToSave.amount,
          credit: 0,
          account_id: receiptDataToSave.cash_account_id,
          created_by: currentUser?.id,
        },
        {
          date: receiptDataToSave.date,
          description: `سند قبض رقم ${savedReceiptId}: (${receiptDataToSave.payment_method})`,
          debit: 0,
          credit: receiptDataToSave.amount,
          account_id: receiptDataToSave.account_id,
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
      const account = accountsFlat.find(a => a.id === receipt.account_id);
      const cashAccount = accountsFlat.find(a => a.id === receipt.cash_account_id);

      const fullReceiptObject: Receipt = {
        ...receiptDataToSave,
        id: savedReceiptId,
        createdBy: currentUser?.id ?? null,
        creatorName: currentUser?.name || 'غير معروف',
        createdAt: receipt.createdAt || new Date().toISOString(), // Ensure createdAt is present for preloading
        account_name: account?.name || 'حساب غير معروف',
        cash_account_name: cashAccount?.name || 'حساب غير معروف',
      };

      // Navigate with the complete data in state.
      navigate(`/accounts/receipts/${savedReceiptId}/view`, { state: { preloadedData: fullReceiptObject }, replace: true });
    } catch (error: any) {
      console.error('Failed to save receipt:', error.message);
      setSaveError(`حدث خطأ أثناء حفظ السند: ${error.message}`);
    } finally {
      setIsSaving(false);
    }
  };

  return { receipt, setReceipt, loading, isSaving, saveError, handleSave };
};
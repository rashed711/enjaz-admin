import { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { supabase } from '../services/supabaseClient';
import { useAuth } from './useAuth';
import { Receipt } from '../types';

interface UseReceiptProps {
  id?: string;
}

export const useReceipt = ({ id: idParam }: UseReceiptProps) => {
  const navigate = useNavigate();
  const { currentUser } = useAuth();
  const [receipt, setReceipt] = useState<Receipt | null>(null);
  const [loading, setLoading] = useState(true);
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
      createdBy: currentUser?.id ?? null,
    };
  }, [currentUser]);

  useEffect(() => {
    const fetchReceipt = async (receiptId: number) => {
      setLoading(true);
      try {
        const { data, error } = await supabase.from('receipts_with_names').select('*').eq('id', receiptId).single();
        if (error) throw error;
        setReceipt(data as Receipt);
      } catch (error: any) {
        console.error('Error fetching receipt:', error.message);
        navigate('/404');
      } finally {
        setLoading(false);
      }
    };

    if (isNew) {
      setReceipt(getNewReceiptDefault());
      setLoading(false);
    } else if (idParam) {
      fetchReceipt(parseInt(idParam, 10));
    }
  }, [idParam, isNew, getNewReceiptDefault, navigate]);

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

      navigate(`/accounts/receipts/${savedReceiptId}/view`);
    } catch (error: any) {
      console.error('Failed to save receipt:', error.message);
      setSaveError(`حدث خطأ أثناء حفظ السند: ${error.message}`);
    } finally {
      setIsSaving(false);
    }
  };

  return { receipt, setReceipt, loading, isSaving, saveError, handleSave };
};
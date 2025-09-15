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
      const { id, creatorName, account_name, cash_account_name, ...receiptData } = receipt;

      const { data: savedReceipt, error: receiptError } = await supabase
        .from('receipts')
        .insert({ ...receiptData, created_by: currentUser?.id })
        .select()
        .single();

      if (receiptError) throw receiptError;

      const journalEntries = [
        {
          date: receiptData.date,
          description: `سند قبض رقم ${savedReceipt.id}: ${receiptData.description || ''}`,
          debit: receiptData.amount,
          credit: 0,
          account_id: receiptData.cash_account_id,
          created_by: currentUser?.id,
        },
        {
          date: receiptData.date,
          description: `سند قبض رقم ${savedReceipt.id}: ${receiptData.description || ''}`,
          debit: 0,
          credit: receiptData.amount,
          account_id: receiptData.account_id,
          created_by: currentUser?.id,
        },
      ];

      const { error: journalError } = await supabase.from('journal_entries').insert(journalEntries);

      if (journalError) {
        await supabase.from('receipts').delete().eq('id', savedReceipt.id);
        throw journalError;
      }

      navigate(`/accounts/receipts`);
    } catch (error: any) {
      console.error('Failed to save receipt:', error.message);
      setSaveError(`حدث خطأ أثناء حفظ السند: ${error.message}`);
    } finally {
      setIsSaving(false);
    }
  };

  return { receipt, setReceipt, loading, isSaving, saveError, handleSave };
};
import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useReceipt } from '../hooks/useReceipt';
import Spinner from '../components/Spinner';
import { useAccounts } from '../contexts/AccountContext';
import AccountSelect from '../components/AccountSelect';
import { AccountType, Receipt } from '../types';

interface ReceiptEditorFormProps {
  receipt: Receipt;
  setReceipt: React.Dispatch<React.SetStateAction<Receipt | null>>;
  onSave: () => Promise<void>;
  isSaving: boolean;
  onCancel: () => void;
  saveError: string | null;
}

const ReceiptEditorForm: React.FC<ReceiptEditorFormProps> = ({ receipt, setReceipt, onSave, isSaving, onCancel, saveError }) => {
  const { accountsFlat, loading: accountsLoading } = useAccounts();

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    const isNumber = ['amount', 'account_id', 'cash_account_id'].includes(name);
    setReceipt((prev) => (prev ? { ...prev, [name]: isNumber ? parseFloat(value) || 0 : value } : null));
  };

  if (accountsLoading || !receipt) {
    return <div className="flex justify-center items-center p-10"><Spinner /></div>;
  }

  const inputClasses = "border border-border bg-white text-text-primary p-2 rounded-lg w-full text-right focus:outline-none focus:ring-2 focus:ring-primary transition-colors";
  const buttonClasses = "px-5 py-2 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2";

  return (
    <div className="bg-card p-6 rounded-lg shadow-sm border border-border max-w-4xl mx-auto">
      <h2 className="text-xl font-bold mb-6 border-b border-border pb-3 text-text-primary">
        {receipt.id === -1 ? 'إضافة سند قبض جديد' : 'تعديل سند قبض'}
      </h2>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="space-y-4">
          <AccountSelect
            label="من حساب العميل (دائن)"
            name="account_id"
            accounts={accountsFlat}
            value={receipt.account_id}
            onChange={handleInputChange}
            className={inputClasses}
            filter={(acc) => acc.code?.startsWith('101-')} // Show only customer sub-accounts
          />
          <AccountSelect
            label="إلى حساب الصندوق/البنك (مدين)"
            name="cash_account_id"
            accounts={accountsFlat}
            value={receipt.cash_account_id}
            onChange={handleInputChange}
            className={inputClasses}
            filter={(acc) => acc.code?.startsWith('102-')} // Show only cash/bank sub-accounts
          />
          <div>
            <label htmlFor="payment_method" className="block text-sm font-medium mb-1 text-text-secondary">طريقة الدفع</label>
            <input type="text" name="payment_method" id="payment_method" value={receipt.payment_method} onChange={handleInputChange} className={inputClasses} />
          </div>
        </div>
        <div className="space-y-4">
          <div>
            <label htmlFor="date" className="block text-sm font-medium mb-1 text-text-secondary">التاريخ</label>
            <input type="date" name="date" id="date" value={receipt.date} onChange={handleInputChange} className={inputClasses} />
          </div>
          <div>
            <label htmlFor="amount" className="block text-sm font-medium mb-1 text-text-secondary">المبلغ</label>
            <input type="number" name="amount" id="amount" value={receipt.amount || ''} onChange={handleInputChange} className={inputClasses} placeholder="0.00" />
          </div>
          <div>
            <label htmlFor="description" className="block text-sm font-medium mb-1 text-text-secondary">الوصف</label>
            <textarea name="description" id="description" value={receipt.description || ''} onChange={handleInputChange} className={inputClasses} rows={3}></textarea>
          </div>
        </div>
      </div>
      <div className="mt-8">
        {saveError && (
          <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 text-right" role="alert">
            <strong className="font-bold">خطأ!</strong>
            <span className="block sm:inline mr-2">{saveError}</span>
          </div>
        )}
        <div className="flex justify-end gap-4">
          <button onClick={onCancel} className={`bg-gray-200 hover:bg-gray-300 text-gray-800 ${buttonClasses}`} disabled={isSaving}>إلغاء</button>
          <button onClick={onSave} className={`bg-green-600 hover:bg-green-700 text-white focus:ring-green-600 ${buttonClasses} w-40`} disabled={isSaving}>
            {isSaving && <Spinner />}
            {isSaving ? 'جاري الحفظ...' : 'حفظ'}
          </button>
        </div>
      </div>
    </div>
  );
};

const ReceiptEditorPage: React.FC = () => {
    const { id: idParam } = useParams<{ id: string }>();
    const navigate = useNavigate();

    const { receipt, setReceipt, loading, isSaving, saveError, handleSave } = useReceipt({ id: idParam });
    
    const isNew = idParam === 'new';

    if (loading || !receipt) {
        return <div className="flex justify-center items-center h-full"><Spinner /></div>;
    }
    
    return (
        <ReceiptEditorForm 
            receipt={receipt}
            setReceipt={setReceipt}
            onSave={handleSave}
            isSaving={isSaving}
            onCancel={() => navigate('/accounts/receipts')}
            saveError={saveError}
        />
    );
};

export default ReceiptEditorPage;
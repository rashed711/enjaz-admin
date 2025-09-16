import React, { useState, useEffect } from 'react';
import { Account, AccountType } from '../types';
import Spinner from './Spinner';
import Modal from './Modal';

export enum PartyType {
    NONE = 'None',
    CUSTOMER = 'Customer',
    SUPPLIER = 'Supplier',
    CUSTOMER_AND_SUPPLIER = 'CustomerAndSupplier',
}

export const partyTypeLabels: Record<PartyType, string> = {
    [PartyType.NONE]: 'لا شيء',
    [PartyType.CUSTOMER]: 'عميل',
    [PartyType.SUPPLIER]: 'مورد',
    [PartyType.CUSTOMER_AND_SUPPLIER]: 'عميل ومورد',
};

export type AccountFormData = {
  id?: number;
  name: string;
  code: string;
  account_type: AccountType;
  parent_id: number | null;
  party_type: PartyType;
};

interface AccountModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSave: (data: AccountFormData) => void;
  initialData: Partial<AccountFormData> | null;
  isSaving: boolean;
  error: string | null;
  accounts: Account[]; // For parent selection
  accountsFlat: Account[]; // For looking up parent properties
}

const accountTypeLabels: Record<AccountType, string> = {
    [AccountType.ASSET]: 'أصل',
    [AccountType.LIABILITY]: 'التزام',
    [AccountType.EQUITY]: 'حقوق ملكية',
    [AccountType.REVENUE]: 'إيراد',
    [AccountType.EXPENSE]: 'مصروف',
};

const AccountModal: React.FC<AccountModalProps> = ({ isOpen, onClose, onSave, initialData, isSaving, error, accounts, accountsFlat }) => {
    const getInitialFormData = (): AccountFormData => ({
        name: '',
        code: '',
        account_type: AccountType.EXPENSE, // Default to a valid and common type
        parent_id: null,
        party_type: PartyType.NONE,
        ...initialData,
    });
    
    const [formData, setFormData] = useState<AccountFormData>(getInitialFormData());

    useEffect(() => {
        if (isOpen) {
            setFormData(getInitialFormData());
        }
    }, [initialData, isOpen]);

    const isNewAccount = !initialData?.id;

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        
        setFormData(prev => {
            const updatedState = {
                ...prev,
                [name]: name === 'parent_id' ? (value ? parseInt(value, 10) : null) : value,
            };

            // If a new parent is selected for a new account, try to inherit its party type.
            if (isNewAccount && name === 'parent_id') {
                const newParentId = value ? parseInt(value, 10) : null;
                if (newParentId) {
                    const parentAccount = accountsFlat.find(acc => acc.id === newParentId);
                    // Inherit only if the parent has a specific classification, otherwise reset to None
                    if (parentAccount && parentAccount.party_type && parentAccount.party_type !== PartyType.NONE) {
                        updatedState.party_type = parentAccount.party_type;
                    } else {
                        updatedState.party_type = PartyType.NONE;
                    }
                } else {
                    // Reset to None if "no parent" is selected
                    updatedState.party_type = PartyType.NONE;
                }
            }
            
            return updatedState;
        });
    };

    const handleSaveClick = () => {
        onSave(formData);
    };

    const flattenAccountsForSelect = (accounts: Account[], level = 0): { id: number; name: string; level: number }[] => {
        let flatList: { id: number; name: string; level: number }[] = [];
        accounts.forEach(account => {
            flatList.push({ id: account.id, name: `${account.name} (${account.code})`, level });
            if (account.children) {
                flatList = flatList.concat(flattenAccountsForSelect(account.children, level + 1));
            }
        });
        return flatList;
    };

    const parentAccountOptions = flattenAccountsForSelect(accounts);

    const inputClasses = "border border-border bg-white text-text-primary p-2 px-3 rounded-lg w-full text-right focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors";
    const labelClasses = "block text-sm font-medium mb-2 text-right text-text-secondary";

    const footer = (
        <div className="flex w-full flex-col gap-3 sm:flex-row sm:justify-end sm:space-x-4 sm:space-x-reverse">
            <button onClick={onClose} className="bg-gray-200 text-gray-800 px-6 py-2 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card focus:ring-gray-500 font-semibold w-full sm:w-auto" disabled={isSaving}>إلغاء</button>
            <button onClick={handleSaveClick} className="bg-green-600 text-white font-semibold px-6 py-2 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card focus:ring-green-600 flex items-center justify-center gap-2 w-full sm:w-32 shadow-md hover:shadow-lg disabled:opacity-50" disabled={isSaving}>
                {isSaving && <Spinner />}
                {isSaving ? 'جاري الحفظ...' : 'حفظ'}
            </button>
        </div>
    );

    return (
        <Modal
            isOpen={isOpen}
            onClose={onClose}
            title={isNewAccount ? 'إضافة حساب جديد' : 'تعديل الحساب'}
            footer={footer}
        >
            <div className="space-y-6">
                <div>
                    <label htmlFor="parent_id" className={labelClasses}>الحساب الأب</label>
                    <select id="parent_id" name="parent_id" value={formData.parent_id || ''} onChange={handleChange} className={inputClasses}>
                        <option value="">-- حساب رئيسي (بدون أب) --</option>
                        {parentAccountOptions
                            .filter(opt => opt.id !== formData.id) // Prevent selecting self as parent
                            .map(opt => (
                            <option key={opt.id} value={opt.id}>
                                {'\u00A0\u00A0'.repeat(opt.level * 2)} {opt.name}
                            </option>
                        ))}
                    </select>
                </div>
                <div>
                    <label htmlFor="name" className={labelClasses}>اسم الحساب</label>
                    <input type="text" id="name" name="name" value={formData.name} onChange={handleChange} className={inputClasses} required/>
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label htmlFor="code" className={labelClasses}>كود الحساب</label>
                        <input type="text" id="code" name="code" value={formData.code} onChange={handleChange} className={inputClasses} required/>
                    </div>
                    <div>
                        <label htmlFor="account_type" className={labelClasses}>نوع الحساب</label>
                        <select id="account_type" name="account_type" value={formData.account_type} onChange={handleChange} className={inputClasses}>
                            {Object.values(AccountType).map(type => (
                                <option key={type} value={type}>{accountTypeLabels[type]}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label htmlFor="party_type" className={labelClasses}>تصنيف الطرف</label>
                        <select id="party_type" name="party_type" value={formData.party_type || PartyType.NONE} onChange={handleChange} className={inputClasses}>
                            {Object.values(PartyType).map(type => (
                                <option key={type} value={type}>{partyTypeLabels[type]}</option>
                            ))}
                        </select>
                    </div>
                </div>
            </div>
            {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
        </Modal>
    );
};

export default AccountModal;
import React from 'react';
import { Account } from '../types';

interface AccountSelectProps {
  accounts: Account[];
  value: number;
  onChange: (e: React.ChangeEvent<HTMLSelectElement>) => void;
  name: string;
  className?: string;
  label: string;
  filter?: (account: Account) => boolean;
}

const AccountSelect: React.FC<AccountSelectProps> = ({
  accounts,
  value,
  onChange,
  name,
  className,
  label,
  filter = () => true,
}) => {
  const filteredAccounts = accounts.filter(filter);

  return (
    <div>
      <label htmlFor={name} className="block text-sm font-medium mb-1 text-text-secondary">
        {label}
      </label>
      <select id={name} name={name} value={value} onChange={onChange} className={className} required>
        <option value={0} disabled>-- اختر حساب --</option>
        {filteredAccounts.map((account) => (
          <option key={account.id} value={account.id}>{account.name} ({account.code})</option>
        ))}
      </select>
    </div>
  );
};

export default AccountSelect;
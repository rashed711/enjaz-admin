import React from 'react';
import { PaymentVoucher } from '../types';

interface PaymentVoucherComponentProps {
    voucher: PaymentVoucher;
}

const formatCurrency = (amount: number) => {
    return amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const PaymentVoucherComponent: React.FC<PaymentVoucherComponentProps> = ({ voucher }) => {
    return (
        <div id="payment-voucher-pdf" className="bg-white p-8 rounded-lg shadow-lg max-w-2xl mx-auto border border-gray-200">
            <header className="flex justify-between items-center border-b pb-4 mb-8">
                <div>
                    <h1 className="text-3xl font-bold text-gray-800">سند صرف</h1>
                    <p className="text-gray-500">Payment Voucher</p>
                </div>
                <div className="text-left">
                    <p className="text-lg font-semibold">رقم السند: {voucher.id}</p>
                    <p className="text-sm text-gray-600">التاريخ: {new Date(voucher.date).toLocaleDateString('en-GB')}</p>
                    {voucher.createdAt && (
                        <p className="text-sm text-gray-600">الوقت: {new Date(voucher.createdAt).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}</p>
                    )}
                </div>
            </header>

            <main className="space-y-6 text-lg">
                <div className="grid grid-cols-3 gap-4 items-baseline">
                    <div className="font-semibold text-gray-700 col-span-1">صرفنا للسيد/السادة:</div>
                    <div className="font-bold text-gray-900 col-span-2 border-b border-dotted pb-1">{voucher.account_name}</div>
                </div>

                <div className="grid grid-cols-3 gap-4 items-baseline">
                    <div className="font-semibold text-gray-700 col-span-1">مبلغ وقدره:</div>
                    <div className="font-bold text-red-600 col-span-2 border-b border-dotted pb-1">{formatCurrency(voucher.amount)}</div>
                </div>

                <div className="grid grid-cols-3 gap-4 items-baseline">
                    <div className="font-semibold text-gray-700 col-span-1">وذلك عن:</div>
                    <div className="text-gray-800 col-span-2 border-b border-dotted pb-1">{voucher.description || 'دفعة من الحساب'}</div>
                </div>

                <div className="grid grid-cols-3 gap-4 items-baseline">
                    <div className="font-semibold text-gray-700 col-span-1">طريقة الدفع:</div>
                    <div className="text-gray-800 col-span-2 border-b border-dotted pb-1">{voucher.payment_method}</div>
                </div>
            </main>

            <footer className="mt-20 pt-6 grid grid-cols-2 gap-8 text-center">
                <div>
                    <p className="mb-12">المحرر</p>
                    <p className="border-t border-gray-400 pt-2 text-gray-600">{voucher.creatorName}</p>
                </div>
                <div>
                    <p className="mb-12">المستلم</p>
                    <p className="border-t border-gray-400 pt-2 text-gray-600">.........................</p>
                </div>
            </footer>
        </div>
    );
};

export default PaymentVoucherComponent;
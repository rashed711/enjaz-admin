import React from 'react';
import { Receipt } from '../types';

interface ReceiptComponentProps {
    receipt: Receipt;
}

const formatCurrency = (amount: number) => {
    return amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const ReceiptComponent: React.FC<ReceiptComponentProps> = ({ receipt }) => {
    return (
        <div id="receipt-pdf" className="bg-white p-8 rounded-lg shadow-lg max-w-2xl mx-auto border border-gray-200">
            <header className="flex justify-between items-center border-b pb-4 mb-8">
                <div>
                    <h1 className="text-3xl font-bold text-gray-800">سند قبض</h1>
                    <p className="text-gray-500">Receipt Voucher</p>
                </div>
                <div className="text-left">
                    <p className="text-lg font-semibold">رقم السند: {receipt.id}</p>
                    <p className="text-sm text-gray-600">التاريخ: {new Date(receipt.date).toLocaleDateString('en-GB')}</p>
                    {receipt.createdAt && (
                        <p className="text-sm text-gray-600">الوقت: {new Date(receipt.createdAt).toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' })}</p>
                    )}
                </div>
            </header>

            <main className="space-y-6 text-lg">
                <div className="grid grid-cols-3 gap-4 items-baseline">
                    <div className="font-semibold text-gray-700 col-span-1">استلمنا من السيد/السادة:</div>
                    <div className="font-bold text-gray-900 col-span-2 border-b border-dotted pb-1">{receipt.account_name}</div>
                </div>

                <div className="grid grid-cols-3 gap-4 items-baseline">
                    <div className="font-semibold text-gray-700 col-span-1">مبلغ وقدره:</div>
                    <div className="font-bold text-green-600 col-span-2 border-b border-dotted pb-1">{formatCurrency(receipt.amount)}</div>
                </div>

                <div className="grid grid-cols-3 gap-4 items-baseline">
                    <div className="font-semibold text-gray-700 col-span-1">طريقة الدفع:</div>
                    <div className="text-gray-800 col-span-2 border-b border-dotted pb-1">{receipt.payment_method}</div>
                </div>
            </main>

            <footer className="mt-20 pt-6 grid grid-cols-2 gap-8 text-center">
                <div>
                    <p className="mb-12">المحرر</p>
                    <p className="border-t border-gray-400 pt-2 text-gray-600">{receipt.creatorName}</p>
                </div>
                <div>
                    <p className="mb-12">الدافع</p>
                    <p className="border-t border-gray-400 pt-2 text-gray-600">.........................</p>
                </div>
            </footer>
        </div>
    );
};

export default ReceiptComponent;
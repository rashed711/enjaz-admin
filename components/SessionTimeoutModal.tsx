import React, { useState, useEffect } from 'react';

interface SessionTimeoutModalProps {
    isOpen: boolean;
    onClose: () => void;
    onStay: () => void;
    countdownSeconds: number;
}

const SessionTimeoutModal: React.FC<SessionTimeoutModalProps> = ({ isOpen, onClose, onStay, countdownSeconds }) => {
    const [countdown, setCountdown] = useState(countdownSeconds);

    useEffect(() => {
        if (isOpen) {
            setCountdown(countdownSeconds); // Reset countdown on open
            const interval = setInterval(() => {
                setCountdown(prev => (prev > 0 ? prev - 1 : 0));
            }, 1000);

            return () => clearInterval(interval);
        }
    }, [isOpen, countdownSeconds]);

    if (!isOpen) return null;

    const minutes = Math.floor(countdown / 60);
    const seconds = countdown % 60;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4" aria-modal="true" role="dialog">
            <div className="bg-card rounded-lg shadow-xl p-6 w-full max-w-md text-center" dir="rtl">
                <h2 className="text-xl font-bold text-text-primary mb-2">هل ما زلت هنا؟</h2>
                <p className="text-text-secondary mb-4">
                    سيتم تسجيل خروجك تلقائياً بسبب عدم النشاط.
                </p>
                <div className="my-6 p-4 bg-yellow-100 border-r-4 border-yellow-500 text-yellow-800 text-right">
                    <p>سيتم تسجيل الخروج خلال:</p>
                    <p className="text-2xl font-bold font-mono tracking-wider text-center">
                        {String(minutes).padStart(2, '0')}:{String(seconds).padStart(2, '0')}
                    </p>
                </div>
                <div className="flex justify-center gap-4">
                    <button onClick={onStay} className="px-6 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">البقاء وتسجيل الدخول</button>
                    <button onClick={onClose} className="px-6 py-2 bg-gray-200 text-text-secondary font-semibold rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400">تسجيل الخروج</button>
                </div>
            </div>
        </div>
    );
};

export default SessionTimeoutModal;
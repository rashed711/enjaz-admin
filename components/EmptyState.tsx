import React from 'react';

interface EmptyStateProps {
    Icon: React.FC<React.SVGProps<SVGSVGElement>>;
    title: string;
    message: string;
    action?: {
        label: string;
        onClick: () => void;
    }
}

const EmptyState: React.FC<EmptyStateProps> = ({ Icon, title, message, action }) => (
    <div className="bg-card rounded-lg shadow-sm border border-border p-12 text-center flex flex-col items-center">
        <Icon className="w-16 h-16 text-slate-300 mb-4" />
        <h3 className="text-xl font-semibold text-text-primary mb-2">{title}</h3>
        <p className="text-text-secondary max-w-sm">{message}</p>
        {action && (
            <button 
                onClick={action.onClick}
                className="mt-6 bg-primary text-white font-semibold px-5 py-2 rounded-lg hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card focus:ring-primary shadow-md hover:shadow-lg"
            >
                {action.label}
            </button>
        )}
    </div>
);

export default EmptyState;

import React from 'react';

const ShieldCheckIcon: React.FC<React.SVGProps<SVGSVGElement>> = (props) => (
  <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" {...props}>
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 20.944a11.955 11.955 0 019-2.607 11.955 11.955 0 019 2.607c-.16.24-.324.472-.498.692A11.982 11.982 0 0112 21a11.982 11.982 0 01-8.502-3.502 12.02 12.02 0 003.502-15.444" />
  </svg>
);

export default React.memo(ShieldCheckIcon);

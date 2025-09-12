import React from 'react';

const ReceiptIcon: React.FC<React.SVGProps<SVGSVGElement>> = (props) => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" {...props}>
    <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-12v.75m0 3v.75m0 3v.75m0 3V18m-3-12v.75m0 3v.75m0 3v.75m0 3V18m-3-12h18M3 12h18m-3 6h3M3 18h3M3 6h18M3 6V4.5a1.5 1.5 0 011.5-1.5h15A1.5 1.5 0 0121 4.5V6m-18 0v12a1.5 1.5 0 001.5 1.5h15a1.5 1.5 0 001.5-1.5V6" />
  </svg>
);

export default ReceiptIcon;

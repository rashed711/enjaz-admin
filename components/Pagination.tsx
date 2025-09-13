import React from 'react';
import ChevronRightIcon from './icons/ChevronRightIcon';
import ChevronLeftIcon from './icons/ChevronLeftIcon';

interface PaginationProps {
  currentPage: number;
  totalCount: number;
  itemsPerPage: number;
  onPageChange: (page: number) => void;
}

const Pagination: React.FC<PaginationProps> = ({ currentPage, totalCount, itemsPerPage, onPageChange }) => {
  const totalPages = Math.ceil(totalCount / itemsPerPage);

  if (totalPages <= 1) {
    return null;
  }

  const handlePrevious = () => {
    if (currentPage > 1) {
      onPageChange(currentPage - 1);
    }
  };

  const handleNext = () => {
    if (currentPage < totalPages) {
      onPageChange(currentPage + 1);
    }
  };

  const fromItem = (currentPage - 1) * itemsPerPage + 1;
  const toItem = Math.min(currentPage * itemsPerPage, totalCount);

  return (
    <div className="flex items-center justify-between bg-card px-4 py-3 sm:px-6 mt-4 border-t border-border">
      <div className="flex-1 flex justify-between sm:hidden">
        <button onClick={handlePrevious} disabled={currentPage === 1} className="relative inline-flex items-center rounded-md border border-border bg-white px-4 py-2 text-sm font-medium text-text-secondary hover:bg-slate-50 disabled:opacity-50">السابق</button>
        <button onClick={handleNext} disabled={currentPage === totalPages} className="relative ml-3 inline-flex items-center rounded-md border border-border bg-white px-4 py-2 text-sm font-medium text-text-secondary hover:bg-slate-50 disabled:opacity-50">التالي</button>
      </div>
      <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
        <div><p className="text-sm text-text-secondary">عرض <span className="font-medium">{fromItem}</span> إلى <span className="font-medium">{toItem}</span> من <span className="font-medium">{totalCount}</span> النتائج</p></div>
        <div><nav className="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination"><button onClick={handleNext} disabled={currentPage === totalPages} className="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 disabled:opacity-50"><span className="sr-only">التالي</span><ChevronRightIcon className="h-5 w-5" aria-hidden="true" /></button><span className="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300">صفحة {currentPage} من {totalPages}</span><button onClick={handlePrevious} disabled={currentPage === 1} className="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 disabled:opacity-50"><span className="sr-only">السابق</span><ChevronLeftIcon className="h-5 w-5" aria-hidden="true" /></button></nav></div>
      </div>
    </div>
  );
};

export default Pagination;
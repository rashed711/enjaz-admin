
import React, { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { jsPDF } from 'jspdf';
import html2canvas from 'html2canvas';
import { Quotation, QuotationItem, Currency, Product, ProductType, Unit } from '../types';
import QuotationComponent from '../components/Quotation';
import { useAuth } from '../hooks/useAuth';
import { useProducts } from '../contexts/ProductContext';
import { supabase } from '../services/supabaseClient';
import AddProductModal from '../components/AddProductModal';

// Extend QuotationItem for editor-specific state
interface QuotationItemState extends QuotationItem {
    productType?: ProductType;
}

const QuotationEditorPage: React.FC = () => {
    const { id: idParam } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const { currentUser } = useAuth();
    const { products, addProduct } = useProducts();

    const [quotation, setQuotation] = useState<Omit<Quotation, 'items'> & { items: QuotationItemState[] } | null>(null);
    const [isEditing, setIsEditing] = useState(false);
    const [loading, setLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isProcessingPdf, setIsProcessingPdf] = useState(false);

    const isNew = idParam === 'new';
    
    const updateItemDescription = useCallback((item: QuotationItemState, product?: Product): string => {
        const p = product || products.find(prod => prod.id === item.productId);
        if (!p) return item.description;

        let desc = p.name;
        if (p.productType === ProductType.DIFFUSER && item.length && item.width) {
            desc += ` (${item.length} x ${item.width})`;
        } else if (p.productType === ProductType.CABLE_TRAY && item.width && item.height) {
            desc += ` (${item.width} x ${item.height})`;
        }
        return desc;
    }, [products]);

    useEffect(() => {
        const createNewQuotation = async () => {
            const { count } = await supabase.from('quotations').select('*', { count: 'exact', head: true });
            const newQuotationNumber = `ENJ-2024-${(count ?? 0) + 1}`;

            setQuotation({
                quotationNumber: newQuotationNumber,
                clientName: '',
                company: '',
                project: '',
                quotationType: '',
                date: new Date().toISOString().split('T')[0],
                currency: Currency.SAR,
                items: [{ description: '', quantity: 1, unitPrice: 0, total: 0, unit: Unit.COUNT }],
                totalAmount: 0,
                createdBy: currentUser?.id || '',
            });
            setIsEditing(true);
            setLoading(false);
        };
        
        const fetchQuotation = async (quotationId: number) => {
            setLoading(true);
            
            const { data: qData, error: qError } = await supabase.from('quotations').select('*').eq('id', quotationId).single();
            if (qError) {
                console.error('Error fetching quotation:', qError);
                return navigate('/404');
            }
            
            const { data: itemsData, error: itemsError } = await supabase.from('quotation_items').select('*').eq('quotation_id', quotationId);
            if (itemsError) console.error('Error fetching items:', itemsError);
            
            const augmentedItems: QuotationItemState[] = itemsData ? itemsData.map(item => {
                const product = products.find(p => p.id === item.product_id);
                const fetchedItem: QuotationItemState = {
                    id: item.id,
                    productId: item.product_id,
                    description: item.description,
                    quantity: item.quantity,
                    unitPrice: item.unit_price,
                    total: item.total,
                    unit: (item.unit as Unit) || (product ? product.unit : Unit.COUNT),
                    productType: product ? product.productType : ProductType.SIMPLE,
                    length: item.length,
                    width: item.width,
                    height: item.height,
                };
                fetchedItem.description = updateItemDescription(fetchedItem, product);
                return fetchedItem;
            }) : [];

            const fetchedQuotation: Omit<Quotation, 'items'> & { items: QuotationItemState[] } = {
                id: qData.id,
                quotationNumber: qData.quotation_number,
                clientName: qData.client_name,
                company: qData.company,
                project: qData.project,
                quotationType: qData.quotation_type,
                date: qData.date,
                currency: qData.currency as Currency,
                totalAmount: qData.total_amount,
                createdBy: qData.created_by,
                items: augmentedItems,
            };

            setQuotation(fetchedQuotation);
            setLoading(false);
        }

        if (isNew) {
            createNewQuotation();
        } else if (idParam && products.length > 0) {
            fetchQuotation(parseInt(idParam, 10));
        }
    }, [idParam, navigate, currentUser, isNew, products, updateItemDescription]);

    const generatePdfBlob = async (): Promise<Blob | null> => {
        const input = document.getElementById('quotation-pdf');
        if (!input) return null;
    
        const originalWidth = input.style.width;
        input.style.width = '1024px';
    
        try {
            const canvas = await html2canvas(input, { scale: 2, useCORS: true });
            
            const pdf = new jsPDF('p', 'mm', 'a4');
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = pdf.internal.pageSize.getHeight();
            
            const canvasWidth = canvas.width;
            const canvasHeight = canvas.height;
            const ratio = canvasWidth / pdfWidth;
            
            let canvasPosition = 0;
            while (canvasPosition < canvasHeight) {
                const pageHeightInCanvas = Math.min(pdfHeight * ratio, canvasHeight - canvasPosition);
                const pageCanvas = document.createElement('canvas');
                pageCanvas.width = canvasWidth;
                pageCanvas.height = pageHeightInCanvas;
                
                const ctx = pageCanvas.getContext('2d');
                if (ctx) {
                    ctx.drawImage(canvas, 0, canvasPosition, canvasWidth, pageHeightInCanvas, 0, 0, canvasWidth, pageHeightInCanvas);
                    const imgData = pageCanvas.toDataURL('image/png');
                    const pageHeightInPDF = pageHeightInCanvas / ratio;
                    if (canvasPosition > 0) pdf.addPage();
                    pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pageHeightInPDF);
                    canvasPosition += pageHeightInCanvas;
                } else {
                    return null;
                }
            }
            return pdf.output('blob');
        } catch (error) {
            console.error("Error generating PDF:", error);
            return null;
        } finally {
            input.style.width = originalWidth;
        }
    };

    const handleExportToPDF = async () => {
        setIsProcessingPdf(true);
        const blob = await generatePdfBlob();
        if (blob && quotation) {
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${quotation.quotationNumber}.pdf`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } else {
            alert("حدث خطأ أثناء إنشاء ملف PDF.");
        }
        setIsProcessingPdf(false);
    };
    
    const handleShare = async () => {
        if (!quotation) return;
    
        setIsProcessingPdf(true);
        const blob = await generatePdfBlob();
        if (!blob) {
            alert("لا يمكن إنشاء ملف PDF للمشاركة.");
            setIsProcessingPdf(false);
            return;
        }
    
        const fileName = `${quotation.quotationNumber}.pdf`;
        const file = new File([blob], fileName, { type: 'application/pdf' });
        const shareData = {
            files: [file],
            title: `عرض سعر ${quotation.quotationNumber}`,
            text: `مرحباً،\n\nتجدون مرفقاً عرض السعر رقم ${quotation.quotationNumber} من شركة إنجاز.\n\nشكراً لكم.`,
        };
    
        if (navigator.share && navigator.canShare && navigator.canShare(shareData)) {
            try {
                await navigator.share(shareData);
            } catch (error) {
                console.error('Error sharing:', error);
            }
        } else {
            alert("مشاركة الملفات غير مدعومة على هذا المتصفح. سيتم فتح واتساب مع رسالة نصية.");
            const message = encodeURIComponent(shareData.text);
            window.open(`https://wa.me/?text=${message}`, '_blank');
        }
        setIsProcessingPdf(false);
    };

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setQuotation(prev => prev ? { ...prev, [name]: value } : null);
    };

    const handleItemChange = (index: number, e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        if (quotation) {
            const newItems = [...quotation.items];
            const itemToUpdate = { ...newItems[index] };
            
            const numValue = parseFloat(value) || 0;

            switch (name) {
                case 'description': itemToUpdate.description = value; break;
                case 'quantity': itemToUpdate.quantity = numValue; break;
                case 'unitPrice': itemToUpdate.unitPrice = numValue; break;
                case 'length': itemToUpdate.length = numValue; break;
                case 'width': itemToUpdate.width = numValue; break;
                case 'height': itemToUpdate.height = numValue; break;
                case 'unit': itemToUpdate.unit = value as Unit; break;
            }

            if (name === 'length' || name === 'width' || name === 'height') {
                itemToUpdate.description = updateItemDescription(itemToUpdate);
            }
            
            itemToUpdate.total = parseFloat((itemToUpdate.quantity * itemToUpdate.unitPrice).toFixed(2));
            newItems[index] = itemToUpdate;
            const newTotalAmount = newItems.reduce((sum, item) => sum + item.total, 0);

            setQuotation({ ...quotation, items: newItems, totalAmount: parseFloat(newTotalAmount.toFixed(2)) });
        }
    };

    const handleProductSelection = (itemIndex: number, selectedProductId: string) => {
        if (!quotation) return;

        const newItems = [...quotation.items];
        let itemToUpdate = { ...newItems[itemIndex] };

        if (selectedProductId === 'custom' || !selectedProductId) {
            itemToUpdate = {
                ...itemToUpdate,
                productId: undefined,
                description: '',
                unitPrice: 0,
                unit: Unit.COUNT,
                productType: ProductType.SIMPLE,
                length: undefined,
                width: undefined,
                height: undefined,
            };
        } else {
            const product = products.find(p => p.id === parseInt(selectedProductId, 10));
            if (product) {
                itemToUpdate = {
                    ...itemToUpdate,
                    productId: product.id,
                    description: product.name,
                    unitPrice: product.unitPrice,
                    unit: product.unit,
                    productType: product.productType,
                    length: undefined,
                    width: undefined,
                    height: undefined,
                };
                itemToUpdate.description = updateItemDescription(itemToUpdate, product);
            }
        }
        
        itemToUpdate.total = parseFloat((itemToUpdate.quantity * itemToUpdate.unitPrice).toFixed(2));
        newItems[itemIndex] = itemToUpdate;
        const newTotalAmount = newItems.reduce((sum, item) => sum + item.total, 0);
        setQuotation({ ...quotation, items: newItems, totalAmount: parseFloat(newTotalAmount.toFixed(2)) });
    };

    const addItem = () => {
        if (quotation) {
            const newItem: QuotationItemState = {
                description: '',
                quantity: 1,
                unitPrice: 0,
                total: 0,
                unit: Unit.COUNT,
                productType: ProductType.SIMPLE
            };
            setQuotation({ ...quotation, items: [...quotation.items, newItem] });
        }
    };
    
    const removeItem = (index: number) => {
        if (quotation) {
            const newItems = quotation.items.filter((_, i) => i !== index);
            const newTotalAmount = newItems.reduce((sum, item) => sum + item.total, 0);
            setQuotation({ ...quotation, items: newItems, totalAmount: parseFloat(newTotalAmount.toFixed(2)) });
        }
    };
    
    const handleSave = async () => {
        if (!quotation || !currentUser) return;
        setIsSaving(true);
    
        const quotationDataForDb = {
            quotation_number: quotation.quotationNumber,
            client_name: quotation.clientName,
            company: quotation.company,
            project: quotation.project,
            quotation_type: quotation.quotationType,
            date: quotation.date,
            currency: quotation.currency,
            total_amount: quotation.totalAmount,
            created_by: quotation.createdBy,
        };
    
        const itemsDataForDb = quotation.items.map(item => ({
            product_id: item.productId || null,
            description: item.description,
            quantity: item.quantity,
            unit_price: item.unitPrice,
            total: item.total,
            unit: item.unit,
            length: item.length,
            width: item.width,
            height: item.height,
        }));
    
        try {
            if (!quotation.id) { // Create new quotation
                const { data: newQ, error: createError } = await supabase.from('quotations').insert(quotationDataForDb).select().single();
                if (createError || !newQ) throw createError;
    
                const itemsWithId = itemsDataForDb.map(item => ({ ...item, quotation_id: newQ.id }));
                const { error: itemsError } = await supabase.from('quotation_items').insert(itemsWithId);
                if (itemsError) throw itemsError;
    
                navigate(`/quotations/${newQ.id}`);
    
            } else { // Update existing quotation
                const { error: updateError } = await supabase.from('quotations').update(quotationDataForDb).eq('id', quotation.id);
                if (updateError) throw updateError;
    
                const { error: deleteError } = await supabase.from('quotation_items').delete().eq('quotation_id', quotation.id);
                if (deleteError) throw deleteError;
    
                const itemsWithId = itemsDataForDb.map(item => ({ ...item, quotation_id: quotation.id }));
                if (itemsWithId.length > 0) {
                    const { error: itemsError } = await supabase.from('quotation_items').insert(itemsWithId);
                    if (itemsError) throw itemsError;
                }
    
                setIsEditing(false);
            }
        } catch (error: any) {
            console.error("Failed to save quotation:", error);
            const errorMessage = error?.message || "An unexpected error occurred.";
            alert(`حدث خطأ أثناء حفظ عرض السعر: ${errorMessage}`);
        } finally {
            setIsSaving(false);
        }
    };
    
    const inputClasses = "border border-border bg-gray-50 text-dark-text p-2 rounded w-full text-right focus:outline-none focus:ring-2 focus:ring-[#10B981] focus:border-[#10B981] transition-colors";
    const buttonClasses = "px-5 py-2 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white font-semibold disabled:opacity-50 disabled:cursor-not-allowed";


    if (loading || !quotation) {
        return <div className="flex justify-center items-center h-full text-dark-text">جاري التحميل...</div>;
    }
    
    if (isEditing) {
        return (
            <>
                <AddProductModal 
                    isOpen={isModalOpen} 
                    onClose={() => setIsModalOpen(false)} 
                    onSave={addProduct} 
                />
                <div className="bg-white p-6 rounded-lg shadow-lg max-w-7xl mx-auto">
                     <h2 className="text-xl font-bold mb-4 border-b border-border pb-2 text-muted-text">تفاصيل العميل</h2>
                     <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <input type="text" name="clientName" placeholder="اسم العميل" value={quotation.clientName} onChange={handleInputChange} className={inputClasses} />
                        <input type="text" name="company" placeholder="الشركة" value={quotation.company} onChange={handleInputChange} className={inputClasses} />
                        <input type="text" name="project" placeholder="المشروع" value={quotation.project} onChange={handleInputChange} className={inputClasses} />
                        <input type="text" name="quotationType" placeholder="نوع العرض" value={quotation.quotationType} onChange={handleInputChange} className={inputClasses} />
                        <input type="date" name="date" value={quotation.date} onChange={handleInputChange} className={`${inputClasses}`} />
                        <select name="currency" value={quotation.currency} onChange={handleInputChange} className={inputClasses}>
                            <option value={Currency.SAR}>ريال سعودي (SAR)</option>
                            <option value={Currency.EGP}>جنيه مصري (EGP)</option>
                            <option value={Currency.USD}>دولار أمريكي (USD)</option>
                        </select>
                     </div>
                     
                     <h2 className="text-xl font-bold my-6 border-b border-border pb-2 text-muted-text">البنود</h2>
                     
                     <div className="hidden md:grid grid-cols-12 gap-x-2 mb-2 text-sm font-bold text-muted-text text-center">
                         <div className="col-span-4 text-right">المنتج / الوصف</div>
                         <div className="col-span-3">الأبعاد</div>
                         <div className="col-span-1">الكمية</div>
                         <div className="col-span-1">الوحدة</div>
                         <div className="col-span-1">السعر</div>
                         <div className="col-span-1">الإجمالي</div>
                         <div className="col-span-1"></div>
                     </div>

                     {quotation.items.map((item, index) => (
                        <div key={item.id || index} className="grid grid-cols-1 md:grid-cols-12 gap-y-4 gap-x-2 mb-4 p-4 bg-gray-50 rounded-lg md:p-2 md:bg-transparent md:rounded-none md:items-start">
                            {/* Product & Description */}
                            <div className="md:col-span-4 space-y-2">
                                <label className="text-xs text-muted-text md:hidden">المنتج / الوصف</label>
                                <select value={item.productId || 'custom'} onChange={(e) => handleProductSelection(index, e.target.value)} className={inputClasses}>
                                    <option value="custom">-- منتج مخصص --</option>
                                    {products.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
                                </select>
                                <textarea name="description" placeholder="الوصف" value={item.description} onChange={(e) => handleItemChange(index, e)} className={`${inputClasses} h-20`} />
                            </div>
                            {/* Dimensions */}
                            <div className="md:col-span-3">
                                {item.productType !== ProductType.SIMPLE && <label className="text-xs text-muted-text md:hidden">الأبعاد</label>}
                                <div className="flex gap-2">
                                    {item.productType === ProductType.DIFFUSER && (
                                        <>
                                            <input type="number" name="length" placeholder="الطول" value={item.length || ''} onChange={(e) => handleItemChange(index, e)} className={inputClasses} />
                                            <input type="number" name="width" placeholder="العرض" value={item.width || ''} onChange={(e) => handleItemChange(index, e)} className={inputClasses} />
                                        </>
                                    )}
                                    {item.productType === ProductType.CABLE_TRAY && (
                                        <>
                                            <input type="number" name="width" placeholder="العرض" value={item.width || ''} onChange={(e) => handleItemChange(index, e)} className={inputClasses} />
                                            <input type="number" name="height" placeholder="الارتفاع" value={item.height || ''} onChange={(e) => handleItemChange(index, e)} className={inputClasses} />
                                        </>
                                    )}
                                </div>
                            </div>
                            {/* Quantity */}
                            <div className="md:col-span-1">
                                <label className="text-xs text-muted-text md:hidden">الكمية</label>
                                <input type="number" name="quantity" placeholder="الكمية" value={item.quantity} onChange={(e) => handleItemChange(index, e)} className={inputClasses} />
                            </div>
                             {/* Unit */}
                            <div className="md:col-span-1">
                                <label className="text-xs text-muted-text md:hidden">الوحدة</label>
                                <select name="unit" value={item.unit} onChange={(e) => handleItemChange(index, e)} className={inputClasses} disabled={!!item.productId}>
                                    {Object.values(Unit).map(u => <option key={u} value={u}>{u}</option>)}
                                </select>
                            </div>
                            {/* Unit Price */}
                            <div className="md:col-span-1">
                                <label className="text-xs text-muted-text md:hidden">سعر الوحدة</label>
                                <input type="number" step="any" name="unitPrice" placeholder="السعر" value={item.unitPrice} onChange={(e) => handleItemChange(index, e)} className={inputClasses} />
                            </div>
                            {/* Total */}
                            <div className="md:col-span-1">
                                <label className="text-xs text-muted-text md:hidden">الإجمالي</label>
                                <input type="text" readOnly value={item.total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} className={`${inputClasses} bg-gray-200 cursor-not-allowed text-dark-text`} />
                            </div>
                            {/* Remove Button */}
                            <div className="md:col-span-1 flex items-center justify-end">
                                <button onClick={() => removeItem(index)} className="w-full md:w-auto bg-red-500/10 text-red-500 hover:bg-red-500/20 hover:text-red-600 font-bold p-2 rounded transition-colors text-xl">×</button>
                            </div>
                        </div>
                     ))}

                    <div className="flex flex-col sm:flex-row items-center gap-4 mt-4">
                        <button onClick={addItem} className="text-[#10B981] hover:text-[#059669] font-semibold transition-colors">+ إضافة بند جديد</button>
                        <button onClick={() => setIsModalOpen(true)} className="text-green-500 hover:text-green-400 font-semibold transition-colors">+ إضافة منتج للقائمة</button>
                    </div>

                     <div className="mt-8 flex justify-end gap-4">
                        <button onClick={() => isNew ? navigate('/quotations') : setIsEditing(false)} className={`bg-gray-500 hover:bg-gray-600 text-white ${buttonClasses}`} disabled={isSaving}>إلغاء</button>
                        <button onClick={handleSave} className={`bg-[#10B981] hover:bg-[#059669] text-white ${buttonClasses}`} disabled={isSaving}>
                            {isSaving ? 'جاري الحفظ...' : 'حفظ التغييرات'}
                        </button>
                     </div>
                </div>
            </>
        )
    }

    return (
        <>
            <div className="flex flex-col sm:flex-row sm:justify-end gap-3 mb-6">
                 <button
                    onClick={() => navigate('/quotations')}
                    className={`w-full sm:w-auto bg-gray-500 hover:bg-gray-600 text-white ${buttonClasses}`}
                    disabled={isProcessingPdf}
                >
                    العودة للقائمة
                </button>
                <button
                    onClick={() => setIsEditing(true)}
                    className={`w-full sm:w-auto bg-yellow-500 hover:bg-yellow-600 text-dark-text ${buttonClasses}`}
                    disabled={isProcessingPdf}
                >
                    تعديل
                </button>
                <button
                    onClick={handleShare}
                    className={`w-full sm:w-auto bg-green-500 hover:bg-green-600 text-white ${buttonClasses}`}
                    disabled={isProcessingPdf}
                    aria-busy={isProcessingPdf}
                >
                    {isProcessingPdf ? 'جاري التحضير...' : 'مشاركة عبر واتساب'}
                </button>
                <button
                    onClick={handleExportToPDF}
                    className={`w-full sm:w-auto bg-[#10B981] hover:bg-[#059669] text-white ${buttonClasses}`}
                    disabled={isProcessingPdf}
                    aria-busy={isProcessingPdf}
                >
                    {isProcessingPdf ? 'جاري التحضير...' : 'تصدير PDF'}
                </button>
            </div>
            <QuotationComponent quotation={quotation as Quotation} />
        </>
    );
};

export default QuotationEditorPage;

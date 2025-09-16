

import { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { supabase } from '../services/supabaseClient';
import { useAuth } from './useAuth';
import { useProducts } from '../contexts/ProductContext';
import { 
    Quotation, SalesInvoice, PurchaseInvoice, DocumentItem, DocumentItemState,
    ProductType, Unit, Currency, PurchaseInvoiceStatus, SalesInvoiceStatus,
    PartyType, AccountType
} from '../types';
import { generateDocumentNumber } from '../utils/numbering';

type DocumentType = 'quotation' | 'purchase_invoice' | 'sales_invoice';
type AnyDocument = Quotation | PurchaseInvoice | SalesInvoice;
type AnyDocumentState = AnyDocument & { items: DocumentItemState[] };

/**
 * Handles the accounting posting for a paid purchase invoice.
 * 1. Finds or creates a supplier account.
 * 2. Links the account to the invoice.
 * 3. Creates the corresponding journal entries (Debit Purchases, Credit Supplier).
 */
const postPurchaseInvoiceJournal = async (
    invoice: PurchaseInvoice, 
    invoiceId: number, 
    currentUserId: string | null,
    supabase: any // SupabaseClient
) => {
    const supplierName = invoice.supplierName.trim();
    if (!supplierName) {
        throw new Error("اسم المورد في الفاتورة لا يمكن أن يكون فارغاً.");
    }

    // --- New robust logic to find parent accounts ---
    let suppliersParentAccount: { id: number; name: string; code: string | null } | undefined;

    // 1. Try to find parent accounts by common names first, for performance.
    const { data: mainAccounts, error: mainAccountsError } = await supabase
        .from('accounts')
        .select('id, name, code')
        .in('name', ['الموردون', 'المشتريات', 'الموردين', 'الخصوم']); // Search for all possible names

    if (mainAccountsError) throw new Error(`فشل البحث عن الحسابات الرئيسية: ${mainAccountsError.message}`);

    suppliersParentAccount = mainAccounts.find((a: any) => a.name.trim() === 'الموردون' || a.name.trim() === 'الموردين');
    let purchasesAccount = mainAccounts.find((a: any) => a.name.trim() === 'المشتريات');
    const liabilitiesAccount = mainAccounts.find((a: any) => a.name.trim() === 'الخصوم');

    // 2. If not found by name, try to infer it from the data structure of existing suppliers.
    if (!suppliersParentAccount) {
        console.log("Could not find suppliers parent by name, attempting to infer...");
        const { data: supplierAccounts, error: supplierAccountsError } = await supabase
            .from('accounts')
            .select('parent_id')
            .eq('party_type', PartyType.SUPPLIER)
            .not('parent_id', 'is', null);

        if (supplierAccountsError) throw new Error(`فشل البحث عن حسابات الموردين الفرعية: ${supplierAccountsError.message}`);

        if (supplierAccounts && supplierAccounts.length > 0) {
            const parentIds = [...new Set(supplierAccounts.map(a => a.parent_id))];
            if (parentIds.length === 1) {
                const parentId = parentIds[0];
                console.log(`Inferred suppliers parent account ID: ${parentId}`);
                const { data: parentData, error: parentError } = await supabase.from('accounts').select('id, name, code').eq('id', parentId).single();
                if (parentError) throw new Error(`فشل جلب بيانات الحساب الأب للموردين: ${parentError.message}`);
                suppliersParentAccount = parentData;
            }
        }
    }

    // 3. If still not found, create it under "الخصوم".
    if (!suppliersParentAccount) {
        console.log("Suppliers parent account not found or inferred. Creating a new one under 'الخصوم'.");

        if (!liabilitiesAccount) {
            throw new Error("لم يتم العثور على حساب 'الخصوم' الرئيسي لإنشاء حساب الموردين تحته. يرجى التأكد من وجوده في دليل الحسابات.");
        }

        // Generate a new code for "الموردون" based on the parent "الخصوم"
        const parentCode = liabilitiesAccount.code || '2'; // Default to '2' for Liabilities
        const { data: childAccounts, error: childrenError } = await supabase
            .from('accounts')
            .select('code')
            .eq('parent_id', liabilitiesAccount.id);
        if (childrenError) throw new Error(`فشل جلب الحسابات الفرعية للخصوم: ${childrenError.message}`);
        
        let maxNum = 0;
        if (childAccounts) {
            for (const child of childAccounts) {
                if (child.code && child.code.startsWith(parentCode + '-')) {
                    const numPart = parseInt(child.code.split('-').pop() || '0', 10);
                    if (!isNaN(numPart) && numPart > maxNum) {
                        maxNum = numPart;
                    }
                }
            }
        }
        const newSuppliersCode = `${parentCode}-${maxNum + 1}`;

        const { data: newParentAccount, error: createParentError } = await supabase
            .from('accounts')
            .insert({
                name: 'الموردون',
                code: newSuppliersCode,
                account_type: AccountType.LIABILITY,
                party_type: PartyType.NONE,
                parent_id: liabilitiesAccount.id,
            })
            .select('id, name, code')
            .single();
        
        if (createParentError) {
            throw new Error(`فشل إنشاء حساب الموردين الرئيسي تلقائياً: ${createParentError.message}.`);
        }
        suppliersParentAccount = newParentAccount;
    }

    if (!suppliersParentAccount) throw new Error("فشل تحديد أو إنشاء حساب الموردين الرئيسي. يرجى مراجعة دليل الحسابات."); // Should be unreachable now

    // --- New robust logic for Purchases account ---
    // 1. If not found by name, try to find by a default code '501'.
    if (!purchasesAccount) {
        console.log("Purchases account not found by name, attempting to find by default code '501'...");
        const { data: accountByCode, error: codeError } = await supabase.from('accounts').select('id, name, code').eq('code', '501').maybeSingle();
        if (codeError) throw new Error(`فشل البحث عن حساب المشتريات الرئيسي بالكود: ${codeError.message}`);
        if (accountByCode) {
            console.log(`Found account '${accountByCode.name}' with code '501'. Using it as purchases account.`);
            purchasesAccount = accountByCode;
        }
    }

    // 2. If still not found, create it.
    if (!purchasesAccount) {
        console.log("Purchases account not found. Creating a new one.");
        const { data: newPurchasesAccount, error: createPurchasesError } = await supabase
            .from('accounts')
            .insert({
                name: 'المشتريات',
                code: '501', // A sensible default code for Expenses > Purchases
                account_type: AccountType.EXPENSE,
                party_type: PartyType.NONE,
                parent_id: null,
            })
            .select('id, name, code')
            .single();
        if (createPurchasesError) throw new Error(`فشل إنشاء حساب المشتريات الرئيسي تلقائياً: ${createPurchasesError.message}.`);
        purchasesAccount = newPurchasesAccount;
    }

    if (!purchasesAccount) throw new Error("فشل تحديد أو إنشاء حساب 'المشتريات' الرئيسي. يرجى مراجعة دليل الحسابات.");

    // 5. Find or create the specific supplier's account
    const { data: existingSupplier, error: findError } = await supabase.from('accounts').select('id').eq('name', supplierName).eq('parent_id', suppliersParentAccount.id).maybeSingle();
    if (findError) throw findError;

    let supplierAccountId = existingSupplier?.id;

    if (!supplierAccountId) {
        // --- New robust logic for generating sub-account code ---
        const { data: childAccounts, error: childrenError } = await supabase
            .from('accounts')
            .select('code')
            .eq('parent_id', suppliersParentAccount.id);

        if (childrenError) throw new Error(`فشل جلب الحسابات الفرعية للموردين: ${childrenError.message}`);
        
        const parentCode = suppliersParentAccount.code || '201';
        let maxNum = 0;
        if (childAccounts) {
            for (const child of childAccounts) {
                if (child.code && child.code.startsWith(parentCode + '-')) {
                    const numPart = parseInt(child.code.split('-').pop() || '0', 10);
                    if (!isNaN(numPart) && numPart > maxNum) {
                        maxNum = numPart;
                    }
                }
            }
        }
        
        const newCode = `${parentCode}-${maxNum + 1}`;
        const { data: newAccount, error: createError } = await supabase.from('accounts').insert({ name: supplierName, code: newCode, parent_id: suppliersParentAccount.id, account_type: AccountType.LIABILITY, party_type: PartyType.SUPPLIER }).select('id').single();
        if (createError) throw createError;
        supplierAccountId = newAccount.id;
    }

    // 6. Update the purchase invoice with the supplier_id
    const { error: updateInvoiceError } = await supabase.from('purchase_invoices').update({ supplier_id: supplierAccountId }).eq('id', invoiceId);
    if (updateInvoiceError) throw new Error(`فشل ربط المورد بالفاتورة: ${updateInvoiceError.message}`);

    // 7. Delete old journal entries for this invoice to make the process idempotent
    await supabase.from('journal_entries').delete().like('description', `فاتورة مشتريات رقم ${invoice.invoiceNumber}%`);

    // 8. Create the new journal entries
    const journalEntries = [
        { date: invoice.date, description: `فاتورة مشتريات رقم ${invoice.invoiceNumber} من ${supplierName}`, debit: invoice.totalAmount, credit: 0, account_id: purchasesAccount.id, created_by: currentUserId },
        { date: invoice.date, description: `فاتورة مشتريات رقم ${invoice.invoiceNumber}`, debit: 0, credit: invoice.totalAmount, account_id: supplierAccountId, created_by: currentUserId }
    ];

    const { error: journalError } = await supabase.from('journal_entries').insert(journalEntries);
    if (journalError) throw new Error(`فشل إنشاء القيد المحاسبي: ${journalError.message}`);
};

interface UseDocumentProps {
    documentType: DocumentType;
    id?: string;
}

export const getTaxInfo = (currency: Currency): { rate: number; label: string } => {
    switch (currency) {
        case Currency.EGP: return { rate: 0.14, label: 'Tax (14%)' };
        case Currency.SAR: return { rate: 0.15, label: 'Tax (15%)' };
        case Currency.USD: return { rate: 0, label: 'Tax (0%)' };
        default: return { rate: 0, label: 'Tax' };
    }
};

const docConfig = {
    quotation: {
        mainTable: 'quotations',
        itemsTable: 'quotation_items',
        foreignKey: 'quotation_id',
        listPath: '/quotations',
        viewPath: '/quotations/:id/view',
        dbFieldMap: {
            quotationNumber: 'quotation_number',
            clientName: 'client_name',
            company: 'company',
            project: 'project',
            quotationType: 'quotation_type',
            taxIncluded: 'tax_included',
            discount: null, // FIX: Removed direct mapping to prevent DB error. Discount is now a line item.
            supplierName: null,
            status: null,
            quotationId: null,
        },
        payloadMap: {
            quotation_number: 'quotationNumber',
            client_name: 'clientName',
            company: 'company',
            project: 'project',
            quotation_type: 'quotationType',
            tax_included: 'taxIncluded',
        }
    },
    purchase_invoice: {
        mainTable: 'purchase_invoices',
        itemsTable: 'purchase_invoice_items',
        foreignKey: 'invoice_id',
        listPath: '/purchase-invoices',
        viewPath: '/purchase-invoices/:id/view',
        dbFieldMap: {
            invoiceNumber: 'invoice_number',
            supplierName: 'supplier_name',
            status: 'status',
            supplierId: 'supplier_id',
            clientName: null,
            company: null,
            project: null,
            quotationType: null,
            quotationId: null,
        },
        payloadMap: {
            invoice_number: 'invoiceNumber',
            supplier_name: 'supplierName',
            status: 'status',
            supplier_id: 'supplierId',
        }
    },
    sales_invoice: {
        mainTable: 'sales_invoices',
        itemsTable: 'sales_invoice_items',
        foreignKey: 'invoice_id',
        listPath: '/sales-invoices',
        viewPath: '/sales-invoices/:id/view',
        dbFieldMap: {
            invoiceNumber: 'invoice_number',
            clientName: 'client_name',
            company: 'company',
            project: 'project',
            status: 'status',
            quotationId: 'quotation_id',
            supplierName: null,
            quotationType: null,
        },
        payloadMap: {
            invoice_number: 'invoiceNumber',
            client_name: 'clientName',
            company: 'company',
            project: 'project',
            status: 'status',
            quotation_id: 'quotationId',
        }
    },
};

export const useDocument = <T extends AnyDocumentState>({ documentType, id: idParam }: UseDocumentProps) => {
    const navigate = useNavigate();
    const { currentUser } = useAuth();
    const { products, fetchProducts } = useProducts();
    const config = docConfig[documentType];

    const [document, setDocument] = useState<T | null>(null);
    const [loading, setLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);
    const [saveError, setSaveError] = useState<string | null>(null);

    const isNew = idParam === 'new';

    const getNewDocumentDefault = useCallback(async (): Promise<T> => {
        // Number is now generated on save to have access to the latest data (e.g. tax status).
        const newDocNumber = 'جديد';
        const base = {
            id: undefined,
            date: new Date().toISOString().split('T')[0],
            items: [{
                id: -Date.now(), // Temporary unique ID for new items
                productName: '',
                description: '',
                quantity: 1,
                unitPrice: 0,
                total: 0,
                unit: Unit.COUNT
            }],
            totalAmount: 0,
            createdBy: currentUser?.id ?? null,
        };

        switch (documentType) {
            case 'quotation':
                return { ...base, quotationNumber: newDocNumber, clientName: '', company: '', project: '', quotationType: '', currency: Currency.EGP, taxIncluded: true, discount: 0 } as T;
            case 'purchase_invoice':
                return { ...base, invoiceNumber: newDocNumber, supplierName: '', status: PurchaseInvoiceStatus.DRAFT, currency: Currency.EGP } as T;
            case 'sales_invoice':
                return { ...base, invoiceNumber: newDocNumber, clientName: '', company: '', project: '', status: SalesInvoiceStatus.DRAFT, currency: Currency.EGP } as T;
            default:
                throw new Error("Invalid document type for new document generation.");
        }
    }, [config, currentUser, documentType]);

    useEffect(() => {
        const fetchDocument = async (docId: number) => {
            setLoading(true);
            try {
                // Fetch main document and its items concurrently for better performance.
                const [docResult, itemsResult] = await Promise.all([
                    supabase.from(config.mainTable).select('*').eq('id', docId).single(),
                    supabase.from(config.itemsTable).select('*').eq(config.foreignKey, docId)
                ]);

                const { data: docData, error: docError } = docResult;
                if (docError) throw docError;

                const { data: itemsData, error: itemsError } = itemsResult;
                if (itemsError) throw itemsError;

                // Fetch creator name
                let creatorName = 'غير معروف';
                if (docData.created_by) {
                    const { data: profile, error: profileError } = await supabase
                        .from('profiles')
                        .select('name')
                        .eq('id', docData.created_by)
                        .single();
                    
                    if (profile) creatorName = profile.name;
                    else if (profileError) console.warn(`Could not fetch creator name for user ${docData.created_by}:`, profileError.message);
                }

                // For quotations, extract the discount which is saved as a special line item.
                const discountItem = documentType === 'quotation' 
                    ? (itemsData || []).find(item => item.description === 'Discount' && item.quantity === 1)
                    : null;
                const discountAmount = discountItem ? -discountItem.unit_price : 0;
                // Filter out the discount item from the main items list.
                const regularItemsData = (itemsData || []).filter(item => item.id !== discountItem?.id);
                
                let wasTaxIncluded = true; // Default for quotations
                if (documentType === 'quotation') {
                    // If the `tax_included` column exists and has a value, use it.
                    if (docData.tax_included !== null && docData.tax_included !== undefined) {
                        wasTaxIncluded = docData.tax_included;
                    } else {
                        // Fallback logic for old records: infer if tax was included by checking totals.
                        const subTotal = regularItemsData.reduce((acc, item) => acc + (item.total || 0), 0);
                        const taxableAmount = subTotal - discountAmount;
                        const taxInfo = getTaxInfo(docData.currency as Currency);
                        const tax = taxableAmount * taxInfo.rate;
                        const grandTotalWithTax = taxableAmount + tax;
                        
                        // Compare the calculated total with the saved total.
                        const savedTotalAmount = docData.total_amount;
                        // Use a small tolerance for floating point comparison.
                        wasTaxIncluded = Math.abs(savedTotalAmount - grandTotalWithTax) < 0.01;
                    }
                }


                const augmentedItems: DocumentItemState[] = (regularItemsData || []).map(item => {
                    const product = products.find(p => p.id === item.product_id);
                    return {
                        id: item.id,
                        productId: item.product_id,
                        productName: product?.name,
                        description: item.description,
                        quantity: item.quantity,
                        unitPrice: item.unit_price,
                        total: item.total,
                        unit: (item.unit as Unit) || (product?.unit || Unit.COUNT),
                        productType: product?.productType || ProductType.SIMPLE,
                    };
                });
                
                const docFields = Object.entries(config.dbFieldMap).reduce((acc, [key, val]) => {
                    if (val) {
                        acc[key] = docData[val];
                    }
                    return acc;
                }, {} as any);

                if (documentType === 'quotation') {
                    docFields.discount = discountAmount;
                    docFields.taxIncluded = wasTaxIncluded;
                }
                
                const fetchedDoc = {
                    id: docData.id,
                    date: docData.date,
                    currency: docData.currency,
                    totalAmount: docData.total_amount,
                    createdBy: docData.created_by,
                    creatorName: creatorName,
                    items: augmentedItems,
                    ...docFields
                } as T;
                
                setDocument(fetchedDoc);

            } catch (error: any) {
                console.error(`Error fetching ${documentType}:`, error.message);
                navigate('/404');
            } finally {
                setLoading(false);
            }
        };

        // If data was passed via navigation state, we don't need to fetch.
        if (preloadedData) {
            setLoading(false);
            return;
        }

        if (isNew) {
            getNewDocumentDefault().then(setDocument).finally(() => setLoading(false));
        } else if (idParam && products.length > 0) {
            const docId = parseInt(idParam, 10);
            if (isNaN(docId)) {
                console.error(`Invalid document ID in URL: "${idParam}" for type "${documentType}"`);
                navigate('/404');
            } else {
                fetchDocument(docId);
            }
        }
    }, [idParam, navigate, currentUser, isNew, products, config, documentType, getNewDocumentDefault, preloadedData]);

    const handleSave = async () => {
        if (!document) return;

        // --- Validation ---
        if (documentType === 'quotation') {
            const quote = document as Quotation;
            if (!quote.clientName?.trim() && !quote.company?.trim()) {
                setSaveError("يجب إدخال اسم العميل أو اسم الشركة على الأقل.");
                return; // Stop before setting isSaving to true
            }
            if (quote.items.length === 0 || quote.items.every(item => !item.description?.trim())) {
                setSaveError("يجب أن يحتوي عرض السعر على بند واحد على الأقل.");
                return; // Stop before setting isSaving to true
            }
        }
        // --- End Validation ---

        setIsSaving(true);
        setSaveError(null);

        try {
            const { items, id, ...docDetails } = document;
            
            let discountToSaveAsItem = 0;
            if (documentType === 'quotation') {
                const quoteDetails = docDetails as any;
                discountToSaveAsItem = quoteDetails.discount || 0;
                delete quoteDetails.discount;
            }

            const payload = Object.entries(config.payloadMap).reduce((acc, [dbKey, docKey]) => {
                const value = docDetails[docKey as keyof typeof docDetails];
                if (value !== undefined) {
                    acc[dbKey] = value;
                }
                return acc;
            }, {} as any);
            
            payload.date = docDetails.date;
            payload.currency = docDetails.currency;
            payload.total_amount = docDetails.totalAmount;

            let savedDocId = id;

            if (isNew) {
                let numberField = '';
                if (documentType === 'quotation' || documentType === 'sales_invoice' || documentType === 'purchase_invoice') {
                    let prefix = '';

                    if (documentType === 'quotation') {
                        const quote = document as Quotation;
                        prefix = quote.taxIncluded ? 'TQUOT' : 'QUOT';
                        numberField = 'quotation_number';
                    } else if (documentType === 'sales_invoice') {
                        prefix = 'SINV';
                        numberField = 'invoice_number';
                    } else if (documentType === 'purchase_invoice') {
                        prefix = 'PINV';
                        numberField = 'invoice_number';
                    }
                    
                    payload[numberField] = await generateDocumentNumber(supabase, config.mainTable, numberField, prefix);
                }
                payload.created_by = currentUser?.id ?? null;
                const { data: newDoc, error } = await supabase.from(config.mainTable).insert(payload).select('id').single();
                if (error || !newDoc) throw error || new Error('Failed to create document');
                savedDocId = newDoc.id;
            } else {
                const { error } = await supabase.from(config.mainTable).update(payload).eq('id', id);
                if (error) throw error;
                await supabase.from(config.itemsTable).delete().eq(config.foreignKey, id);
            }

            const finalItems = [...items];
            if (documentType === 'quotation' && discountToSaveAsItem > 0) {
                finalItems.push({
                    description: 'Discount',
                    quantity: 1,
                    unitPrice: -discountToSaveAsItem,
                    total: -discountToSaveAsItem,
                    unit: Unit.COUNT,
                    productId: null,
                    productType: ProductType.SIMPLE
                } as DocumentItemState);
            }


            if (finalItems.length > 0) {
                const itemsToInsert = finalItems.map(item => ({
                    [config.foreignKey]: savedDocId,
                    product_id: item.productId || null,
                    description: item.description,
                    quantity: item.quantity,
                    unit_price: item.unitPrice,
                    total: item.total,
                    unit: item.unit,
                }));
                const { error: itemsError } = await supabase.from(config.itemsTable).insert(itemsToInsert);
                if (itemsError) throw itemsError;
            }

            let journalError = null;
            // --- New: Trigger journal entry creation for PAID sales invoices ---
            if (documentType === 'sales_invoice' && document.status === SalesInvoiceStatus.PAID) {
                console.log(`Sales invoice ${savedDocId} is PAID. Triggering journal entry creation...`);
                try {
                    const { data: functionData, error: functionError } = await supabase.functions.invoke('create-journal-from-invoice', {
                        body: { invoiceId: savedDocId },
                    });

                    if (functionError) throw functionError;
                    if (functionData?.error) throw new Error(functionData.error);

                    console.log('Successfully triggered journal entry creation.');
                } catch (e: any) {
                    journalError = e;
                }
            }

            // --- New: Trigger journal entry creation for PAID purchase invoices ---
            if (documentType === 'purchase_invoice' && document.status === PurchaseInvoiceStatus.PAID) {
                console.log(`Purchase invoice ${savedDocId} is PAID. Triggering journal entry creation...`);
                try {
                    await postPurchaseInvoiceJournal(document as PurchaseInvoice, savedDocId!, currentUser?.id ?? null, supabase);
                    console.log('Successfully created journal entry for purchase invoice.');
                } catch (e: any) {
                    journalError = e;
                }
            }

            await fetchProducts();

            if (journalError) {
                const warningMessage = `تم حفظ الفاتورة بنجاح، ولكن فشل إنشاء القيد المحاسبي: ${journalError.message}`;
                console.error('Journal entry creation failed:', journalError);
                setSaveError(warningMessage);
                // NOTE: We don't navigate away, so the user can see the error on the form.
            } else {
                // Construct the full object to pass to the view page, avoiding a refetch race condition.
                const docNumberPropertyName = isNew && numberField ? (config.payloadMap as any)[numberField] : null;

                const fullDocumentObject: any = {
                    ...document,
                    id: savedDocId,
                    creatorName: document.creatorName || currentUser?.name || 'غير معروف',
                };
                if (docNumberPropertyName && numberField && payload[numberField]) {
                    fullDocumentObject[docNumberPropertyName] = payload[numberField];
                }
                navigate(config.viewPath.replace(':id', savedDocId!.toString()), { state: { preloadedData: fullDocumentObject as AnyDocumentState }, replace: true });
            }

        } catch (error: any) {
            console.error(`Failed to save ${documentType}:`, error.message);
            setSaveError(`حدث خطأ أثناء حفظ المستند: ${error.message}`);
        } finally {
            setIsSaving(false);
        }
    };
    
    return { document, setDocument, loading, isSaving, saveError, handleSave };
};
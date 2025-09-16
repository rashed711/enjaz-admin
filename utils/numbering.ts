import { SupabaseClient } from '@supabase/supabase-js';

/**
 * Generates a unique, sequential, date-based document number.
 * Example: "SINV-27-05-2024-1001"
 * @param supabase The Supabase client instance.
 * @param tableName The name of the table to query for the last document.
 * @param numberField The name of the column that stores the document number.
 * @param prefix The prefix for the document number (e.g., "SINV", "QUOT").
 * @returns A promise that resolves to the new document number string.
 */
export const generateDocumentNumber = async (
    supabase: SupabaseClient,
    tableName: string,
    numberField: string,
    prefix: string
): Promise<string> => {
    const today = new Date();
    const day = String(today.getDate()).padStart(2, '0');
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const year = today.getFullYear();
    const dateString = `${day}-${month}-${year}`;
    const numberPrefix = `${prefix}-${dateString}-`;

    const { data: lastDoc, error } = await supabase.from(tableName).select(numberField).like(numberField, `${numberPrefix}%`).order('created_at', { ascending: false }).limit(1).single();

    if (error && error.code !== 'PGRST116') throw error; // PGRST116 means no rows found, which is fine.

    let nextSeqNum = 1001;
    if (lastDoc) {
        const lastNum = parseInt((lastDoc as any)[numberField].split('-').pop() || '1000', 10);
        if (!isNaN(lastNum)) nextSeqNum = lastNum + 1;
    }
    return `${numberPrefix}${nextSeqNum}`;
};
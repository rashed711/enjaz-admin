import { useState, useEffect, useCallback, useRef } from 'react';
import { supabase } from '../services/supabaseClient';
import { useAuth } from './useAuth';
import { usePermissions } from './usePermissions';
import { PermissionModule, PermissionAction } from '../types';

interface UsePaginatedListProps<T> {
    tableName: string;
    permissionModule: PermissionModule;
    formatter: (item: any) => T;
    searchQuery?: string;
    searchColumns?: string[];
    itemsPerPage?: number;
}

export const usePaginatedList = <T extends { createdBy?: string | null; creatorName?: string; createdAt?: string }>({
    tableName,
    permissionModule,
    formatter,
    searchQuery = '',
    searchColumns = [],
    itemsPerPage = 15,
}: UsePaginatedListProps<T>) => {
    const [items, setItems] = useState<T[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [totalCount, setTotalCount] = useState(0);
    const [currentPage, setCurrentPage] = useState(1);
    const { currentUser, loading: isAuthLoading } = useAuth();
    const permissions = usePermissions();

    // This ref-based approach makes the hook resilient to unstable `formatter` and `searchColumns` props,
    // which can cause infinite loops if they are redefined on every render in the parent component.
    const formatterRef = useRef(formatter);
    const searchColumnsRef = useRef(searchColumns);
    useEffect(() => { formatterRef.current = formatter; }, [formatter]);
    useEffect(() => { searchColumnsRef.current = searchColumns; }, [searchColumns]);

    const canViewAll = permissions.can(permissionModule, PermissionAction.VIEW_ALL);
    const canViewOwn = permissions.can(permissionModule, PermissionAction.VIEW_OWN);

    const [refetchCount, setRefetchCount] = useState(0);
    const refetch = useCallback(() => setRefetchCount(c => c + 1), []);

    // This is the main data fetching effect.
    // It is triggered by changes in pagination, search, user, or permissions.
    // It intentionally does not depend on `formatter` or `searchColumns` to prevent loops.
    // Instead, it uses refs to get the latest versions of those props inside the fetch logic.
    useEffect(() => {
        const controller = new AbortController();
        const signal = controller.signal;

        const doFetch = async () => {
            if (isAuthLoading || !currentUser) {
                // If auth is still loading or there's no user, don't fetch.
                // Clear previous data to avoid showing stale info on logout.
                setItems([]);
                setTotalCount(0);
                setLoading(false);
                return;
            }

            setLoading(true);
            setError(null);
            try {
                if (!canViewAll && !canViewOwn) {
                    setItems([]);
                    setTotalCount(0);
                    return;
                }

                const from = (currentPage - 1) * itemsPerPage;
                const to = from + itemsPerPage - 1;

                let query = supabase.from(tableName).select('*', { count: 'exact' });

                if (!canViewAll && canViewOwn) {
                    query = query.eq('created_by', currentUser.id);
                }

                if (searchQuery && searchColumnsRef.current.length > 0) {
                    const orFilter = searchColumnsRef.current.map(column => `${column}.ilike.%${searchQuery}%`).join(',');
                    query = query.or(orFilter);
                }
                
                const { data, error, count } = await query
                    .order('date', { ascending: false, nullsFirst: false })
                    .order('id', { ascending: false })
                    .range(from, to)
                    .abortSignal(signal);

                if (signal.aborted) return;
                if (error) throw error;

                if (data) {
                    const formattedItems = data.map(formatterRef.current);
                    const creatorIds = [...new Set(formattedItems.map(item => item.createdBy).filter(Boolean))];

                    if (creatorIds.length > 0) {
                        const { data: profiles, error: profilesError } = await supabase
                            .from('profiles')
                            .select('id, name')
                            .in('id', creatorIds)
                            .abortSignal(signal);
                        
                        if (signal.aborted) return;

                        if (profilesError) {
                            console.warn(`Could not fetch creator names for ${tableName}:`, profilesError.message);
                            setItems(formattedItems);
                        } else {
                            const creatorMap = new Map((profiles || []).map(p => [p.id, p.name]));
                            const enrichedItems = formattedItems.map(item => ({
                                ...item,
                                creatorName: creatorMap.get(item.createdBy) || item.creatorName || 'غير معروف'
                            }));
                            setItems(enrichedItems);
                        }
                    } else {
                        setItems(formattedItems);
                    }
                    setTotalCount(count ?? 0);
                } else {
                    // If data is null/undefined for any reason, reset the state.
                    setItems([]);
                    setTotalCount(0);
                }
            } catch (e: any) {
                if (e.name !== 'AbortError') {
                    console.error(`An unexpected error occurred while fetching ${tableName}:`, e.message);
                    setError(`فشل تحميل البيانات. الخطأ: ${e.message}`);
                    setItems([]);
                    setTotalCount(0);
                }
            } finally {
                if (!signal.aborted) {
                    setLoading(false);
                }
            }
        };
        
        doFetch();
        return () => controller.abort();
    }, [currentUser, isAuthLoading, currentPage, itemsPerPage, tableName, searchQuery, permissionModule, canViewAll, canViewOwn, refetchCount]);

    // Reset to page 1 when search query changes
    useEffect(() => {
        if (currentPage !== 1) {
            setCurrentPage(1);
        }
    }, [searchQuery]);

    return { items, loading, error, totalCount, currentPage, setCurrentPage, itemsPerPage, refetch };
};

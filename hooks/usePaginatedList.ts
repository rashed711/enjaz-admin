import { useState, useEffect, useCallback } from 'react';
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
    const [totalCount, setTotalCount] = useState(0);
    const [currentPage, setCurrentPage] = useState(1);
    const { currentUser, loading: isAuthLoading } = useAuth();
    const permissions = usePermissions();

    const fetchData = useCallback(async (signal: AbortSignal) => {
        if (isAuthLoading || !currentUser) return;

        setLoading(true);
        try {
            const canViewAll = permissions.can(permissionModule, PermissionAction.VIEW_ALL);
            const canViewOwn = permissions.can(permissionModule, PermissionAction.VIEW_OWN);

            if (!canViewAll && !canViewOwn) {
                setItems([]);
                setTotalCount(0);
                setLoading(false);
                return;
            }

            const from = (currentPage - 1) * itemsPerPage;
            const to = from + itemsPerPage - 1;

            let query = supabase.from(tableName).select('*', { count: 'exact' });

            if (!canViewAll && canViewOwn) {
                query = query.eq('created_by', currentUser.id);
            }

            if (searchQuery && searchColumns.length > 0) {
                const orFilter = searchColumns.map(column => `${column}.ilike.%${searchQuery}%`).join(',');
                query = query.or(orFilter);
            }
            
            const { data, error, count } = await query
                // Sort by date first, then by ID for consistent ordering.
                // This is more robust than sorting by 'created_at' which might be missing from some views.
                .order('date', { ascending: false })
                .order('id', { ascending: false })
                .range(from, to)
                .abortSignal(signal);

            if (signal.aborted) return;

            if (error) {
                if (error.name === 'AbortError') return;
                console.error(`Error fetching ${tableName}:`, error.message);
                setItems([]);
            } else if (data) {
                const formattedItems = data.map(formatter);
                const creatorIds = [...new Set(formattedItems.map(item => item.createdBy).filter(Boolean))];

                if (creatorIds.length > 0) {
                    const { data: profiles, error: profilesError } = await supabase
                        .from('profiles')
                        .select('id, name')
                        .in('id', creatorIds);
                    
                    if (profilesError && profilesError.name !== 'AbortError') {
                        console.warn(`Could not fetch creator names for ${tableName}:`, profilesError.message);
                        setItems(formattedItems);
                    } else {
                        const creatorMap = new Map(profiles.map(p => [p.id, p.name]));
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
            }
        } catch (e: any) {
            if (e.name !== 'AbortError') {
                console.error(`An unexpected error occurred while fetching ${tableName}:`, e.message);
                setItems([]);
            }
        } finally {
            if (!signal.aborted) {
                setLoading(false);
            }
        }
    }, [ currentUser, isAuthLoading, currentPage, itemsPerPage, tableName, formatter, searchQuery, searchColumns, permissions, permissionModule ]);

    const [refetchCount, setRefetchCount] = useState(0);
    const refetch = useCallback(() => setRefetchCount(c => c + 1), []);

    useEffect(() => {
        const controller = new AbortController();
        if (!isAuthLoading && currentUser) {
            fetchData(controller.signal);
        } else if (!isAuthLoading && !currentUser) {
            setLoading(false);
            setItems([]);
            setTotalCount(0);
        }
        return () => controller.abort();
    }, [fetchData, refetchCount, isAuthLoading, currentUser]);

    return { items, loading, totalCount, currentPage, setCurrentPage, itemsPerPage, refetch };
};
import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, FlatList, Pressable, ActivityIndicator, RefreshControl } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import type { RootStackParamList } from '@/types';
import { ChevronLeft, ArrowUpRight, ArrowDownLeft, PhoneCall, Calendar, Activity } from 'lucide-react-native';

import { useTokens } from '@/theme';
import { IconButton } from '@/components/Button';
import { Card } from '@/components/Card';
import { SectionLabel } from '@/components/SectionLabel';
import { api } from '@/services/api';
import { CustomDialog } from '@/components/Dialog';
import { Avatar } from '@/components/Avatar';
import { CallsListSkeleton } from '@/components/ListItemSkeleton';

export default function CallsScreen() {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const nav = useNavigation<NativeStackNavigationProp<RootStackParamList>>();

  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [refreshing, setRefreshing] = useState(false);

  // Calls list & pagination state
  const [callsList, setCallsList] = useState<any[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);

  // Statistics state
  const [stats, setStats] = useState<any>(null);

  // Filter state
  const [directionFilter, setDirectionFilter] = useState<'all' | 'inbound' | 'outbound'>('all');

  // Dialog State
  const [dialogConfig, setDialogConfig] = useState<{
    visible: boolean;
    title: string;
    message: string;
    buttons: any[];
  }>({
    visible: false,
    title: '',
    message: '',
    buttons: [],
  });

  const showDialog = (title: string, message: string, buttons: any[] = [{ text: 'OK' }]) => {
    setDialogConfig({ visible: true, title, message, buttons });
  };

  const fetchStats = useCallback(async () => {
    try {
      const statsRes = await api.get('/v1/calls/statistics');
      if (statsRes.success) {
        setStats(statsRes.data);
      }
    } catch (err) {
      console.warn('Failed to load call statistics:', err);
    }
  }, []);

  const fetchCalls = useCallback(async (pageNum = 1, filterDir = directionFilter, append = false, isRefresh = false) => {
    if (pageNum === 1 && !isRefresh) {
      setLoading(true);
    } else if (pageNum > 1) {
      setLoadingMore(true);
    }

    try {
      let url = `/v1/calls?page=${pageNum}&per_page=15`;
      if (filterDir !== 'all') {
        url += `&direction=${filterDir}`;
      }

      const response = await api.get(url);
      
      const rawData = response?.data || [];
      const pagination = response?.pagination || {};
      const currentPageNum = pagination.current_page || 1;
      const lastPageNum = pagination.last_page || 1;

      setPage(currentPageNum);
      setLastPage(lastPageNum);

      if (append) {
        setCallsList((prev) => {
          const combined = [...prev, ...rawData];
          const seenIds = new Set();
          return combined.filter((c) => {
            if (seenIds.has(c.id)) return false;
            seenIds.add(c.id);
            return true;
          });
        });
      } else {
        setCallsList(rawData);
      }
    } catch (err: any) {
      console.warn('Failed to load calls list:', err);
      showDialog('Error', 'Could not load call log from server.');
    } finally {
      setLoading(false);
      setRefreshing(false);
      setLoadingMore(false);
    }
  }, [directionFilter]);

  // Initial load
  useEffect(() => {
    fetchStats();
    fetchCalls(1, directionFilter, false, false);
  }, [directionFilter, fetchStats, fetchCalls]);

  const onRefresh = () => {
    setRefreshing(true);
    fetchStats();
    fetchCalls(1, directionFilter, false, true);
  };

  const loadMore = () => {
    if (loading || loadingMore || page >= lastPage) return;
    fetchCalls(page + 1, directionFilter, true, false);
  };

  const formatDuration = (seconds: number) => {
    if (!seconds) return 'No answer';
    if (seconds < 60) return `${seconds}s`;
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return secs > 0 ? `${mins}m ${secs}s` : `${mins}m`;
  };

  const limitInfo = stats?.usage_limits || {};
  const maxMinutes = limitInfo.max_call_minutes_per_month || 0;
  const minutesUsed = limitInfo.minutes_used || 0;
  const limitPercentage = maxMinutes > 0 ? Math.min((minutesUsed / maxMinutes) * 100, 100) : 0;

  return (
    <View className="flex-1 bg-bg dark:bg-d-bg" style={{ paddingTop: insets.top }}>
      {/* Header */}
      <View className="flex-row items-center gap-[10px] px-3 pb-3 border-b border-hairline dark:border-d-hairline">
        <IconButton icon={ChevronLeft} onPress={() => nav.goBack()} />
        <Text className="text-[17px] font-bold text-ink dark:text-d-ink">Call Logs</Text>
      </View>

      {/* FlatList combining Stats, Filters, and Calls Log */}
      <FlatList
        data={callsList}
        keyExtractor={(item) => String(item.id)}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor={tokens.accent}
          />
        }
        contentContainerStyle={{ paddingBottom: insets.bottom + 24 }}
        onEndReached={loadMore}
        onEndReachedThreshold={0.4}
        ListHeaderComponent={
          <View className="px-[18px] pt-4 gap-4">
            {/* Usage limits banner */}
            {maxMinutes > 0 && (
              <Card pad={12}>
                <View className="flex-row justify-between items-center mb-1.5">
                  <Text className="text-xs font-bold text-muted dark:text-d-muted uppercase tracking-wide">Monthly Limit Usage</Text>
                  <Text className="text-xs font-semibold text-ink dark:text-d-ink">{minutesUsed} / {maxMinutes} min</Text>
                </View>
                <View className="w-full h-2 bg-surface2 dark:bg-d-surface2 rounded-full overflow-hidden">
                  <View 
                    style={{ width: `${limitPercentage}%` }} 
                    className="h-full bg-accent dark:bg-d-accent rounded-full" 
                  />
                </View>
                <Text className="text-[10px] text-muted dark:text-d-muted mt-2">
                  Subscription resets on the billing date.
                </Text>
              </Card>
            )}

            {/* Quick Stats Grid */}
            <View className="flex-row gap-3">
              <View className="flex-1">
                <Card pad={12}>
                  <PhoneCall size={16} color={tokens.accent} />
                  <Text className="text-[10px] text-muted dark:text-d-muted mt-1 font-bold uppercase tracking-wide">Total Calls</Text>
                  <Text className="text-base font-bold text-ink dark:text-d-ink mt-0.5">{stats?.total_calls || 0}</Text>
                </Card>
              </View>
              <View className="flex-1">
                <Card pad={12}>
                  <Activity size={16} color={tokens.ok} />
                  <Text className="text-[10px] text-muted dark:text-d-muted mt-1 font-bold uppercase tracking-wide">Answered</Text>
                  <Text className="text-base font-bold text-ink dark:text-d-ink mt-0.5">{stats?.answered_calls || 0}</Text>
                </Card>
              </View>
              <View className="flex-1">
                <Card pad={12}>
                  <Calendar size={16} color={tokens.warn} />
                  <Text className="text-[10px] text-muted dark:text-d-muted mt-1 font-bold uppercase tracking-wide">Duration</Text>
                  <Text className="text-base font-bold text-ink dark:text-d-ink mt-0.5" numberOfLines={1}>
                    {Math.round(stats?.total_duration_minutes || 0)}m
                  </Text>
                </Card>
              </View>
            </View>

            {/* Direction filter segmented control */}
            <View className="flex-row bg-surface dark:bg-d-surface p-1 rounded-lg border border-hairline dark:border-d-hairline">
              {([
                { id: 'all', label: 'All' },
                { id: 'inbound', label: 'Inbound' },
                { id: 'outbound', label: 'Outbound' },
              ] as const).map((opt) => {
                const isSelected = directionFilter === opt.id;
                return (
                  <Pressable
                    key={opt.id}
                    onPress={() => setDirectionFilter(opt.id)}
                    className={`flex-1 py-1.5 rounded items-center ${
                      isSelected ? 'bg-surface2 dark:bg-d-surface2 border border-faint dark:border-d-faint' : 'bg-transparent'
                    }`}
                  >
                    <Text className={`text-xs font-semibold ${isSelected ? 'text-accent dark:text-d-accent' : 'text-muted dark:text-d-muted'}`}>
                      {opt.label}
                    </Text>
                  </Pressable>
                );
              })}
            </View>

            <SectionLabel>Logs</SectionLabel>
          </View>
        }
        renderItem={({ item }) => {
          const isMissed = item.status === 'missed' || item.status === 'rejected' || item.status === 'failed';
          const isOutbound = item.direction === 'outbound';
          const arrowIconColor = isMissed ? tokens.danger : isOutbound ? tokens.accent : tokens.ok;
          const statusBgColor = isMissed ? 'bg-danger/10' : isOutbound ? 'bg-accent/10' : 'bg-ok/10';
          const statusTextStyle = isMissed ? 'text-danger' : isOutbound ? 'text-accent' : 'text-ok';

          return (
            <View className="mx-[18px] mb-2 px-3 py-3 rounded-lg bg-surface dark:bg-d-surface border border-hairline dark:border-d-hairline flex-row items-center gap-3">
              <Avatar name={item.contact?.name || item.to_number || 'Unknown'} size={38} />

              <View className="flex-1 min-w-0">
                <View className="flex-row items-center gap-1.5 font-semibold">
                  <Text className="text-sm font-semibold text-ink dark:text-d-ink flex-shrink" numberOfLines={1}>
                    {item.contact?.name || item.to_number || 'Unknown'}
                  </Text>
                  {isOutbound ? (
                    <ArrowUpRight size={13} color={arrowIconColor} strokeWidth={2.2} />
                  ) : (
                    <ArrowDownLeft size={13} color={arrowIconColor} strokeWidth={2.2} />
                  )}
                </View>
                <Text className="text-xs text-muted dark:text-d-muted mt-0.5">
                  {item.created_at ? new Date(item.created_at).toLocaleString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : ''}
                </Text>
              </View>

              <View className="items-end gap-1">
                <View className={`px-2 py-0.5 rounded-full ${statusBgColor}`}>
                  <Text className={`text-[10px] font-bold uppercase tracking-wider ${statusTextStyle}`}>
                    {item.status}
                  </Text>
                </View>
                <Text className="text-xs text-muted dark:text-d-muted font-mono font-medium">
                  {formatDuration(item.duration_seconds)}
                </Text>
                {item.cost_amount > 0 && (
                  <Text className="text-[10px] text-muted dark:text-d-muted font-medium">
                    ${parseFloat(item.cost_amount).toFixed(2)}
                  </Text>
                )}
              </View>
            </View>
          );
        }}
        ListEmptyComponent={
          loading ? (
            <View className="flex-1 mt-4">
              <CallsListSkeleton count={6} />
            </View>
          ) : (
            <View className="py-20 items-center justify-center">
              <Text className="text-sm text-muted dark:text-d-muted italic">No call history found.</Text>
            </View>
          )
        }
        ListFooterComponent={
          loadingMore ? (
            <View className="py-4 items-center justify-center">
              <ActivityIndicator size="small" color={tokens.accent} />
            </View>
          ) : null
        }
      />

      <CustomDialog
        visible={dialogConfig.visible}
        title={dialogConfig.title}
        message={dialogConfig.message}
        buttons={dialogConfig.buttons}
        onClose={() => setDialogConfig((c) => ({ ...c, visible: false }))}
      />
    </View>
  );
}

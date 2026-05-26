import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, FlatList, TextInput, Pressable, RefreshControl, ActivityIndicator } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import type { RootStackParamList } from '@/types';
import { ChevronLeft, Search, ShieldAlert, History } from 'lucide-react-native';

import { useTokens } from '@/theme';
import { IconButton } from '@/components/Button';
import { Avatar } from '@/components/Avatar';
import { api } from '@/services/api';
import { CustomDialog } from '@/components/Dialog';

export default function ActivitiesScreen() {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const nav = useNavigation<NativeStackNavigationProp<RootStackParamList>>();

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [activities, setActivities] = useState<any[]>([]);
  const [searchQuery, setSearchQuery] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);

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

  const fetchActivities = useCallback(async (page = 1, isRefresh = false) => {
    if (page === 1 && !isRefresh) setLoading(true);
    try {
      const url = `/v1/mobile/activities?page=${page}${searchQuery ? `&search=${encodeURIComponent(searchQuery)}` : ''}`;
      const response = await api.get(url);
      
      const newItems = response.data || [];
      if (page === 1) {
        setActivities(newItems);
      } else {
        setActivities((prev) => [...prev, ...newItems]);
      }
      setCurrentPage(response.current_page || 1);
      setLastPage(response.last_page || 1);
    } catch (err: any) {
      console.warn('Failed to fetch activity logs:', err);
      showDialog('Error', 'Could not fetch activity logs from server.');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [searchQuery]);

  useEffect(() => {
    const delayDebounce = setTimeout(() => {
      fetchActivities(1);
    }, 400);

    return () => clearTimeout(delayDebounce);
  }, [searchQuery]);

  const onRefresh = () => {
    setRefreshing(true);
    fetchActivities(1, true);
  };

  const loadMore = () => {
    if (currentPage < lastPage && !loading) {
      fetchActivities(currentPage + 1);
    }
  };

  return (
    <View className="flex-1 bg-bg dark:bg-d-bg" style={{ paddingTop: insets.top }}>
      {/* Header */}
      <View className="flex-row items-center gap-[10px] px-3 pb-3 border-b border-hairline dark:border-d-hairline">
        <IconButton icon={ChevronLeft} onPress={() => nav.goBack()} />
        <Text className="text-[17px] font-bold text-ink dark:text-d-ink flex-1">Activity Logs</Text>
      </View>

      {/* Search Input */}
      <View className="px-4 py-2.5">
        <View className="flex-row items-center bg-surface2 dark:bg-d-surface2 rounded-lg px-3 py-1.5 gap-2">
          <Search size={16} color={tokens.muted} strokeWidth={1.6} />
          <TextInput
            value={searchQuery}
            onChangeText={setSearchQuery}
            placeholder="Search activity logs..."
            placeholderTextColor={tokens.muted}
            className="flex-1 text-ink dark:text-d-ink text-[14px] p-0"
          />
        </View>
      </View>

      {/* List */}
      {loading && activities.length === 0 ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator size="large" color={tokens.accent} />
          <Text className="text-xs text-muted dark:text-d-muted mt-2">Loading logs...</Text>
        </View>
      ) : (
        <FlatList
          data={activities}
          keyExtractor={(item) => String(item.id)}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={onRefresh}
              tintColor={tokens.accent}
            />
          }
          onEndReached={loadMore}
          onEndReachedThreshold={0.3}
          renderItem={({ item }) => {
            const userName = item.user?.name || 'System';
            return (
              <Pressable
                onPress={() => nav.navigate('ActivityDetail', { activityId: item.id })}
                className="flex-row items-center gap-3.5 py-3.5 px-[18px] border-b border-hairline dark:border-d-hairline active:bg-surface2 dark:active:bg-d-surface2"
              >
                <Avatar name={userName} size={38} ring={tokens.faint} />
                <View className="flex-1 min-w-0">
                  <Text numberOfLines={2} className="text-[13.5px] font-medium text-ink dark:text-d-ink leading-relaxed">
                    {item.description}
                  </Text>
                  <View className="flex-row items-center gap-1.5 mt-1">
                    <Text className="text-[11px] text-muted dark:text-d-muted">by {userName}</Text>
                    <Text className="text-[11px] text-muted dark:text-d-muted">·</Text>
                    <Text className="text-[11px] text-muted dark:text-d-muted">
                      {new Date(item.created_at).toLocaleDateString()} {new Date(item.created_at).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', hour12: false })}
                    </Text>
                  </View>
                </View>
                <ChevronLeft size={16} color={tokens.muted} style={{ transform: [{ rotate: '180deg' }] }} />
              </Pressable>
            );
          }}
          ListEmptyComponent={
            <View className="py-20 items-center justify-center px-6">
              <History size={32} color={tokens.muted} strokeWidth={1.5} />
              <Text className="text-sm text-muted dark:text-d-muted mt-3 text-center">No activity logs found.</Text>
            </View>
          }
          ListFooterComponent={
            currentPage < lastPage ? (
              <View className="py-4">
                <ActivityIndicator size="small" color={tokens.accent} />
              </View>
            ) : (
              <View className="h-[40px]" />
            )
          }
        />
      )}

      {/* Reusable Dialog */}
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

import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, FlatList, Pressable, ActivityIndicator, RefreshControl } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import type { RootStackParamList } from '@/types';
import { ChevronLeft, ArrowUpRight, ArrowDownLeft, Star, ChevronRight } from 'lucide-react-native';

import { useTokens } from '@/theme';
import { IconButton } from '@/components/Button';
import { Avatar } from '@/components/Avatar';
import { StarredListSkeleton } from '@/components/ListItemSkeleton';
import { api } from '@/services/api';
import { CustomDialog } from '@/components/Dialog';

export default function StarredMessagesScreen() {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const nav = useNavigation<NativeStackNavigationProp<RootStackParamList>>();

  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [fetchingConversation, setFetchingConversation] = useState(false);

  // Starred messages list & pagination state
  const [messages, setMessages] = useState<any[]>([]);
  const [page, setPage] = useState(1);
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

  const fetchStarredMessages = useCallback(async (pageNum = 1, append = false, isRefresh = false) => {
    if (pageNum === 1 && !isRefresh) {
      setLoading(true);
    } else if (pageNum > 1) {
      setLoadingMore(true);
    }

    try {
      const response = await api.get(`/v1/mobile/messages/starred?page=${pageNum}&per_page=15`);
      
      const rawData = response?.data || [];
      const currentPageNum = response?.current_page || 1;
      const lastPageNum = response?.last_page || 1;

      setPage(currentPageNum);
      setLastPage(lastPageNum);

      if (append) {
        setMessages((prev) => {
          const combined = [...prev, ...rawData];
          const seenIds = new Set();
          return combined.filter((m) => {
            if (seenIds.has(m.id)) return false;
            seenIds.add(m.id);
            return true;
          });
        });
      } else {
        setMessages(rawData);
      }
    } catch (err: any) {
      console.warn('Failed to load starred messages list:', err);
      showDialog('Error', 'Could not load starred messages from server.');
    } finally {
      setLoading(false);
      setRefreshing(false);
      setLoadingMore(false);
    }
  }, []);

  // Initial load
  useEffect(() => {
    fetchStarredMessages(1, false, false);
  }, [fetchStarredMessages]);

  const onRefresh = () => {
    setRefreshing(true);
    fetchStarredMessages(1, false, true);
  };

  const loadMore = () => {
    if (loading || loadingMore || page >= lastPage) return;
    fetchStarredMessages(page + 1, true, false);
  };

  const handleOpenChat = async (msg: any) => {
    if (!msg.conversation_id) {
      showDialog('Error', 'This message does not have an associated conversation.');
      return;
    }

    setFetchingConversation(true);
    try {
      const res = await api.get(`/v1/mobile/conversations/${msg.conversation_id}`);
      if (res && res.id) {
        const conversation = {
          id: res.id,
          contact_id: res.contact?.id,
          name: res.contact?.name || res.contact?.phone_number || 'Unknown Contact',
          phone: res.contact?.phone_number || 'Unknown',
          last: '',
          time: '',
          unread: res.unread_count || 0,
          status: (res.status === 'closed' ? 'delivered' : 'read') as any,
          tag: 'Sales' as const, // Default fallback
          online: res.is_within_24_hours || false,
          pinned: false,
          reply: undefined,
          bot: res.is_ai_enabled || false,
        };
        nav.navigate('Chat', { conversation });
      } else {
        showDialog('Error', 'Failed to retrieve conversation details.');
      }
    } catch (err: any) {
      console.warn('Failed to load conversation for starred message:', err);
      showDialog('Error', err.message || 'Could not load conversation details.');
    } finally {
      setFetchingConversation(false);
    }
  };

  return (
    <View className="flex-1 bg-bg dark:bg-d-bg" style={{ paddingTop: insets.top }}>
      {/* Header */}
      <View className="flex-row items-center justify-between px-3 pb-3 border-b border-hairline dark:border-d-hairline">
        <View className="flex-row items-center gap-[10px]">
          <IconButton icon={ChevronLeft} onPress={() => nav.goBack()} />
          <Text className="text-[17px] font-bold text-ink dark:text-d-ink">Starred Messages</Text>
        </View>
        <View className="pr-3">
          <Star size={18} color={tokens.accent} fill={tokens.accent} />
        </View>
      </View>

      {/* Starred Messages List */}
      <FlatList
        data={messages}
        keyExtractor={(item) => String(item.id)}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor={tokens.accent}
          />
        }
        contentContainerStyle={{ paddingVertical: 16, paddingBottom: insets.bottom + 24 }}
        onEndReached={loadMore}
        onEndReachedThreshold={0.4}
        renderItem={({ item }) => {
          const isOutbound = item.direction === 'outbound';
          const name = item.contact?.name || item.contact?.phone_number || 'Unknown';
          const timeStr = item.created_at
            ? new Date(item.created_at).toLocaleString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
            : '';

          return (
            <Pressable
              onPress={() => handleOpenChat(item)}
              className="mx-[18px] mb-3 p-3.5 rounded-lg bg-surface dark:bg-d-surface border border-hairline dark:border-d-hairline active:bg-surface2 dark:active:bg-d-surface2 flex-row gap-3 items-start"
            >
              <Avatar name={name} size={38} />
              
              <View className="flex-1 min-w-0">
                <View className="flex-row justify-between items-baseline">
                  <View className="flex-row items-center gap-1.5 min-w-0">
                    <Text className="text-sm font-semibold text-ink dark:text-d-ink truncate" numberOfLines={1}>
                      {name}
                    </Text>
                    {isOutbound ? (
                      <ArrowUpRight size={13} color={tokens.accent} strokeWidth={2.2} />
                    ) : (
                      <ArrowDownLeft size={13} color={tokens.ok} strokeWidth={2.2} />
                    )}
                  </View>
                  <Text className="text-[11px] text-muted dark:text-d-muted ml-2 font-medium">
                    {timeStr}
                  </Text>
                </View>
                
                {/* Quoted Message Block */}
                <View 
                  className="mt-2 pl-3 py-1.5 border-l-2 bg-surface2 dark:bg-d-surface2 rounded-r-md"
                  style={{ borderColor: tokens.accent }}
                >
                  <Text className="text-ink dark:text-d-ink text-[13px] leading-[18px]" numberOfLines={3}>
                    {item.content || '[Media Message]'}
                  </Text>
                </View>

                {/* Additional Metadata */}
                <Text className="text-[10px] text-muted dark:text-d-muted mt-2 font-medium">
                  {isOutbound ? 'Sent by your team' : 'Sent by contact'}
                </Text>
              </View>
              
              <View className="justify-center h-full pl-1">
                <ChevronRight size={16} color={tokens.faint} strokeWidth={1.6} />
              </View>
            </Pressable>
          );
        }}
        ListEmptyComponent={
          loading ? (
            <View className="flex-1 mt-4">
              <StarredListSkeleton count={6} />
            </View>
          ) : (
            <View className="py-20 items-center justify-center px-6">
              <Star size={44} color={tokens.faint} strokeWidth={1.2} className="mb-3" />
              <Text className="text-sm text-ink dark:text-d-ink font-semibold">No Starred Messages</Text>
              <Text className="text-xs text-muted dark:text-d-muted text-center mt-1">
                Star important messages in your chat sessions to view them here.
              </Text>
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

      {/* Loading overlay when opening chat */}
      {fetchingConversation && (
        <View 
          className="absolute inset-0 bg-black/40 items-center justify-center z-50"
          style={{ paddingTop: insets.top }}
        >
          <View className="bg-surface dark:bg-d-surface px-6 py-4 rounded-xl flex-row items-center gap-3 border border-hairline dark:border-d-hairline shadow-lg">
            <ActivityIndicator size="small" color={tokens.accent} />
            <Text className="text-sm font-semibold text-ink dark:text-d-ink">Opening chat...</Text>
          </View>
        </View>
      )}

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

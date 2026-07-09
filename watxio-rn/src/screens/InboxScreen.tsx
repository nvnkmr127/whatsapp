// src/screens/InboxScreen.tsx — conversation list with filter chips + FAB.
// Tapping a row opens the Chat screen with that contact preloaded.

import React, { useEffect, useMemo, useState, useCallback } from 'react';
import {
  View, Text, Pressable, ScrollView, RefreshControl, FlatList, TextInput, ActivityIndicator, Modal
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation, useFocusEffect } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { Search, SquarePen, ChevronDown, Pin, UserPlus } from 'lucide-react-native';

import { useTokens } from '@/theme';
import type { Conversation, RootStackParamList } from '@/types';
import { Avatar } from '@/components/Avatar';
import { Chip } from '@/components/Chip';
import { useGlobalState } from '@/store';
import { CustomDialog } from '@/components/Dialog';
import { api } from '@/services/api';
import { cacheInbox, loadCachedInbox } from '@/services/chatCache';
import { OfflineBanner } from '@/components/OfflineBanner';
import { useNetworkStatus } from '@/hooks/useNetworkStatus';

type FilterKey = 'All' | 'Unread' | 'Open' | 'Mine' | 'Bots';
const FILTERS: FilterKey[] = ['All', 'Unread', 'Open', 'Mine', 'Bots'];

export default function InboxScreen({ navigation }: any) {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const nav = navigation;
  const isOnline = useNetworkStatus();
  const [filter, setFilter] = useState<FilterKey>('All');
  const [refreshing, setRefreshing] = useState(false);
  const [isSearching, setIsSearching] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [globalState, setGlobalState] = useGlobalState();
  const [conversations, setConversations] = useState<Conversation[]>([]);
  const [counts, setCounts] = useState<Record<FilterKey, number>>({ All: 0, Unread: 0, Open: 0, Mine: 0, Bots: 0 });
  const [showCreateContactModal, setShowCreateContactModal] = useState(false);
  const [newContactName, setNewContactName] = useState('');
  const [newContactPhone, setNewContactPhone] = useState('');
  const [newContactEmail, setNewContactEmail] = useState('');
  const [newContactCompany, setNewContactCompany] = useState('');
  const [isCreatingContact, setIsCreatingContact] = useState(false);
  const [loading, setLoading] = useState(false);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loadingMore, setLoadingMore] = useState(false);

  // Dialog State
  const [dialogConfig, setDialogConfig] = useState<{
    visible: boolean;
    title: string;
    message: string;
    buttons: { text: string; onPress?: () => void; style?: 'default' | 'cancel' | 'destructive' }[];
  }>({
    visible: false,
    title: '',
    message: '',
    buttons: [],
  });

  const showDialog = (title: string, message: string, buttons: typeof dialogConfig.buttons = [{ text: 'OK' }]) => {
    setDialogConfig({ visible: true, title, message, buttons });
  };

  // Fetch Conversations from API
  const fetchConversations = useCallback(async (isSilent = false, pageNum = 1, append = false, overwrite = false) => {
    if (!globalState.token) return;

    // When offline, load from cache and skip API call
    if (!isOnline) {
      const cached = await loadCachedInbox();
      if (cached && cached.length > 0) {
        setConversations(cached);
      }
      setLoading(false);
      setRefreshing(false);
      setLoadingMore(false);
      return;
    }

    if (!isSilent && pageNum === 1) setLoading(true);
    if (pageNum > 1) setLoadingMore(true);

    try {
      let apiFilter = 'all';
      if (filter === 'Unread') apiFilter = 'unread';
      if (filter === 'Mine') apiFilter = 'assigned';
      if (filter === 'Open') apiFilter = 'open';
      if (filter === 'Bots') apiFilter = 'bots';

      const headers = isSilent ? { 'X-Silent-Errors': 'true' } : undefined;
      const response = await api.get(`/v1/mobile/conversations?filter=${apiFilter}&query=${searchQuery}&page=${pageNum}`, headers);
      const rawData = response.data || [];
      const currentPage = response.current_page || 1;
      const totalPages = response.last_page || 1;

      // Map backend model to frontend UI types
      const mapped: Conversation[] = rawData.map((c: any) => {
        return {
          id: c.id,
          contact_id: c.contact_id,
          name: c.name || c.phone || 'Unknown Contact',
          phone: c.phone,
          last: c.last_message ? c.last_message.content : 'No messages yet',
          time: c.last_message ? c.last_message.pretty_time : '',
          unread: c.unread_count || 0,
          status: c.status === 'closed' ? 'delivered' : 'read',
          tag: c.tags && c.tags.length > 0 ? c.tags[0].name : undefined,
          tagColor: c.tags && c.tags.length > 0 ? c.tags[0].color : undefined,
          online: c.is_within_24_hours || false,
          pinned: false,
          reply: c.last_message?.is_outbound ? 'me' : undefined,
          bot: c.is_ai_enabled || false,
        };
      });

      if (append) {
        setConversations((prev) => {
          const combined = [...prev, ...mapped];
          return combined.filter((v, i, a) => a.findIndex(t => t.id === v.id) === i);
        });
      } else {
        setConversations((prev) => {
          if (!overwrite && prev.length > 0 && pageNum === 1) {
            const merged = [...mapped, ...prev];
            return merged.filter((v, i, a) => a.findIndex(t => t.id === v.id) === i);
          }
          return mapped;
        });
      }

      setPage(currentPage);
      setLastPage(totalPages);

      // Cache the first page of conversations for offline use
      if (pageNum === 1 && !append) {
        cacheInbox(mapped).catch(() => {});
      }

      if (response.counts) {
        setCounts({
          All: response.counts.All ?? 0,
          Unread: response.counts.Unread ?? 0,
          Open: response.counts.Open ?? 0,
          Mine: response.counts.Mine ?? 0,
          Bots: response.counts.Bots ?? 0,
        });
      } else {
        // Fallback to local calculation if server doesn't provide them
        const allCount = mapped.length;
        const unreadCount = mapped.filter((c) => c.unread > 0).length;
        const openCount = mapped.filter((c) => c.status !== 'delivered').length;
        const mineCount = mapped.filter((c) => c.reply === 'me' || c.unread > 0).length;
        const botsCount = mapped.filter((c) => c.bot).length;

        setCounts({
          All: allCount,
          Unread: unreadCount,
          Open: openCount,
          Mine: mineCount,
          Bots: botsCount,
        });
      }
    } catch (err: any) {
      if (!isSilent) {
        console.warn('Fetch conversations failed:', err);
      }
    } finally {
      setLoading(false);
      setRefreshing(false);
      setLoadingMore(false);
    }
  }, [globalState.token, filter, searchQuery, isOnline]);

  const handleCreateContact = async () => {
    if (!newContactPhone.trim()) {
      showDialog('Phone Required', 'Please enter a valid WhatsApp phone number.');
      return;
    }

    const phoneRegex = /^\+?[1-9]\d{1,14}$/;
    const phoneClean = newContactPhone.trim().replace(/[\s-()]/g, '');
    if (!phoneRegex.test(phoneClean)) {
      showDialog('Invalid Phone', 'Please enter a valid international phone number (e.g. +15551234567).');
      return;
    }

    setIsCreatingContact(true);
    try {
      const response = await api.post('/v1/mobile/contacts', {
        phone_number: phoneClean,
        name: newContactName.trim() || undefined,
        email: newContactEmail.trim() || undefined,
        custom_attributes: {
          email: newContactEmail.trim() || undefined,
          company: newContactCompany.trim() || undefined,
        },
      });

      setShowCreateContactModal(false);
      setNewContactName('');
      setNewContactPhone('');
      setNewContactEmail('');
      setNewContactCompany('');

      // Refresh list
      await fetchConversations(true);

      // Navigate to chat
      if (response.conversation) {
        nav.navigate('Chat', { conversation: response.conversation });
      }
    } catch (err: any) {
      if (err.message?.includes('already exists') || err.status === 409 || err.code === 409) {
        const existingContact = err.data?.contact || err.contact;
        const existingConv = err.data?.conversation || err.conversation;
        if (existingContact) {
          showDialog(
            'Contact Exists',
            'A contact with this phone number already exists. Would you like to open their chat history?',
            [
              { text: 'Cancel', style: 'cancel' },
              {
                text: 'Open Chat',
                onPress: async () => {
                  if (existingConv) {
                    setShowCreateContactModal(false);
                    setNewContactName('');
                    setNewContactPhone('');
                    setNewContactEmail('');
                    setNewContactCompany('');
                    nav.navigate('Chat', { conversation: existingConv });
                    return;
                  }

                  try {
                    setLoading(true);
                    const res = await api.get(`/v1/mobile/contacts/${existingContact.id}`);
                    const contactDetail = res.contact || {};
                    if (contactDetail.activeConversation) {
                      const activeConv = {
                        id: contactDetail.activeConversation.id,
                        contact_id: existingContact.id,
                        name: existingContact.name || existingContact.phone_number,
                        phone: existingContact.phone_number,
                        unread: 0,
                        status: contactDetail.activeConversation.status || 'open',
                        online: false,
                        bot: !(existingContact.is_bot_paused ?? false),
                      };
                      setShowCreateContactModal(false);
                      setNewContactName('');
                      setNewContactPhone('');
                      setNewContactEmail('');
                      setNewContactCompany('');
                      nav.navigate('Chat', { conversation: activeConv });
                    } else {
                      showDialog('Chat Error', 'No active conversation found for this contact.');
                    }
                  } catch (fetchErr) {
                    showDialog('Error', 'Failed to open conversation for existing contact.');
                  } finally {
                    setLoading(false);
                  }
                }
              }
            ]
          );
        } else {
          showDialog('Error', 'Contact with this number already exists.');
        }
      } else {
        showDialog('Failed to Create', err.message || 'Could not save the new contact.');
      }
    } finally {
      setIsCreatingContact(false);
    }
  };

  // Load cached inbox immediately on first mount
  useEffect(() => {
    loadCachedInbox().then((cached) => {
      if (cached && cached.length > 0) {
        setConversations(cached);
      }
    });
  }, []);

  // Load conversations when screen gains focus.
  // fetchConversations already depends on filter + searchQuery via useCallback,
  // so changing either re-creates the callback which re-fires useFocusEffect.
  // A separate useEffect for filter/searchQuery changes would cause a double
  // fetch while focused — removed to prevent that race condition.
  useFocusEffect(
    useCallback(() => {
      setPage(1);
      setLastPage(1);
      fetchConversations(true, 1, false, true);
      const interval = setInterval(() => fetchConversations(true, 1, false, false), 15000);
      return () => clearInterval(interval);
    }, [fetchConversations])
  );

  const onRefresh = () => {
    setRefreshing(true);
    fetchConversations(true, 1, false, true);
  };

  const loadMore = () => {
    if (loadingMore || page >= lastPage) return;
    fetchConversations(true, page + 1, true, false);
  };

  const handleSwitchTeam = async (teamId: number, teamName: string) => {
    setLoading(true);
    try {
      // 1. Tell backend to switch team
      await api.post('/v1/mobile/auth/switch-team', { team_id: teamId });
      
      // 2. Set active team headers
      api.setTeamId(teamId);

      // 3. Fetch active numbers for this team
      const numbers = await api.get('/v1/mobile/auth/numbers');
      const activeNumber = numbers[0] || null;

      // 4. Update store
      setGlobalState({
        activeTeamId: teamId,
        businessName: teamName,
        waNumber: activeNumber ? activeNumber.display_number : '+1 (415) 555-0118',
        numbers: numbers,
      });

      // 5. Reload conversations
      setPage(1);
      setLastPage(1);
      await fetchConversations(false, 1, false, true);
      showDialog('Workspace Switched', `Active workspace changed to ${teamName}.`);
    } catch (err: any) {
      showDialog('Error Switching Workspace', err.message || 'Could not complete team swap.');
    } finally {
      setLoading(false);
    }
  };

  const triggerWorkspaceDialog = () => {
    const buttons: Array<{ text: string; onPress?: () => any; style?: "default" | "cancel" | "destructive" }> = globalState.teams.map((team) => ({
      text: team.name,
      onPress: () => handleSwitchTeam(team.id, team.name),
    }));

    buttons.push({ text: 'Cancel', style: 'cancel', onPress: async () => {} });

    showDialog('Switch Active Workspace', 'Choose a WhatsApp Business team to display:', buttons);
  };

  return (
    <View className="flex-1 bg-bg dark:bg-d-bg" style={{ paddingTop: insets.top }}>
      {/* Offline banner */}
      <OfflineBanner />
      {/* Header */}
      {isSearching ? (
        <View className="flex-row items-center gap-2 px-[18px] pt-4 pb-[10px]">
          <View className="flex-1 flex-row items-center bg-surface2 dark:bg-d-surface2 rounded-lg px-3 py-1.5 gap-2">
            <Search size={16} color={tokens.muted} strokeWidth={1.6} />
            <TextInput
              value={searchQuery}
              onChangeText={setSearchQuery}
              placeholder="Search conversations..."
              placeholderTextColor={tokens.muted}
              className="flex-1 text-ink dark:text-d-ink text-[14px] p-0"
              autoFocus
            />
          </View>
          <Pressable
            onPress={() => {
              setIsSearching(false);
              setSearchQuery('');
            }}
            className="px-2.5 py-1.5 rounded-md active:bg-surface2 dark:active:bg-d-surface2"
          >
            <Text className="text-accent dark:text-d-accent text-sm font-semibold">Cancel</Text>
          </Pressable>
        </View>
      ) : (
        <View className="flex-row items-center justify-between px-[18px] pt-4 pb-[10px]">
          <View>
            <Text className="text-[26px] font-bold tracking-[-0.4px] text-ink dark:text-d-ink">
              Inbox
            </Text>
            <Pressable
              onPress={triggerWorkspaceDialog}
              className="flex-row items-center gap-1 mt-[3px]"
            >
              <Text className="text-xs text-muted dark:text-d-muted font-normal">{globalState.businessName} · {globalState.waNumber}</Text>
              <ChevronDown size={11} color={tokens.muted} strokeWidth={1.6} />
            </Pressable>
          </View>

          <View className="flex-row gap-1">
            <Pressable
              onPress={() => setIsSearching(true)}
              className="w-9 h-9 items-center justify-center rounded-full active:opacity-60"
              hitSlop={8}
            >
              <Search size={20} color={tokens.ink} strokeWidth={1.6} />
            </Pressable>
            <Pressable
              onPress={() => setShowCreateContactModal(true)}
              className="w-9 h-9 items-center justify-center rounded-full active:opacity-60"
              hitSlop={8}
            >
              <UserPlus size={20} color={tokens.ink} strokeWidth={1.6} />
            </Pressable>
            <Pressable
              onPress={() => nav.navigate('Broadcast')}
              className="w-9 h-9 items-center justify-center rounded-full active:opacity-60"
              hitSlop={8}
            >
              <SquarePen size={20} color={tokens.ink} strokeWidth={1.6} />
            </Pressable>
          </View>
        </View>
      )}

      {/* Filter chips */}
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        className="flex-grow-0"
        contentContainerStyle={{ paddingHorizontal: 14, gap: 6, paddingBottom: 10, paddingTop: 6 }}
      >
        {FILTERS.map((f) => (
          <Chip
            key={f}
            label={f}
            active={filter === f}
            count={counts[f]}
            onPress={() => setFilter(f)}
          />
        ))}
      </ScrollView>

      {/* Loading Indicator */}
      {loading && !refreshing ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator size="large" color={tokens.accent} />
        </View>
      ) : (
        /* List */
        <FlatList
          className="flex-1"
          data={conversations}
          keyExtractor={(c) => String(c.id)}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={onRefresh}
              tintColor={tokens.accent}
            />
          }
          renderItem={({ item, index }) => (
            <Row
              c={item}
              divider={index < conversations.length - 1}
              onPress={() => nav.navigate('Chat', { conversation: item })}
            />
          )}
          ListEmptyComponent={
            <View className="py-20 items-center justify-center">
              <Text className="text-sm text-muted dark:text-d-muted">No conversations found.</Text>
            </View>
          }
          onEndReached={loadMore}
          onEndReachedThreshold={0.4}
          ListFooterComponent={
            loadingMore ? (
              <View className="py-4 items-center justify-center">
                <ActivityIndicator size="small" color={tokens.accent} />
              </View>
            ) : (
              <View className="h-[100px]" />
            )
          }
        />
      )}

      {/* Floating Action Button (FAB) */}
      <Pressable
        onPress={() => setShowCreateContactModal(true)}
        className="absolute bottom-6 right-5 w-[54px] h-[54px] bg-accent dark:bg-d-accent items-center justify-center shadow-lg shadow-accent/20 active:opacity-85 z-50 rounded-full"
      >
        <UserPlus size={20} color="#FFFFFF" strokeWidth={2} />
      </Pressable>

      {/* Create Contact Modal */}
      <Modal transparent visible={showCreateContactModal} animationType="slide">
        <View className="flex-1 bg-black/40 justify-end">
          <View
            className="bg-surface dark:bg-d-surface rounded-t-2xl px-[18px] pt-4"
            style={{ paddingBottom: insets.bottom + 16 }}
          >
            <View className="flex-row items-center justify-between pb-4 border-b border-hairline dark:border-d-hairline mb-4">
              <Text className="text-base font-bold text-ink dark:text-d-ink">Create New Contact</Text>
              <Pressable
                onPress={() => {
                  setShowCreateContactModal(false);
                  setNewContactName('');
                  setNewContactPhone('');
                  setNewContactEmail('');
                  setNewContactCompany('');
                }}
                className="p-1"
              >
                <Text className="text-accent dark:text-d-accent font-semibold text-sm">Cancel</Text>
              </Pressable>
            </View>

            <ScrollView className="max-h-[400px]">
              <View className="mb-4">
                <Text className="text-xs font-semibold text-muted dark:text-d-muted uppercase tracking-wider mb-1.5">Phone Number *</Text>
                <View className="flex-row items-center bg-surface2 dark:bg-d-surface2 rounded-lg px-3 py-3">
                  <TextInput
                    value={newContactPhone}
                    onChangeText={setNewContactPhone}
                    placeholder="+15551234567"
                    placeholderTextColor={tokens.muted}
                    keyboardType="phone-pad"
                    className="flex-1 text-ink dark:text-d-ink text-sm p-0 font-mono"
                  />
                </View>
              </View>

              <View className="mb-4">
                <Text className="text-xs font-semibold text-muted dark:text-d-muted uppercase tracking-wider mb-1.5">Name</Text>
                <View className="flex-row items-center bg-surface2 dark:bg-d-surface2 rounded-lg px-3 py-3">
                  <TextInput
                    value={newContactName}
                    onChangeText={setNewContactName}
                    placeholder="John Doe"
                    placeholderTextColor={tokens.muted}
                    className="flex-1 text-ink dark:text-d-ink text-sm p-0"
                  />
                </View>
              </View>

              <View className="mb-4">
                <Text className="text-xs font-semibold text-muted dark:text-d-muted uppercase tracking-wider mb-1.5">Email</Text>
                <View className="flex-row items-center bg-surface2 dark:bg-d-surface2 rounded-lg px-3 py-3">
                  <TextInput
                    value={newContactEmail}
                    onChangeText={setNewContactEmail}
                    placeholder="john@example.com"
                    placeholderTextColor={tokens.muted}
                    keyboardType="email-address"
                    className="flex-1 text-ink dark:text-d-ink text-sm p-0"
                  />
                </View>
              </View>

              <View className="mb-6">
                <Text className="text-xs font-semibold text-muted dark:text-d-muted uppercase tracking-wider mb-1.5">Company</Text>
                <View className="flex-row items-center bg-surface2 dark:bg-d-surface2 rounded-lg px-3 py-3">
                  <TextInput
                    value={newContactCompany}
                    onChangeText={setNewContactCompany}
                    placeholder="Acme Corp"
                    placeholderTextColor={tokens.muted}
                    className="flex-1 text-ink dark:text-d-ink text-sm p-0"
                  />
                </View>
              </View>

              <View className="mb-2">
                <Pressable
                  onPress={handleCreateContact}
                  disabled={isCreatingContact}
                  className="w-full bg-accent dark:bg-d-accent py-3.5 rounded-lg items-center justify-center active:opacity-90 disabled:opacity-50"
                >
                  {isCreatingContact ? (
                    <ActivityIndicator size="small" color="#FFFFFF" />
                  ) : (
                    <Text className="text-white text-[15px] font-bold">Create Contact & Start Chat</Text>
                  )}
                </Pressable>
              </View>
            </ScrollView>
          </View>
        </View>
      </Modal>

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

interface RowProps { c: Conversation; divider: boolean; onPress: () => void; }

function Row({ c, divider, onPress }: RowProps) {
  const { tokens } = useTokens();
  return (
    <Pressable
      onPress={onPress}
      className={`flex-row items-center gap-3 py-[11px] px-[18px] active:bg-surface2 dark:active:bg-d-surface2 relative ${
        divider ? 'border-b border-hairline dark:border-d-hairline' : ''
      }`}
    >
      {/* Pin badge indicator */}
      {c.pinned ? (
        <View className="absolute top-[12px] left-[4px] z-10 w-[10px] h-[17px] justify-center items-center">
          <Pin size={10} color="#83898A" className="rotate-45" />
        </View>
      ) : null}

      <Avatar name={c.name} size={44} dot={c.online ? tokens.ok : null} />
      <View className="flex-1 min-w-0">
        <View className="flex-row justify-between items-baseline">
          <View className="flex-1 flex-row items-center gap-1.5 mr-2 overflow-hidden">
            <Text
              numberOfLines={1}
              className={`flex-shrink text-[15px] ${
                c.unread ? 'font-bold text-ink dark:text-d-ink' : 'font-semibold text-ink dark:text-d-ink'
              }`}
            >
              {c.name}
            </Text>
            {c.tag ? (
              <View 
                className="px-[5px] py-[2px] rounded border" 
                style={{ 
                  backgroundColor: c.tagColor ? `${c.tagColor}15` : tokens.surface2,
                  borderColor: c.tagColor ? `${c.tagColor}30` : tokens.hairline
                }}
              >
                <Text 
                  className="text-[9px] font-bold uppercase tracking-wider"
                  style={{ color: c.tagColor || tokens.muted }}
                >
                  {c.tag}
                </Text>
              </View>
            ) : null}
          </View>
          <Text
            className={`text-[11.5px] ml-2 ${
              c.unread ? 'text-accent dark:text-d-accent font-bold' : 'text-muted dark:text-d-muted font-medium'
            }`}
          >
            {c.time}
          </Text>
        </View>
        <View className="flex-row items-center gap-1.5 mt-[3px]">
          {c.reply === 'me' ? (
            <Text className="text-[12.5px] text-muted dark:text-d-muted font-medium">You:</Text>
          ) : null}
          <Text
            numberOfLines={1}
            className={`flex-1 text-[13px] ${
              c.unread ? 'text-ink2 dark:text-d-ink2 font-medium' : 'text-muted dark:text-d-muted font-normal'
            }`}
          >
            {c.last}
          </Text>
          {c.unread > 0 ? (
            <View className="min-w-[18px] h-[18px] px-[5px] rounded-full bg-accent dark:bg-d-accent items-center justify-center">
              <Text className="text-accent-ink dark:text-d-accent-ink text-[11px] font-bold">{c.unread}</Text>
            </View>
          ) : null}
        </View>
      </View>
    </Pressable>
  );
}

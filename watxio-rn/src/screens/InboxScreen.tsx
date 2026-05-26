// src/screens/InboxScreen.tsx — conversation list with filter chips + FAB.
// Tapping a row opens the Chat screen with that contact preloaded.

import React, { useEffect, useMemo, useState, useCallback } from 'react';
import {
  View, Text, Pressable, ScrollView, RefreshControl, FlatList, TextInput, ActivityIndicator
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation, useFocusEffect } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { Search, SquarePen, ChevronDown, Pin } from 'lucide-react-native';

import { useTokens } from '@/theme';
import type { Conversation, RootStackParamList } from '@/types';
import { Avatar } from '@/components/Avatar';
import { Chip } from '@/components/Chip';
import { useGlobalState } from '@/store';
import { CustomDialog } from '@/components/Dialog';
import { api } from '@/services/api';

type FilterKey = 'All' | 'Unread' | 'Open' | 'Mine' | 'Bots';
const FILTERS: FilterKey[] = ['All', 'Unread', 'Open', 'Mine', 'Bots'];

export default function InboxScreen({ navigation }: any) {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const nav = navigation;
  const [filter, setFilter] = useState<FilterKey>('All');
  const [refreshing, setRefreshing] = useState(false);
  const [isSearching, setIsSearching] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [globalState, setGlobalState] = useGlobalState();
  const [conversations, setConversations] = useState<Conversation[]>([]);
  const [counts, setCounts] = useState<Record<FilterKey, number>>({ All: 0, Unread: 0, Open: 0, Mine: 0, Bots: 0 });
  const [loading, setLoading] = useState(false);

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
  const fetchConversations = useCallback(async (isSilent = false) => {
    if (!globalState.token) return;
    if (!isSilent) setLoading(true);

    try {
      let apiFilter = 'all';
      if (filter === 'Unread') apiFilter = 'unread';
      if (filter === 'Mine') apiFilter = 'assigned';

      const response = await api.get(`/v1/mobile/conversations?filter=${apiFilter}&query=${searchQuery}`);
      const rawData = response.data || [];

      // Map backend model to frontend UI types
      const mapped: Conversation[] = rawData.map((c: any) => {
        return {
          id: c.id,
          name: c.name || c.phone || 'Unknown Contact',
          phone: c.phone,
          last: c.last_message ? c.last_message.content : 'No messages yet',
          time: c.last_message ? c.last_message.pretty_time : '',
          unread: c.unread_count || 0,
          status: c.status === 'closed' ? 'delivered' : 'read',
          tag: c.tags && c.tags[0] ? c.tags[0].name : 'Sales',
          online: c.is_within_24_hours || false,
          pinned: false,
          reply: c.last_message?.is_outbound ? 'me' : undefined,
          bot: c.is_ai_enabled || false,
        };
      });

      setConversations(mapped);

      // Dynamically calculate counts based on loaded data for simplicity
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
    } catch (err: any) {
      console.error('Fetch conversations failed:', err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [globalState.token, filter, searchQuery]);

  // Load conversations when screen gains focus
  useFocusEffect(
    useCallback(() => {
      fetchConversations(true);
      const interval = setInterval(() => fetchConversations(true), 15000); // Poll every 15s
      return () => clearInterval(interval);
    }, [fetchConversations])
  );

  // Trigger load on filter change or query change
  useEffect(() => {
    fetchConversations();
  }, [filter, searchQuery]);

  const onRefresh = () => {
    setRefreshing(true);
    fetchConversations(true);
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
      await fetchConversations();
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
              onPress={() => nav.navigate('Chat', { contact: item })}
            />
          )}
          ListEmptyComponent={
            <View className="py-20 items-center justify-center">
              <Text className="text-sm text-muted dark:text-d-muted">No conversations found.</Text>
            </View>
          }
          ListFooterComponent={<View className="h-[100px]" />}
        />
      )}

      {/* Floating Action Button (FAB) */}
      <Pressable
        onPress={() => nav.navigate('Broadcast')}
        className="absolute bottom-6 right-5 w-[54px] h-[54px] bg-accent dark:bg-d-accent items-center justify-center shadow-lg shadow-accent/20 active:opacity-85 z-50 rounded-full"
      >
        <SquarePen size={20} color="#FFFFFF" strokeWidth={2} />
      </Pressable>

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
          <Text
            numberOfLines={1}
            className={`flex-1 text-[15px] ${
              c.unread ? 'font-bold text-ink dark:text-d-ink' : 'font-semibold text-ink dark:text-d-ink'
            }`}
          >
            {c.name}
          </Text>
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

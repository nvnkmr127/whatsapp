// src/screens/InboxScreen.tsx — conversation list with filter chips + FAB.
// Tapping a row opens the Chat screen with that contact preloaded.

import React, { useMemo, useState } from 'react';
import {
  View, Text, Pressable, ScrollView, RefreshControl, FlatList, TextInput,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { Search, SquarePen, ChevronDown, Pin } from 'lucide-react-native';

import { useTokens } from '@/theme';
import { CONVERSATIONS, BUSINESS } from '@/data';
import type { Conversation, RootStackParamList } from '@/types';
import { Avatar } from '@/components/Avatar';
import { Chip } from '@/components/Chip';
import { useGlobalState } from '@/store';
import { CustomDialog } from '@/components/Dialog';

type FilterKey = 'All' | 'Unread' | 'Open' | 'Mine' | 'Bots';
const FILTERS: FilterKey[] = ['All', 'Unread', 'Open', 'Mine', 'Bots'];
const COUNTS: Record<FilterKey, number> = { All: 8, Unread: 3, Open: 5, Mine: 4, Bots: 1 };

export default function InboxScreen() {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const nav = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  const [filter, setFilter] = useState<FilterKey>('All');
  const [refreshing, setRefreshing] = useState(false);
  const [isSearching, setIsSearching] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [globalState, setGlobalState] = useGlobalState();

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


  const items = useMemo(() => {
    let filtered = CONVERSATIONS.filter((c) =>
      filter === 'All' ? true :
      filter === 'Unread' ? c.unread > 0 :
      filter === 'Mine' ? c.reply === 'me' || c.unread > 0 :
      filter === 'Bots' ? !!c.bot :
      !c.bot
    );
    if (searchQuery.trim().length > 0) {
      const q = searchQuery.toLowerCase();
      filtered = filtered.filter(
        (c) =>
          c.name.toLowerCase().includes(q) ||
          c.last.toLowerCase().includes(q)
      );
    }
    return filtered;
  }, [filter, searchQuery]);

  const onRefresh = () => {
    setRefreshing(true);
    setTimeout(() => setRefreshing(false), 900);
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
              onPress={() => {
                showDialog(
                  'Switch Workspace / Active Number',
                  'Select a verified business phone number and workspace:',
                  [
                    { text: 'Acme Coffee Roasters (+1 415 555-0118)', onPress: () => setGlobalState({ businessName: 'Acme Coffee Roasters', waNumber: '+1 (415) 555-0118' }) },
                    { text: 'Acme Wholesale HQ (+1 800 555-9213)', onPress: () => setGlobalState({ businessName: 'Acme Wholesale HQ', waNumber: '+1 (800) 555-9213' }) },
                    { text: 'Cancel', style: 'cancel' }
                  ]
                );
              }}
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
            count={COUNTS[f]}
            onPress={() => setFilter(f)}
          />
        ))}
      </ScrollView>

      {/* List */}
      <FlatList
        className="flex-1"
        data={items}
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
            divider={index < items.length - 1}
            onPress={() => nav.navigate('Chat', { contact: item })}
          />
        )}
        ListFooterComponent={<View className="h-[100px]" />}
      />

      {/* Floating Action Button (FAB) */}
      <Pressable
        onPress={() => nav.navigate('Broadcast')}
        className="absolute bottom-6 right-5 w-[54px] h-[54px] bg-accent dark:bg-d-accent items-center justify-center shadow-lg shadow-accent/20 active:opacity-85 z-50"
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


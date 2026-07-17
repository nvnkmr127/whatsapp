import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { View, Text, FlatList, TextInput, Pressable, RefreshControl, ActivityIndicator, Modal, ScrollView } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import type { RootStackParamList } from '@/types';
import { ChevronLeft, Plus, Search, Bot, Trash2, Copy, Edit2, Play, CircleDot, Send } from 'lucide-react-native';

import { useTokens, Tokens } from '@/theme';
import { IconButton } from '@/components/Button';
import { Card } from '@/components/Card';
import { Toggle } from '@/components/Toggle';
import { api } from '@/services/api';
import { CustomDialog } from '@/components/Dialog';
import { BotsListSkeleton } from '@/components/ListItemSkeleton';

export default function BotsScreen() {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const nav = useNavigation<NativeStackNavigationProp<RootStackParamList>>();

  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [bots, setBots] = useState<any[]>([]);
  const [searchQuery, setSearchQuery] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);

  // Form Modal States
  const [showFormModal, setShowFormModal] = useState(false);
  const [editingBotId, setEditingBotId] = useState<number | null>(null);
  const [formName, setFormName] = useState('');
  const [formTriggers, setFormTriggers] = useState('');
  const [formReplyText, setFormReplyText] = useState('');
  const [formIsActive, setFormIsActive] = useState(false);

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

  const fetchBots = useCallback(async (page = 1, isRefresh = false) => {
    if (page === 1 && !isRefresh) setLoading(true);
    try {
      const response = await api.get(`/v1/mobile/bots?page=${page}`);
      const newItems = response.data || [];
      if (page === 1) {
        setBots(newItems);
      } else {
        setBots((prev) => [...prev, ...newItems]);
      }
      setCurrentPage(response.current_page || 1);
      setLastPage(response.last_page || 1);
    } catch (err: any) {
      console.warn('Failed to fetch message bots:', err);
      showDialog('Error', 'Could not fetch message bots from server.');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    fetchBots(1);
  }, [fetchBots]);

  const onRefresh = () => {
    setRefreshing(true);
    fetchBots(1, true);
  };

  const loadMore = () => {
    if (currentPage < lastPage && !loading) {
      fetchBots(currentPage + 1);
    }
  };

  // Local Filtered Bots
  const filteredBots = useMemo(() => {
    if (!searchQuery.trim()) return bots;
    const q = searchQuery.toLowerCase().trim();
    return bots.filter((b) => {
      const matchName = b.name?.toLowerCase().includes(q);
      const matchReply = b.reply_text?.toLowerCase().includes(q);
      const matchTrigger = Array.isArray(b.trigger) 
        ? b.trigger.some((t: string) => t.toLowerCase().includes(q))
        : false;
      return matchName || matchReply || matchTrigger;
    });
  }, [bots, searchQuery]);

  // Handle Toggle Status
  const handleToggle = async (id: number, currentIndex: number) => {
    // Optimistic Update
    setBots((prev) => 
      prev.map((b, i) => (i === currentIndex ? { ...b, is_bot_active: !b.is_bot_active } : b))
    );

    try {
      await api.post(`/v1/mobile/bots/${id}/toggle`);
    } catch (err: any) {
      // Revert on error
      setBots((prev) => 
        prev.map((b, i) => (i === currentIndex ? { ...b, is_bot_active: !b.is_bot_active } : b))
      );
      showDialog('Update Failed', err.message || 'Could not update bot active status.');
    }
  };

  // Handle Duplicate Bot
  const handleDuplicate = async (id: number) => {
    setActionLoading(true);
    try {
      const response = await api.post(`/v1/mobile/bots/${id}/duplicate`);
      showDialog('Bot Duplicated', `Created a copy of bot: "${response.name}"`, [
        {
          text: 'OK',
          onPress: () => {
            fetchBots(1);
          }
        }
      ]);
    } catch (err: any) {
      showDialog('Error', err.message || 'Could not duplicate bot.');
    } finally {
      setActionLoading(false);
    }
  };

  // Handle Delete Confirm
  const handleDeleteConfirm = (bot: any) => {
    showDialog('Delete Bot', `Are you sure you want to permanently delete "${bot.name}"?`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Delete',
        style: 'destructive',
        onPress: () => handleDelete(bot.id)
      }
    ]);
  };

  const handleDelete = async (id: number) => {
    setActionLoading(true);
    try {
      await api.delete(`/v1/mobile/bots/${id}`);
      showDialog('Deleted', 'Message bot has been deleted successfully.', [
        {
          text: 'OK',
          onPress: () => {
            fetchBots(1);
          }
        }
      ]);
    } catch (err: any) {
      showDialog('Error', err.message || 'Could not delete message bot.');
    } finally {
      setActionLoading(false);
    }
  };

  // Open Create Form
  const openCreateModal = () => {
    setEditingBotId(null);
    setFormName('');
    setFormTriggers('');
    setFormReplyText('');
    setFormIsActive(true);
    setShowFormModal(true);
  };

  // Open Edit Form
  const openEditModal = (bot: any) => {
    setEditingBotId(bot.id);
    setFormName(bot.name || '');
    setFormTriggers(Array.isArray(bot.trigger) ? bot.trigger.join(', ') : '');
    setFormReplyText(bot.reply_text || '');
    setFormIsActive(!!bot.is_bot_active);
    setShowFormModal(true);
  };

  // Submit Form (Save / Update)
  const handleSubmit = async () => {
    if (!formName.trim()) {
      showDialog('Validation Error', 'Please enter a name for the bot.');
      return;
    }
    if (!formTriggers.trim()) {
      showDialog('Validation Error', 'Please specify at least one trigger keyword.');
      return;
    }
    if (!formReplyText.trim()) {
      showDialog('Validation Error', 'Please write a reply message text.');
      return;
    }

    const triggerArray = formTriggers
      .split(',')
      .map((t) => t.trim())
      .filter((t) => t.length > 0);

    if (triggerArray.length === 0) {
      showDialog('Validation Error', 'Please specify at least one valid trigger keyword.');
      return;
    }

    setActionLoading(true);
    try {
      const payload = {
        name: formName.trim(),
        trigger: triggerArray,
        reply_text: formReplyText.trim(),
        is_bot_active: formIsActive,
      };

      if (editingBotId) {
        await api.patch(`/v1/mobile/bots/${editingBotId}`, payload);
        showDialog('Updated', 'Bot configuration has been updated.', [
          {
            text: 'OK',
            onPress: () => {
              setShowFormModal(false);
              fetchBots(1);
            }
          }
        ]);
      } else {
        await api.post('/v1/mobile/bots', payload);
        showDialog('Created', 'New message bot has been successfully created.', [
          {
            text: 'OK',
            onPress: () => {
              setShowFormModal(false);
              fetchBots(1);
            }
          }
        ]);
      }
    } catch (err: any) {
      showDialog('Submission Failed', err.message || 'Error occurred while saving the bot.');
    } finally {
      setActionLoading(false);
    }
  };

  return (
    <View className="flex-1 bg-bg dark:bg-d-bg" style={{ paddingTop: insets.top }}>
      {/* Header */}
      <View className="flex-row items-center gap-[10px] px-3 pb-3 border-b border-hairline dark:border-d-hairline">
        <IconButton icon={ChevronLeft} onPress={() => nav.goBack()} />
        <Text className="text-[17px] font-bold text-ink dark:text-d-ink flex-1">Message Bots</Text>
        <IconButton icon={Plus} onPress={openCreateModal} />
      </View>

      {/* Search Input */}
      <View className="px-4 py-2.5">
        <View className="flex-row items-center bg-surface2 dark:bg-d-surface2 rounded-lg px-3 py-1.5 gap-2">
          <Search size={16} color={tokens.muted} strokeWidth={1.6} />
          <TextInput
            value={searchQuery}
            onChangeText={setSearchQuery}
            placeholder="Search by name, triggers, or reply..."
            placeholderTextColor={tokens.muted}
            className="flex-1 text-ink dark:text-d-ink text-[14px] p-0"
          />
        </View>
      </View>

      {/* List */}
      {loading && bots.length === 0 ? (
        <BotsListSkeleton count={4} />
      ) : (
        <FlatList
          data={filteredBots}
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
          contentContainerStyle={{ paddingHorizontal: 16, paddingBottom: 100, paddingTop: 4 }}
          renderItem={({ item, index }) => {
            const isBotActive = !!item.is_bot_active;
            const activeColor = isBotActive ? tokens.ok : tokens.muted;
            
            return (
              <Card pad={14} className="mb-3">
                <View className="flex-row justify-between items-start gap-2 mb-2">
                  <View className="flex-1 min-w-0">
                    <View className="flex-row items-center gap-1.5">
                      <CircleDot size={10} color={activeColor} fill={isBotActive ? activeColor : 'transparent'} />
                      <Text className="text-[14.5px] font-bold text-ink dark:text-d-ink leading-tight" numberOfLines={1}>
                        {item.name}
                      </Text>
                    </View>
                    <Text className="text-[11px] text-muted dark:text-d-muted mt-0.5">
                      Updated {new Date(item.updated_at).toLocaleDateString()}
                    </Text>
                  </View>
                  <Toggle on={isBotActive} onChange={() => handleToggle(item.id, index)} />
                </View>

                {/* Triggers */}
                <View className="flex-row flex-wrap gap-1.25 mb-2.5">
                  {Array.isArray(item.trigger) && item.trigger.map((tag: string, tagIdx: number) => (
                    <View key={tagIdx} className="bg-surface2 dark:bg-d-surface2 px-2.5 py-0.5 rounded-full">
                      <Text className="text-[10px] font-semibold text-ink2 dark:text-d-ink2 font-mono">
                        {tag}
                      </Text>
                    </View>
                  ))}
                  {(!item.trigger || item.trigger.length === 0) && (
                    <Text className="text-[11.5px] text-muted dark:text-d-muted italic">No trigger keywords</Text>
                  )}
                </View>

                {/* Reply preview */}
                <View className="bg-bubble-in dark:bg-d-bubble-in py-2.5 px-3 rounded-md mb-3.5 border border-hairline dark:border-d-hairline">
                  <Text className="text-ink2 dark:text-d-ink2 text-[12.5px] leading-5 font-normal" numberOfLines={3}>
                    {item.reply_text}
                  </Text>
                </View>

                {/* Footer metrics and CRUD actions */}
                <View className="flex-row justify-between items-center pt-2.5 border-t border-hairline dark:border-d-hairline">
                  <View className="flex-row items-center gap-1">
                    <Send size={11} color={tokens.muted} />
                    <Text className="text-[11.5px] text-muted dark:text-d-muted font-medium">
                      Triggered {item.sending_count || 0} times
                    </Text>
                  </View>

                  <View className="flex-row items-center gap-2">
                    <Pressable
                      onPress={() => openEditModal(item)}
                      className="px-2.5 py-1.5 rounded bg-surface2 dark:bg-d-surface2 active:bg-tint dark:active:bg-d-tint flex-row items-center gap-1"
                    >
                      <Edit2 size={11} color={tokens.ink2} />
                      <Text className="text-[11.5px] font-bold text-ink2 dark:text-d-ink2">Edit</Text>
                    </Pressable>
                    <Pressable
                      onPress={() => handleDuplicate(item.id)}
                      className="px-2.5 py-1.5 rounded bg-surface2 dark:bg-d-surface2 active:bg-tint dark:active:bg-d-tint flex-row items-center gap-1"
                    >
                      <Copy size={11} color={tokens.ink2} />
                      <Text className="text-[11.5px] font-bold text-ink2 dark:text-d-ink2">Clone</Text>
                    </Pressable>
                    <Pressable
                      onPress={() => handleDeleteConfirm(item)}
                      className="px-2.5 py-1.5 rounded bg-danger/10 dark:bg-d-danger/10 active:opacity-60 flex-row items-center gap-1"
                    >
                      <Trash2 size={11} color={tokens.danger} />
                      <Text className="text-[11.5px] font-bold text-danger dark:text-d-danger">Delete</Text>
                    </Pressable>
                  </View>
                </View>
              </Card>
            );
          }}
          ListEmptyComponent={
            <View className="py-20 items-center justify-center px-6">
              <Bot size={36} color={tokens.muted} strokeWidth={1.5} />
              <Text className="text-[14.5px] text-muted dark:text-d-muted mt-3 text-center">No message bots configured.</Text>
              <Pressable
                onPress={openCreateModal}
                className="mt-4 px-4 py-2 bg-accent dark:bg-d-accent rounded-lg"
              >
                <Text className="text-accent-ink dark:text-d-accent-ink text-xs font-bold">Add Bot</Text>
              </Pressable>
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

      {/* Create/Edit Bot Modal */}
      <Modal transparent visible={showFormModal} animationType="slide">
        <View className="flex-1 bg-black/40 justify-end">
          <View className="bg-surface dark:bg-d-surface rounded-t-2xl p-5 gap-4" style={{ paddingBottom: insets.bottom + 16 }}>
            <View className="flex-row items-center justify-between border-b border-hairline dark:border-d-hairline pb-2.5">
              <Text className="text-base font-bold text-ink dark:text-d-ink">
                {editingBotId ? 'Edit Message Bot' : 'Create Message Bot'}
              </Text>
              <Pressable onPress={() => setShowFormModal(false)}>
                <Text className="text-muted dark:text-d-muted font-semibold text-sm">Cancel</Text>
              </Pressable>
            </View>

            <ScrollView contentContainerStyle={{ gap: 14 }}>
              <View>
                <Text className="text-xs font-semibold text-muted dark:text-d-muted mb-1.5 uppercase tracking-wide">Bot Name</Text>
                <TextInput
                  value={formName}
                  onChangeText={setFormName}
                  placeholder="e.g. Welcome greetings bot"
                  placeholderTextColor={tokens.faint}
                  className="bg-surface2 dark:bg-d-surface2 p-3 text-sm rounded-lg text-ink dark:text-d-ink font-medium"
                />
              </View>

              <View>
                <Text className="text-xs font-semibold text-muted dark:text-d-muted mb-1.5 uppercase tracking-wide">Trigger Keywords (comma separated)</Text>
                <TextInput
                  value={formTriggers}
                  onChangeText={setFormTriggers}
                  placeholder="e.g. hello, hi, greetings, support"
                  placeholderTextColor={tokens.faint}
                  className="bg-surface2 dark:bg-d-surface2 p-3 text-sm rounded-lg text-ink dark:text-d-ink font-medium"
                />
                <Text className="text-[10.5px] text-muted dark:text-d-muted mt-1">
                  The bot will respond when an inbound message matches any of these words exactly.
                </Text>
              </View>

              <View>
                <Text className="text-xs font-semibold text-muted dark:text-d-muted mb-1.5 uppercase tracking-wide">Auto Response Message Text</Text>
                <TextInput
                  value={formReplyText}
                  onChangeText={setFormReplyText}
                  placeholder="Type the message that will be sent back..."
                  placeholderTextColor={tokens.faint}
                  className="bg-surface2 dark:bg-d-surface2 p-3 text-sm rounded-lg text-ink dark:text-d-ink font-medium min-h-[80px]"
                  multiline
                />
              </View>

              <View className="flex-row justify-between items-center bg-surface2 dark:bg-d-surface2 p-3 rounded-lg mt-1">
                <View>
                  <Text className="text-xs font-semibold text-ink dark:text-d-ink">Active Status</Text>
                  <Text className="text-[10px] text-muted dark:text-d-muted mt-0.5">Allow bot to immediately process messages</Text>
                </View>
                <Toggle on={formIsActive} onChange={setFormIsActive} />
              </View>
            </ScrollView>

            <Pressable
              onPress={handleSubmit}
              className="bg-accent dark:bg-d-accent py-3.5 rounded-xl items-center active:opacity-90 mt-2"
            >
              <Text className="text-accent-ink dark:text-d-accent-ink font-bold text-sm">
                {editingBotId ? 'Save Bot Changes' : 'Create & Activate Bot'}
              </Text>
            </Pressable>
          </View>
        </View>
      </Modal>

      {/* Action Loading Modal */}
      <Modal transparent visible={actionLoading} animationType="fade">
        <View className="flex-1 bg-black/40 items-center justify-center">
          <View className="bg-surface dark:bg-d-surface p-6 rounded-2xl items-center gap-3 shadow-xl">
            <ActivityIndicator size="large" color={tokens.accent} />
            <Text className="text-sm font-bold text-ink dark:text-d-ink mt-2">Processing...</Text>
          </View>
        </View>
      </Modal>

      {/* Custom Reusable Dialog */}
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

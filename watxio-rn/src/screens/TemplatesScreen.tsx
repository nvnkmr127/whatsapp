// src/screens/TemplatesScreen.tsx — tab-filtered template library + FAB.

import React, { useMemo, useState, useEffect, useCallback } from 'react';
import { View, Text, ScrollView, FlatList, Pressable, TextInput, Modal, ActivityIndicator } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation, useFocusEffect } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { Search, Plus } from 'lucide-react-native';

import { useTokens, Tokens } from '@/theme';
import type { Template, RootStackParamList } from '@/types';
import { Chip } from '@/components/Chip';
import { CustomDialog } from '@/components/Dialog';
import { api } from '@/services/api';

type TabKey = 'All' | 'Approved' | 'Pending' | 'Rejected';
const TABS: TabKey[] = ['All', 'Approved', 'Pending', 'Rejected'];

export default function TemplatesScreen({ navigation }: any) {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const nav = navigation;
  const [tab, setTab] = useState<TabKey>('All');
  const [list, setList] = useState<Template[]>([]);
  const [loading, setLoading] = useState(true);
  const [isSearching, setIsSearching] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');

  // Creation States
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [newName, setNewName] = useState('');
  const [newCat, setNewCat] = useState<'Marketing' | 'Utility' | 'Authentication'>('Marketing');
  const [newLang, setNewLang] = useState('EN');
  const [newPreview, setNewPreview] = useState('');

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

  // Fetch Templates
  const fetchTemplates = useCallback(async (isSilent = false) => {
    if (!isSilent) setLoading(true);
    try {
      const response = await api.get('/v1/mobile/templates');
      const rawData = response.data || [];

      const mapped: Template[] = rawData.map((t: any) => {
        const bodyPreview = t.components?.find((c: any) => c.type === 'BODY')?.text || t.content || '';
        return {
          name: t.name,
          cat: t.category === 'MARKETING' ? 'Marketing' : t.category === 'UTILITY' ? 'Utility' : 'Authentication',
          lang: t.language || 'en_US',
          status: t.status === 'APPROVED' ? 'Approved' : t.status === 'PENDING' ? 'Pending' : t.status === 'DRAFT' ? 'Pending' : 'Rejected',
          uses: String(t.total_sent || 0),
          preview: bodyPreview,
        };
      });

      setList(mapped);
    } catch (err: any) {
      console.warn('Failed to load templates:', err);
    } finally {
      setLoading(false);
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      fetchTemplates(true);
    }, [fetchTemplates])
  );

  useEffect(() => {
    fetchTemplates();
  }, [fetchTemplates]);

  const items = useMemo(() => {
    let filtered = list.filter((x) => (tab === 'All' ? true : x.status === tab));
    if (searchQuery.trim().length > 0) {
      const q = searchQuery.toLowerCase();
      filtered = filtered.filter((x) => x.name.toLowerCase().includes(q) || x.preview.toLowerCase().includes(q));
    }
    return filtered;
  }, [tab, list, searchQuery]);

  // Compute counts for subtitles
  const approvedCount = useMemo(() => list.filter((x) => x.status === 'Approved').length, [list]);
  const pendingCount = useMemo(() => list.filter((x) => x.status === 'Pending').length, [list]);

  const onDuplicate = async (tpl: Template) => {
    setLoading(true);
    try {
      let suffix = 1;
      let dupName = `${tpl.name}_copy`;
      while (list.some((x) => x.name === dupName)) {
        dupName = `${tpl.name}_copy${suffix}`;
        suffix++;
      }

      const langMapping: Record<string, string> = {
        EN: 'en_US',
        ES: 'es_ES',
        PT: 'pt_BR',
        FR: 'fr_FR',
      };

      await api.post('/v1/mobile/templates', {
        name: dupName,
        category: tpl.cat.toUpperCase(),
        language: langMapping[tpl.lang] || tpl.lang,
        components: [
          {
            type: 'BODY',
            text: tpl.preview,
          },
        ],
      });

      await fetchTemplates();
      showDialog('Template Duplicated', `Created new template draft: "${dupName}"`);
    } catch (err: any) {
      showDialog('Error Duplicating', err.message || 'Could not duplicate template.');
    } finally {
      setLoading(false);
    }
  };

  const onCreateTemplate = async () => {
    if (!newName.trim() || !newPreview.trim()) {
      showDialog('Required Fields', 'Please fill in all fields before creating a template.');
      return;
    }
    const cleanName = newName.trim().toLowerCase().replace(/[^a-z0-9_]/g, '_');
    if (list.some((x) => x.name === cleanName)) {
      showDialog('Duplicate Name', `A template named "${cleanName}" already exists.`);
      return;
    }

    const langMapping: Record<string, string> = {
      EN: 'en_US',
      ES: 'es_ES',
      PT: 'pt_BR',
      FR: 'fr_FR',
    };

    setLoading(true);
    try {
      await api.post('/v1/mobile/templates', {
        name: cleanName,
        category: newCat.toUpperCase(),
        language: langMapping[newLang] || 'en_US',
        components: [
          {
            type: 'BODY',
            text: newPreview.trim(),
          },
        ],
      });

      setShowCreateModal(false);
      setNewName('');
      setNewPreview('');
      
      await fetchTemplates();
      showDialog('Template Created', `"${cleanName}" has been submitted as a draft.`);
    } catch (err: any) {
      showDialog('Failed to Create Template', err.message || 'Ensure name only contains lowercase alphanumeric and underscores.');
    } finally {
      setLoading(false);
    }
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
              placeholder="Search templates..."
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
        <View className="flex-row items-center justify-between px-[18px] pt-3.5 pb-1.5">
          <View>
            <Text className="text-[24px] font-bold text-ink dark:text-d-ink tracking-[-0.3px]">
              Templates
            </Text>
            <Text className="text-xs text-muted dark:text-d-muted mt-0.5">
              {approvedCount} approved · {pendingCount} pending/draft
            </Text>
          </View>
          <Pressable
            onPress={() => setIsSearching(true)}
            className="w-9 h-9 items-center justify-center rounded-full active:opacity-60"
            hitSlop={8}
          >
            <Search size={20} color={tokens.ink} strokeWidth={1.6} />
          </Pressable>
        </View>
      )}

      {/* Tabs scroll view */}
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        className="flex-grow-0"
        contentContainerStyle={{ paddingHorizontal: 14, gap: 4, paddingBottom: 10, paddingTop: 6 }}
      >
        {TABS.map((x) => (
          <Chip
            key={x}
            label={x}
            active={tab === x}
            onPress={() => setTab(x)}
          />
        ))}
      </ScrollView>

      {/* List */}
      {loading ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator size="large" color={tokens.accent} />
        </View>
      ) : (
        <FlatList
          className="flex-1"
          data={items}
          keyExtractor={(t) => t.name}
          contentContainerStyle={{ paddingHorizontal: 18, paddingBottom: 100, gap: 10 }}
          ListEmptyComponent={
            <View className="py-20 items-center justify-center">
              <Text className="text-sm text-muted dark:text-d-muted">No templates found.</Text>
            </View>
          }
          renderItem={({ item }) => (
            <TemplateCard
              tpl={item}
              tokens={tokens}
              onDuplicate={() => onDuplicate(item)}
              onUse={() => nav.navigate('Broadcast')}
            />
          )}
        />
      )}

      {/* FAB Button */}
      <Fab tokens={tokens} onPress={() => setShowCreateModal(true)} />

      {/* Create Template Modal */}
      <Modal transparent visible={showCreateModal} animationType="slide">
        <View className="flex-1 bg-black/40 justify-end">
          <View className="bg-surface dark:bg-d-surface rounded-t-2xl p-5 gap-4" style={{ paddingBottom: insets.bottom + 16 }}>
            <View className="flex-row items-center justify-between border-b border-hairline dark:border-d-hairline pb-2.5">
              <Text className="text-base font-bold text-ink dark:text-d-ink">Create WhatsApp Template</Text>
              <Pressable onPress={() => setShowCreateModal(false)}>
                <Text className="text-muted dark:text-d-muted font-semibold text-sm">Cancel</Text>
              </Pressable>
            </View>

            <ScrollView contentContainerStyle={{ gap: 14 }}>
              <View>
                <Text className="text-xs font-semibold text-muted dark:text-d-muted mb-1.5 uppercase tracking-wide">Template Name (lowercase, numbers, underscores)</Text>
                <TextInput
                  value={newName}
                  onChangeText={setNewName}
                  placeholder="e.g. order_completed_v3"
                  placeholderTextColor={tokens.faint}
                  className="bg-surface2 dark:bg-d-surface2 p-3 text-sm rounded-lg text-ink dark:text-d-ink font-medium"
                  autoFocus
                />
              </View>

              <View>
                <Text className="text-xs font-semibold text-muted dark:text-d-muted mb-1.5 uppercase tracking-wide">Category</Text>
                <View className="flex-row gap-2">
                  {(['Marketing', 'Utility', 'Authentication'] as const).map((cat) => (
                    <Pressable
                      key={cat}
                      onPress={() => setNewCat(cat)}
                      className={`flex-1 py-2 rounded-lg items-center border ${
                        newCat === cat ? 'bg-accent/10 border-accent' : 'bg-surface2 dark:bg-d-surface2 border-transparent'
                      }`}
                    >
                      <Text className={`text-xs font-bold ${newCat === cat ? 'text-accent' : 'text-ink2 dark:text-d-ink2'}`}>{cat}</Text>
                    </Pressable>
                  ))}
                </View>
              </View>

              <View>
                <Text className="text-xs font-semibold text-muted dark:text-d-muted mb-1.5 uppercase tracking-wide">Language</Text>
                <View className="flex-row gap-2">
                  {['EN', 'ES', 'PT', 'FR'].map((lang) => (
                    <Pressable
                      key={lang}
                      onPress={() => setNewLang(lang)}
                      className={`flex-1 py-2 rounded-lg items-center border ${
                        newLang === lang ? 'bg-accent/10 border-accent' : 'bg-surface2 dark:bg-d-surface2 border-transparent'
                      }`}
                    >
                      <Text className={`text-xs font-bold ${newLang === lang ? 'text-accent' : 'text-ink2 dark:text-d-ink2'}`}>{lang}</Text>
                    </Pressable>
                  ))}
                </View>
              </View>

              <View>
                <Text className="text-xs font-semibold text-muted dark:text-d-muted mb-1.5 uppercase tracking-wide">Template Text Body</Text>
                <TextInput
                  value={newPreview}
                  onChangeText={setNewPreview}
                  placeholder="e.g. Your order {{1}} has been received!"
                  placeholderTextColor={tokens.faint}
                  className="bg-surface2 dark:bg-d-surface2 p-3 text-sm rounded-lg text-ink dark:text-d-ink font-medium min-h-[70px]"
                  multiline
                />
              </View>
            </ScrollView>

            <Pressable
              onPress={onCreateTemplate}
              className="bg-accent dark:bg-d-accent py-3.5 rounded-xl items-center active:opacity-90 mt-2"
            >
              <Text className="text-accent-ink dark:text-d-accent-ink font-bold text-sm">Save & Submit as Draft</Text>
            </Pressable>
          </View>
        </View>
      </Modal>

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

function statusClasses(s: Template['status']) {
  if (s === 'Approved') return { dot: 'bg-ok dark:bg-d-ok', text: 'text-ok dark:text-d-ok' };
  if (s === 'Pending') return { dot: 'bg-warn dark:bg-d-warn', text: 'text-warn dark:text-d-warn' };
  return { dot: 'bg-danger dark:bg-d-danger', text: 'text-danger dark:text-d-danger' };
}

function TemplateCard({
  tpl,
  tokens,
  onDuplicate,
  onUse,
}: {
  tpl: Template;
  tokens: Tokens;
  onDuplicate: () => void;
  onUse: () => void;
}) {
  const sc = statusClasses(tpl.status);
  return (
    <View className="bg-surface dark:bg-d-surface rounded-lg p-[14px] gap-3">
      {/* Title & metadata */}
      <View className="flex-row items-center gap-[10px]">
        <View className="flex-1">
          <Text
            numberOfLines={1}
            className="text-sm font-semibold text-ink dark:text-d-ink font-mono"
          >
            {tpl.name}
          </Text>
          <Text className="text-[11.5px] text-muted dark:text-d-muted mt-0.5">
            {tpl.cat} · {tpl.lang} · {tpl.uses} sends
          </Text>
        </View>
        <View className="flex-row items-center gap-1.5">
          <View className={`w-1.5 h-1.5 rounded-full ${sc.dot}`} />
          <Text className={`text-[11.5px] font-medium ${sc.text}`}>{tpl.status}</Text>
        </View>
      </View>

      {/* Preview block */}
      <View className="bg-surface2 dark:bg-d-surface2 py-2.5 px-[13px] rounded-md">
        <Text className="text-ink2 dark:text-d-ink2 text-[13px] leading-5 font-normal">{tpl.preview}</Text>
      </View>

      {/* Actions */}
      <View className="flex-row h-8">
        <Pressable
          onPress={onDuplicate}
          className="bg-surface2 dark:bg-d-surface2 px-3.5 py-2 rounded-md active:bg-tint dark:active:bg-d-tint"
        >
          <Text className="text-ink2 dark:text-d-ink2 text-[12.5px] font-medium">Duplicate</Text>
        </Pressable>
        <View className="flex-1" />
        <Pressable
          onPress={onUse}
          className="bg-ink dark:bg-d-ink px-3.5 py-2 rounded-md active:opacity-90"
        >
          <Text className="text-bg dark:text-d-bg text-[12.5px] font-medium">Use template</Text>
        </Pressable>
      </View>
    </View>
  );
}

function Fab({ tokens, onPress }: { tokens: Tokens; onPress: () => void }) {
  return (
    <Pressable
      onPress={onPress}
      className="absolute bottom-6 right-5 h-12 px-[18px] bg-accent dark:bg-d-accent rounded-full flex-row items-center gap-1.5 shadow-lg shadow-accent/20 active:opacity-90 z-50"
    >
      <Plus size={16} color={tokens.accentInk} strokeWidth={2.4} />
      <Text className="text-accent-ink dark:text-d-accent-ink text-[13px] font-semibold">New template</Text>
    </Pressable>
  );
}

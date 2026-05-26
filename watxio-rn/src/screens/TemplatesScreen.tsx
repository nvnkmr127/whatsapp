// src/screens/TemplatesScreen.tsx — tab-filtered template library + FAB.

import React, { useMemo, useState } from 'react';
import { View, Text, ScrollView, FlatList, Pressable, TextInput, Modal } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { Search, Plus } from 'lucide-react-native';

import { useTokens, Tokens } from '@/theme';
import { TEMPLATES } from '@/data';
import type { Template, RootStackParamList } from '@/types';
import { Chip } from '@/components/Chip';
import { CustomDialog } from '@/components/Dialog';

type TabKey = 'All' | 'Approved' | 'Pending' | 'Rejected';
const TABS: TabKey[] = ['All', 'Approved', 'Pending', 'Rejected'];

export default function TemplatesScreen() {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const nav = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  const [tab, setTab] = useState<TabKey>('All');
  const [list, setList] = useState<Template[]>(TEMPLATES);
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

  const onDuplicate = (tpl: Template) => {
    setList((prev) => {
      let suffix = 1;
      let newName = `${tpl.name}_copy`;
      while (prev.some((x) => x.name === newName)) {
        newName = `${tpl.name}_copy${suffix}`;
        suffix++;
      }
      return [...prev, { ...tpl, name: newName, uses: '0' }];
    });
  };

  const onCreateTemplate = () => {
    if (!newName.trim() || !newPreview.trim()) {
      showDialog('Required Fields', 'Please fill in all fields before creating a template.');
      return;
    }
    const cleanName = newName.trim().toLowerCase().replace(/[^a-z0-9_]/g, '_');
    if (list.some((x) => x.name === cleanName)) {
      showDialog('Duplicate Name', `A template named "${cleanName}" already exists.`);
      return;
    }
    setList((prev) => [
      ...prev,
      {
        name: cleanName,
        cat: newCat,
        lang: newLang,
        status: 'Approved',
        uses: '0',
        preview: newPreview.trim(),
      },
    ]);
    setShowCreateModal(false);
    setNewName('');
    setNewPreview('');
    showDialog('Template Created', `"${cleanName}" has been created and approved.`);
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
              {approvedCount} approved · {pendingCount} pending
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
      <FlatList
        className="flex-1"
        data={items}
        keyExtractor={(t) => t.name}
        contentContainerStyle={{ paddingHorizontal: 18, paddingBottom: 100, gap: 10 }}
        renderItem={({ item }) => (
          <TemplateCard
            tpl={item}
            tokens={tokens}
            onDuplicate={() => onDuplicate(item)}
            onUse={() => nav.navigate('Broadcast')}
          />
        )}
      />

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

            <View className="gap-3">
              <View>
                <Text className="text-xs font-semibold text-muted dark:text-d-muted mb-1.5 uppercase tracking-wide">Template Name</Text>
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
            </View>

            <Pressable
              onPress={onCreateTemplate}
              className="bg-accent dark:bg-d-accent py-3.5 rounded-xl items-center active:opacity-90 mt-2"
            >
              <Text className="text-accent-ink dark:text-d-accent-ink font-bold text-sm">Save & Submit for Approval</Text>
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


// src/screens/AutomationsScreen.tsx — list of toggleable flows + starter grid.

import React, { useState } from 'react';
import { View, Text, ScrollView, Pressable, Modal, TextInput } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import {
  Plus, Sparkles, CreditCard, CalendarDays, Reply, Bot, Users,
  ShieldCheck, Globe, MessageSquareReply,
} from 'lucide-react-native';
import type { LucideIcon } from 'lucide-react-native';

import { useTokens } from '@/theme';
import { AUTOMATIONS } from '@/data';
import { SectionLabel } from '@/components/SectionLabel';
import { Toggle } from '@/components/Toggle';
import { IconButton } from '@/components/Button';
import { CustomDialog } from '@/components/Dialog';

const ICONS: Record<string, LucideIcon> = {
  Sparkles, CreditCard, CalendarDays, Reply, Bot, Users, ShieldCheck, Globe, MessageSquareReply,
};

const STARTERS: { Icon: LucideIcon; l: string; s: string; desc: string; iconKey: string }[] = [
  { Icon: Sparkles,     l: 'Birthday wishes', s: '1 step', desc: 'Sends birthday coupon automatically', iconKey: 'Sparkles' },
  { Icon: MessageSquareReply, l: 'NPS survey',  s: '3 steps', desc: 'Collects NPS feedback after order delivery', iconKey: 'MessageSquareReply' },
  { Icon: ShieldCheck,  l: 'Spam filter',     s: '2 steps', desc: 'Filters outgoing spam keywords', iconKey: 'ShieldCheck' },
  { Icon: Globe,        l: 'Auto-translate',  s: 'AI', desc: 'Translates replies to customer language via LLM', iconKey: 'Globe' },
];

export default function AutomationsScreen() {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const [list, setList] = useState(AUTOMATIONS);
  
  // Custom Flow Creator States
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [newFlowName, setNewFlowName] = useState('');
  const [newFlowDesc, setNewFlowDesc] = useState('');
  const [newFlowIcon, setNewFlowIcon] = useState('Sparkles');

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

  const toggleAt = (i: number) =>
    setList((arr) => arr.map((a, j) => (j === i ? { ...a, on: !a.on } : a)));

  const onAddStarter = (title: string, desc: string, iconKey: string) => {
    if (list.some((x) => x.name === title)) {
      showDialog('Flow Already Exists', `"${title}" flow is already active.`);
      return;
    }
    setList((prev) => [
      ...prev,
      {
        name: title,
        desc,
        icon: iconKey,
        on: true,
        runs: '0 / wk',
      },
    ]);
    showDialog('Flow Added', `"${title}" flow has been successfully added to your active list.`);
  };

  const onCreateCustomFlow = () => {
    if (!newFlowName.trim() || !newFlowDesc.trim()) {
      showDialog('Required Fields', 'Please fill in Flow Name and Description.');
      return;
    }
    if (list.some((x) => x.name.toLowerCase() === newFlowName.trim().toLowerCase())) {
      showDialog('Flow Already Exists', `"${newFlowName.trim()}" flow is already in use.`);
      return;
    }
    setList((prev) => [
      ...prev,
      {
        name: newFlowName.trim(),
        desc: newFlowDesc.trim(),
        icon: newFlowIcon,
        on: true,
        runs: '0 / wk',
      },
    ]);
    setShowCreateModal(false);
    setNewFlowName('');
    setNewFlowDesc('');
    showDialog('Flow Created', `"${newFlowName.trim()}" flow is now active!`);
  };

  const active = list.filter((a) => a.on).length;

  return (
    <View className="flex-1 bg-bg dark:bg-d-bg" style={{ paddingTop: insets.top }}>
      <View className="flex-row items-center justify-between px-[18px] pt-3.5 pb-2.5">
        <View>
          <Text className="text-2xl font-bold tracking-[-0.3px] text-ink dark:text-d-ink">
            Automations
          </Text>
          <Text className="text-[12.5px] text-muted dark:text-d-muted mt-1">
            {active} of {list.length} flows running · 6,750 msgs / week
          </Text>
        </View>
        <IconButton icon={Plus} onPress={() => setShowCreateModal(true)} />
      </View>


      <ScrollView contentContainerStyle={{ paddingHorizontal: 18, paddingBottom: 100 }}>
        <SectionLabel>Active flows</SectionLabel>
        <View className="gap-1">
          {list.map((a, i) => {
            const Icon = ICONS[a.icon] ?? Sparkles;
            return (
              <View
                key={a.name}
                className="flex-row items-center gap-3 bg-surface dark:bg-d-surface rounded-lg px-4 py-3.5"
              >
                <View className="w-9 h-9 rounded-md bg-surface2 dark:bg-d-surface2 items-center justify-center">
                  <Icon size={17} color={a.on ? tokens.accent : tokens.muted} strokeWidth={1.6} />
                </View>
                <View className="flex-1 min-w-0">
                  <Text className="text-sm font-semibold text-ink dark:text-d-ink">{a.name}</Text>
                  <Text
                    numberOfLines={1}
                    className="text-xs text-muted dark:text-d-muted mt-0.5"
                  >
                    {a.desc} · {a.runs}
                  </Text>
                </View>
                <Toggle on={a.on} onChange={() => toggleAt(i)} />
              </View>
            );
          })}
        </View>

        <SectionLabel>Starter templates</SectionLabel>
        <View className="flex-row flex-wrap gap-2">
          {STARTERS.map(({ Icon, l, s, desc, iconKey }) => (
            <Pressable
              key={l}
              onPress={() => onAddStarter(l, desc, iconKey)}
              className="flex-grow flex-shrink basis-[48%] bg-surface dark:bg-d-surface rounded-lg p-3.5 gap-2.5 active:bg-surface2 dark:active:bg-d-surface2"
            >
              <View className="w-8 h-8 rounded-md bg-surface2 dark:bg-d-surface2 items-center justify-center">
                <Icon size={16} color={tokens.ink2} strokeWidth={1.6} />
              </View>
              <View>
                <Text className="text-[13px] font-semibold text-ink dark:text-d-ink">{l}</Text>
                <Text className="text-[11px] text-muted dark:text-d-muted mt-0.25">{s}</Text>
              </View>
            </Pressable>
          ))}
        </View>
      </ScrollView>

      {/* Create Flow Modal */}
      <Modal transparent visible={showCreateModal} animationType="slide">
        <View className="flex-1 bg-black/40 justify-end">
          <View className="bg-surface dark:bg-d-surface rounded-t-2xl p-5 gap-4" style={{ paddingBottom: insets.bottom + 16 }}>
            <View className="flex-row items-center justify-between border-b border-hairline dark:border-d-hairline pb-2.5">
              <Text className="text-base font-bold text-ink dark:text-d-ink">Create Custom Automation Flow</Text>
              <Pressable onPress={() => setShowCreateModal(false)}>
                <Text className="text-muted dark:text-d-muted font-semibold text-sm">Cancel</Text>
              </Pressable>
            </View>

            <View className="gap-3">
              <View>
                <Text className="text-xs font-semibold text-muted dark:text-d-muted mb-1.5 uppercase tracking-wide">Flow Name</Text>
                <TextInput
                  value={newFlowName}
                  onChangeText={setNewFlowName}
                  placeholder="e.g. Abandoned cart re-engage"
                  placeholderTextColor={tokens.faint}
                  className="bg-surface2 dark:bg-d-surface2 p-3 text-sm rounded-lg text-ink dark:text-d-ink font-medium"
                  autoFocus
                />
              </View>

              <View>
                <Text className="text-xs font-semibold text-muted dark:text-d-muted mb-1.5 uppercase tracking-wide">Description</Text>
                <TextInput
                  value={newFlowDesc}
                  onChangeText={setNewFlowDesc}
                  placeholder="e.g. Sends discount code after 1 hour"
                  placeholderTextColor={tokens.faint}
                  className="bg-surface2 dark:bg-d-surface2 p-3 text-sm rounded-lg text-ink dark:text-d-ink font-medium min-h-[50px]"
                  multiline
                />
              </View>

              <View>
                <Text className="text-xs font-semibold text-muted dark:text-d-muted mb-1.5 uppercase tracking-wide">Flow Icon Trigger</Text>
                <View className="flex-row gap-2 flex-wrap">
                  {(['Sparkles', 'CreditCard', 'CalendarDays', 'Reply', 'Bot', 'Users'] as const).map((ic) => (
                    <Pressable
                      key={ic}
                      onPress={() => setNewFlowIcon(ic)}
                      className={`px-3 py-2 rounded-lg items-center border flex-row gap-1.5 ${
                        newFlowIcon === ic ? 'bg-accent/10 border-accent' : 'bg-surface2 dark:bg-d-surface2 border-transparent'
                      }`}
                    >
                      <Text className={`text-xs font-bold ${newFlowIcon === ic ? 'text-accent' : 'text-ink2 dark:text-d-ink2'}`}>{ic}</Text>
                    </Pressable>
                  ))}
                </View>
              </View>
            </View>

            <Pressable
              onPress={onCreateCustomFlow}
              className="bg-accent dark:bg-d-accent py-3.5 rounded-xl items-center active:opacity-90 mt-2"
            >
              <Text className="text-accent-ink dark:text-d-accent-ink font-bold text-sm">Activate Automation Flow</Text>
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

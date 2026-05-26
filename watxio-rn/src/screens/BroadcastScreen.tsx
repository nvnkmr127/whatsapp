// src/screens/BroadcastScreen.tsx — segment-based broadcast composer.

import React, { useState, useMemo } from 'react';
import { View, Text, ScrollView, Pressable, Modal, FlatList, ActivityIndicator } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { X, Users, Check, FileText, Send } from 'lucide-react-native';

import { useTokens } from '@/theme';
import { Card } from '@/components/Card';
import { SectionLabel } from '@/components/SectionLabel';
import { PrimaryButton, IconButton } from '@/components/Button';
import { TEMPLATES } from '@/data';
import type { Template } from '@/types';
import { CustomDialog } from '@/components/Dialog';

const SEGMENTS = [
  { name: 'VIP',           count: 142 },
  { name: 'Subscribers',   count: 1284 },
  { name: 'Recent buyers', count: 482 },
  { name: 'Wholesale',     count: 38 },
] as const;

export default function BroadcastScreen() {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const nav = useNavigation<any>();

  const [audience, setAudience] = useState<string[]>(['VIP']);
  const [schedule, setSchedule] = useState<'now' | 'later'>('now');
  const [selectedTemplate, setSelectedTemplate] = useState<Template>(TEMPLATES[1]); // cart_recovery
  const [showTemplatePicker, setShowTemplatePicker] = useState(false);
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


  const total = useMemo(
    () => audience.reduce((s, a) => s + (SEGMENTS.find((x) => x.name === a)?.count ?? 0), 0),
    [audience],
  );

  const toggle = (n: string) =>
    setAudience((a) => (a.includes(n) ? a.filter((x) => x !== n) : [...a, n]));

  const handleSend = () => {
    if (selectedTemplate.status !== 'Approved') {
      showDialog('Unable to Send', 'Only approved templates can be used for broadcasts.');
      return;
    }
    if (total === 0) {
      showDialog('No Contacts Selected', 'Please select at least one audience segment.');
      return;
    }
    setLoading(true);
    setTimeout(() => {
      setLoading(false);
      if (schedule === 'now') {
        showDialog(
          'Broadcast Sent',
          `Your broadcast "${selectedTemplate.name}" has been successfully queued for ${total.toLocaleString()} contacts.`,
          [{ text: 'OK', onPress: () => nav.goBack() }]
        );
      } else {
        showDialog(
          'Broadcast Scheduled',
          `Your broadcast "${selectedTemplate.name}" has been scheduled for ${total.toLocaleString()} contacts.`,
          [{ text: 'OK', onPress: () => nav.goBack() }]
        );
      }
    }, 1200);
  };

  return (
    <View className="flex-1 bg-bg dark:bg-d-bg" style={{ paddingTop: insets.top }}>
      {/* Header */}
      <View className="px-3 py-2.5 flex-row items-center gap-2">
        <IconButton icon={X} onPress={() => nav.goBack()} />
        <Text className="flex-1 text-base font-bold text-ink dark:text-d-ink">
          New broadcast
        </Text>
         <Pressable
          onPress={() => {
            showDialog('Draft Saved', 'Your broadcast draft has been saved successfully.', [
              { text: 'OK', onPress: () => nav.goBack() }
            ]);
          }}
          className="px-3 py-1.5 rounded-full active:bg-surface2 dark:active:bg-d-surface2"
        >
          <Text className="text-muted dark:text-d-muted text-xs font-semibold">Save draft</Text>
        </Pressable>
      </View>

      <ScrollView contentContainerStyle={{ paddingHorizontal: 18, paddingBottom: 120 }}>
        {/* Headline target counter */}
        <View className="py-1 flex-row items-end justify-between">
          <View>
            <Text className="text-xs text-muted dark:text-d-muted">Sending to</Text>
            <Text className="text-[36px] font-bold text-ink dark:text-d-ink tracking-[-0.8px] mt-1">
              {total.toLocaleString()}
            </Text>
            <Text className="text-xs text-muted dark:text-d-muted mt-0.5">contacts</Text>
          </View>
          <Users size={32} color={tokens.muted} strokeWidth={1.4} />
        </View>

        <SectionLabel>Audience</SectionLabel>
        <View className="gap-1">
          {SEGMENTS.map((s) => {
            const on = audience.includes(s.name);
            return (
              <Pressable
                key={s.name}
                onPress={() => toggle(s.name)}
                className="flex-row items-center gap-3 px-3.5 py-3.5 rounded-md bg-surface dark:bg-d-surface active:bg-surface2 dark:active:bg-d-surface2"
              >
                <View
                  className={`w-5 h-5 rounded-sm items-center justify-center ${
                    on ? 'bg-accent dark:bg-d-accent border-0' : 'border border-faint dark:border-d-faint bg-transparent'
                  }`}
                  style={on ? {} : { borderWidth: 1.5 }}
                >
                  {on ? <Check size={12} color={tokens.accentInk} strokeWidth={3} /> : null}
                </View>
                <View className="flex-1">
                  <Text className="text-sm font-medium text-ink dark:text-d-ink">{s.name}</Text>
                  <Text className="text-[11.5px] text-muted dark:text-d-muted mt-0.5">
                    {s.count.toLocaleString()} contacts
                  </Text>
                </View>
              </Pressable>
            );
          })}
        </View>

        <SectionLabel action="Browse →" onActionPress={() => setShowTemplatePicker(true)}>Template</SectionLabel>
        <Pressable onPress={() => setShowTemplatePicker(true)}>
          <Card pad={14}>
            <View className="flex-row items-start gap-2.5">
              <View className="w-[38px] h-[38px] rounded-md bg-surface2 dark:bg-d-surface2 items-center justify-center">
                <FileText size={18} color={tokens.ink2} strokeWidth={1.6} />
              </View>
              <View className="flex-1">
                <View className="flex-row items-center gap-2">
                  <Text className="text-sm font-semibold text-ink dark:text-d-ink font-mono">
                    {selectedTemplate.name}
                  </Text>
                  <View className="flex-row items-center gap-1">
                    <View
                      className={`w-1.5 h-1.5 rounded-full ${
                        selectedTemplate.status === 'Approved' ? 'bg-ok dark:bg-d-ok' : 'bg-warn dark:bg-d-warn'
                      }`}
                    />
                    <Text
                      className={`text-[11px] font-semibold ${
                        selectedTemplate.status === 'Approved' ? 'text-ok dark:text-d-ok' : 'text-warn dark:text-d-warn'
                      }`}
                    >
                      {selectedTemplate.status}
                    </Text>
                  </View>
                </View>
                <Text className="text-[11.5px] text-muted dark:text-d-muted mt-0.5">
                  {selectedTemplate.cat} · {selectedTemplate.lang} · {selectedTemplate.uses} uses
                </Text>
              </View>
            </View>
            <View className="mt-3 py-[11px] px-[13px] bg-bubble-out dark:bg-d-bubble-out rounded-md">
              <Text className="text-[13px] text-ink dark:text-d-ink leading-5">
                {selectedTemplate.preview}
              </Text>
            </View>
          </Card>
        </Pressable>

        <SectionLabel>Schedule</SectionLabel>
        <View className="flex-row gap-2">
          {([
            { id: 'now',   l: 'Send now',  s: 'Estimated 2–4 min' },
            { id: 'later', l: 'Schedule',  s: 'Tue · 10:00 AM' },
          ] as const).map((o) => {
            const on = schedule === o.id;
            return (
              <Pressable
                key={o.id}
                onPress={() => setSchedule(o.id)}
                className={`flex-1 px-3.5 py-3.5 rounded-md bg-surface dark:bg-d-surface active:bg-surface2 dark:active:bg-d-surface2 ${
                  on ? 'border-[1.5px] border-accent dark:border-d-accent' : ''
                }`}
              >
                <Text className={`text-[13.5px] font-semibold ${on ? 'text-accent dark:text-d-accent' : 'text-ink dark:text-d-ink'}`}>
                  {o.l}
                </Text>
                <Text className="text-[11.5px] text-muted dark:text-d-muted mt-[3px]">{o.s}</Text>
              </Pressable>
            );
          })}
        </View>
      </ScrollView>

      {/* Sticky CTA */}
      <View
        className="absolute left-0 right-0 px-[18px]"
        style={{ bottom: insets.bottom + 12 }}
      >
        <PrimaryButton
          full
          size="lg"
          icon={Send}
          label={schedule === 'now' ? `Send to ${total.toLocaleString()}` : `Schedule for ${total.toLocaleString()}`}
          onPress={handleSend}
        />
      </View>

      {/* Template Selector Modal */}
      <Modal transparent visible={showTemplatePicker} animationType="slide">
        <View className="flex-1 bg-black/40 justify-end">
          <View className="bg-surface dark:bg-d-surface rounded-t-2xl max-h-[75%]" style={{ paddingBottom: insets.bottom + 16 }}>
            <View className="flex-row items-center justify-between px-[18px] py-4 border-b border-hairline dark:border-d-hairline">
              <Text className="text-base font-bold text-ink dark:text-d-ink">Select Template</Text>
              <Pressable onPress={() => setShowTemplatePicker(false)} className="p-1">
                <Text className="text-accent dark:text-d-accent font-semibold text-sm">Close</Text>
              </Pressable>
            </View>
            <FlatList
              data={TEMPLATES}
              keyExtractor={(item) => item.name}
              contentContainerStyle={{ padding: 18, gap: 10 }}
              renderItem={({ item }) => {
                const isApproved = item.status === 'Approved';
                return (
                  <Pressable
                    onPress={() => {
                      if (!isApproved) {
                        showDialog('Template Not Approved', `This template status is currently "${item.status}". You can only select Approved templates.`);
                        return;
                      }
                      setSelectedTemplate(item);
                      setShowTemplatePicker(false);
                    }}
                    className={`p-3.5 rounded-lg border bg-surface2 dark:bg-d-surface2 ${
                      selectedTemplate.name === item.name ? 'border-accent dark:border-d-accent' : 'border-transparent'
                    }`}
                  >
                    <View className="flex-row justify-between items-center mb-1.5">
                      <Text className="font-mono text-[13px] font-bold text-ink dark:text-d-ink">{item.name}</Text>
                      <View className="flex-row items-center gap-1">
                        <View className={`w-1.5 h-1.5 rounded-full ${isApproved ? 'bg-ok dark:bg-d-ok' : 'bg-warn dark:bg-d-warn'}`} />
                        <Text className={`text-[11px] font-semibold ${isApproved ? 'text-ok dark:text-d-ok' : 'text-warn dark:text-d-warn'}`}>{item.status}</Text>
                      </View>
                    </View>
                    <Text numberOfLines={2} className="text-xs text-muted dark:text-d-muted leading-4">{item.preview}</Text>
                  </Pressable>
                );
              }}
            />
          </View>
        </View>
      </Modal>

      {/* Action Loading Modal */}
      <Modal transparent visible={loading} animationType="fade">
        <View className="flex-1 bg-black/40 items-center justify-center">
          <View className="bg-surface dark:bg-d-surface p-6 rounded-2xl items-center gap-3 shadow-xl">
            <ActivityIndicator size="large" color={tokens.accent} />
            <Text className="text-sm font-bold text-ink dark:text-d-ink mt-2">Processing Broadcast...</Text>
          </View>
        </View>
      </Modal>

      {/* Custom Dialog */}
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


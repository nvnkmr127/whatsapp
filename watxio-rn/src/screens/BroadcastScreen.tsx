// src/screens/BroadcastScreen.tsx — segment-based broadcast composer.

import React, { useState, useMemo, useEffect, useCallback } from 'react';
import { View, Text, ScrollView, Pressable, Modal, FlatList, ActivityIndicator } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { X, Users, Check, FileText, Send } from 'lucide-react-native';

import { useTokens } from '@/theme';
import { Card } from '@/components/Card';
import { SectionLabel } from '@/components/SectionLabel';
import { PrimaryButton, IconButton } from '@/components/Button';
import type { Template } from '@/types';
import { CustomDialog } from '@/components/Dialog';
import { api } from '@/services/api';

interface TagItem {
  id: number;
  name: string;
  count?: number; // Optional local/remote count
}

export default function BroadcastScreen({ navigation }: any) {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const nav = navigation;

  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [tagsList, setTagsList] = useState<TagItem[]>([]);
  const [templatesList, setTemplatesList] = useState<any[]>([]);

  const [selectedTagId, setSelectedTagId] = useState<number | null>(null);
  const [schedule, setSchedule] = useState<'now' | 'later'>('now');
  const [selectedTemplate, setSelectedTemplate] = useState<any | null>(null);
  const [showTemplatePicker, setShowTemplatePicker] = useState(false);

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

  const loadBroadcastFormOptions = useCallback(async () => {
    setLoading(true);
    try {
      // 1. Load tags
      const tagsResponse = await api.get('/v1/mobile/contacts/tags');
      setTagsList(tagsResponse || []);
      if (tagsResponse && tagsResponse.length > 0) {
        setSelectedTagId(tagsResponse[0].id);
      }

      // 2. Load templates
      const templatesResponse = await api.get('/v1/mobile/templates');
      const data = templatesResponse.data || [];
      setTemplatesList(data);
      
      const approved = data.find((t: any) => t.status === 'APPROVED');
      if (approved) {
        setSelectedTemplate(approved);
      } else if (data.length > 0) {
        setSelectedTemplate(data[0]);
      }
    } catch (err: any) {
      console.warn('Failed to load broadcast options:', err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadBroadcastFormOptions();
  }, [loadBroadcastFormOptions]);

  const handleSend = async () => {
    if (!selectedTemplate) {
      showDialog('Template Required', 'Please select a WhatsApp message template.');
      return;
    }
    if (selectedTemplate.status !== 'APPROVED') {
      showDialog('Unable to Send', 'Only approved templates can be used for campaigns.');
      return;
    }
    if (!selectedTagId) {
      showDialog('No Contacts Selected', 'Please select an audience segment tag.');
      return;
    }

    setActionLoading(true);
    try {
      const selectedTagName = tagsList.find((t) => t.id === selectedTagId)?.name || 'Campaign';
      const campaignName = `MobileBroadcast_${selectedTemplate.name}_${selectedTagName}_${Date.now()}`;

      await api.post('/v1/mobile/campaigns', {
        name: campaignName,
        template_id: selectedTemplate.id,
        tag_id: selectedTagId,
        variables: [],
      });

      setActionLoading(false);
      showDialog(
        'Broadcast Queued',
        `Your campaign "${campaignName}" has been successfully created and queued for delivery.`,
        [{ text: 'OK', onPress: () => nav.goBack() }]
      );
    } catch (err: any) {
      setActionLoading(false);
      showDialog('Campaign Failed', err.message || 'Error occurred while creating the broadcast.');
    }
  };

  const selectedTemplatePreview = useMemo(() => {
    if (!selectedTemplate) return 'No template selected';
    return selectedTemplate.components?.find((c: any) => c.type === 'BODY')?.text || selectedTemplate.content || '';
  }, [selectedTemplate]);

  if (loading) {
    return (
      <View className="flex-1 bg-bg dark:bg-d-bg items-center justify-center">
        <ActivityIndicator size="large" color={tokens.accent} />
        <Text className="text-xs text-muted dark:text-d-muted mt-2">Loading broadcast parameters...</Text>
      </View>
    );
  }

  return (
    <View className="flex-1 bg-bg dark:bg-d-bg" style={{ paddingTop: insets.top }}>
      {/* Header */}
      <View className="px-3 py-2.5 flex-row items-center gap-2">
        <IconButton icon={X} onPress={() => nav.goBack()} />
        <Text className="flex-1 text-base font-bold text-ink dark:text-d-ink">
          New broadcast
        </Text>
      </View>

      <ScrollView contentContainerStyle={{ paddingHorizontal: 18, paddingBottom: 120 }}>
        <SectionLabel>Audience Tag Segment</SectionLabel>
        <View className="gap-1">
          {tagsList.map((s) => {
            const on = selectedTagId === s.id;
            return (
              <Pressable
                key={s.id}
                onPress={() => setSelectedTagId(s.id)}
                className="flex-row items-center gap-3 px-3.5 py-3.5 rounded-md bg-surface dark:bg-d-surface active:bg-surface2 dark:active:bg-d-surface2"
              >
                <View
                  className={`w-5 h-5 rounded-full items-center justify-center ${
                    on ? 'bg-accent dark:bg-d-accent border-0' : 'border border-faint dark:border-d-faint bg-transparent'
                  }`}
                  style={on ? {} : { borderWidth: 1.5 }}
                >
                  {on ? <Check size={12} color={tokens.accentInk} strokeWidth={3} /> : null}
                </View>
                <View className="flex-1">
                  <Text className="text-sm font-medium text-ink dark:text-d-ink">{s.name}</Text>
                  <Text className="text-[11.5px] text-muted dark:text-d-muted mt-0.5">
                    Targeting tag segment
                  </Text>
                </View>
              </Pressable>
            );
          })}
          {tagsList.length === 0 && (
            <View className="p-4 bg-surface dark:bg-d-surface rounded items-center justify-center">
              <Text className="text-xs text-muted dark:text-d-muted italic">No contact tags loaded from server.</Text>
            </View>
          )}
        </View>

        <SectionLabel action="Browse →" onActionPress={() => setShowTemplatePicker(true)}>Template</SectionLabel>
        {selectedTemplate ? (
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
                          selectedTemplate.status === 'APPROVED' ? 'bg-ok dark:bg-d-ok' : 'bg-warn dark:bg-d-warn'
                        }`}
                      />
                      <Text
                        className={`text-[11px] font-semibold ${
                          selectedTemplate.status === 'APPROVED' ? 'text-ok dark:text-d-ok' : 'text-warn dark:text-d-warn'
                        }`}
                      >
                        {selectedTemplate.status}
                      </Text>
                    </View>
                  </View>
                  <Text className="text-[11.5px] text-muted dark:text-d-muted mt-0.5">
                    {selectedTemplate.category} · {selectedTemplate.language} · {selectedTemplate.total_sent || 0} uses
                  </Text>
                </View>
              </View>
              <View className="mt-3 py-[11px] px-[13px] bg-bubble-out dark:bg-d-bubble-out rounded-md">
                <Text className="text-[13px] text-ink dark:text-d-ink leading-5">
                  {selectedTemplatePreview}
                </Text>
              </View>
            </Card>
          </Pressable>
        ) : (
          <Pressable onPress={() => setShowTemplatePicker(true)} className="p-6 bg-surface dark:bg-d-surface rounded-lg items-center border border-dashed border-hairline">
            <Text className="text-xs text-muted dark:text-d-muted">Click to select an approved template</Text>
          </Pressable>
        )}

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
          label={schedule === 'now' ? 'Send Broadcast Campaign' : 'Schedule Broadcast Campaign'}
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
              data={templatesList}
              keyExtractor={(item) => String(item.id)}
              contentContainerStyle={{ padding: 18, gap: 10 }}
              renderItem={({ item }) => {
                const isApproved = item.status === 'APPROVED';
                const bodyPreview = item.components?.find((c: any) => c.type === 'BODY')?.text || item.content || '';
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
                      selectedTemplate?.id === item.id ? 'border-accent dark:border-d-accent' : 'border-transparent'
                    }`}
                  >
                    <View className="flex-row justify-between items-center mb-1.5">
                      <Text className="font-mono text-[13px] font-bold text-ink dark:text-d-ink">{item.name}</Text>
                      <View className="flex-row items-center gap-1">
                        <View className={`w-1.5 h-1.5 rounded-full ${isApproved ? 'bg-ok dark:bg-d-ok' : 'bg-warn dark:bg-d-warn'}`} />
                        <Text className={`text-[11px] font-semibold ${isApproved ? 'text-ok dark:text-d-ok' : 'text-warn dark:text-d-warn'}`}>{item.status}</Text>
                      </View>
                    </View>
                    <Text numberOfLines={2} className="text-xs text-muted dark:text-d-muted leading-4">{bodyPreview}</Text>
                  </Pressable>
                );
              }}
            />
          </View>
        </View>
      </Modal>

      {/* Action Loading Modal */}
      <Modal transparent visible={actionLoading} animationType="fade">
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

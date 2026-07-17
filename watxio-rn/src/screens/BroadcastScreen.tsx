// src/screens/BroadcastScreen.tsx — campaign list + composer.

import React, { useState, useMemo, useEffect, useCallback } from 'react';
import { View, Text, ScrollView, Pressable, Modal, FlatList, ActivityIndicator, RefreshControl, Platform } from 'react-native';
import DateTimePicker from '@react-native-community/datetimepicker';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { X, Users, Check, FileText, Send, Plus, ChevronLeft, Calendar } from 'lucide-react-native';

import { useTokens } from '@/theme';
import { Card } from '@/components/Card';
import { SectionLabel } from '@/components/SectionLabel';
import { PrimaryButton, IconButton } from '@/components/Button';
import type { Template } from '@/types';
import { ListSkeleton, CampaignsListSkeleton } from '@/components/ListItemSkeleton';
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

  const [mode, setMode] = useState<'list' | 'create'>('list');
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [refreshing, setRefreshing] = useState(false);

  // Campaigns list and pagination state
  const [campaignsList, setCampaignsList] = useState<any[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loadingMore, setLoadingMore] = useState(false);

  // Composer Form options
  const [tagsList, setTagsList] = useState<TagItem[]>([]);
  const [templatesList, setTemplatesList] = useState<any[]>([]);

  const [selectedTagId, setSelectedTagId] = useState<number | null>(null);
  const [schedule, setSchedule] = useState<'now' | 'later'>('now');
  const [scheduledDate, setScheduledDate] = useState<Date>(() => {
    const d = new Date();
    d.setMinutes(d.getMinutes() + 10); // default to 10 mins in the future
    return d;
  });
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [showTimePicker, setShowTimePicker] = useState(false);
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

  const loadCampaigns = useCallback(async (pageNum = 1, append = false, isRefresh = false) => {
    if (pageNum === 1 && !isRefresh) {
      setLoading(true);
    } else if (pageNum > 1) {
      setLoadingMore(true);
    }
    try {
      const response = await api.get(`/v1/mobile/campaigns?page=${pageNum}`);
      const rawData = response?.data || [];
      const currentPageNum = response?.current_page || 1;
      const lastPageNum = response?.last_page || 1;

      setPage(currentPageNum);
      setLastPage(lastPageNum);

      if (append) {
        setCampaignsList((prev) => {
          const combined = [...prev, ...rawData];
          const seenIds = new Set();
          return combined.filter((c) => {
            if (seenIds.has(c.id)) return false;
            seenIds.add(c.id);
            return true;
          });
        });
      } else {
        setCampaignsList(rawData);
      }
    } catch (err: any) {
      console.warn('Failed to load campaigns:', err);
    } finally {
      setLoading(false);
      setRefreshing(false);
      setLoadingMore(false);
    }
  }, []);

  const loadMore = () => {
    if (loading || loadingMore || page >= lastPage) return;
    loadCampaigns(page + 1, true, false);
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
    if (mode === 'list') {
      loadCampaigns();
    } else {
      loadBroadcastFormOptions();
    }
  }, [mode, loadCampaigns, loadBroadcastFormOptions]);

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

      const payload: any = {
        name: campaignName,
        template_id: selectedTemplate.id,
        tag_id: selectedTagId,
        variables: [],
      };

      if (schedule === 'later') {
        payload.scheduled_at = scheduledDate.toISOString();
      }

      await api.post('/v1/mobile/campaigns', payload);

      setActionLoading(false);
      showDialog(
        'Broadcast Queued',
        `Your campaign "${campaignName}" has been successfully created and queued for delivery.`,
        [{
          text: 'OK',
          onPress: () => {
            setMode('list');
            loadCampaigns();
          }
        }]
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

  const onRefresh = () => {
    setRefreshing(true);
    loadCampaigns(1, false, true);
  };

  return (
    <View className="flex-1 bg-bg dark:bg-d-bg" style={{ paddingTop: insets.top }}>
      {/* Header */}
      {mode === 'list' ? (
        <View className="px-3 py-2.5 flex-row items-center gap-2 border-b border-hairline dark:border-d-hairline">
          <IconButton icon={X} onPress={() => nav.goBack()} />
          <Text className="flex-1 text-base font-bold text-ink dark:text-d-ink">
            Broadcast Campaigns
          </Text>
          <IconButton icon={Plus} onPress={() => setMode('create')} />
        </View>
      ) : (
        <View className="px-3 py-2.5 flex-row items-center gap-2 border-b border-hairline dark:border-d-hairline">
          <IconButton icon={ChevronLeft} onPress={() => setMode('list')} />
          <Text className="flex-1 text-base font-bold text-ink dark:text-d-ink">
            New broadcast
          </Text>
        </View>
      )}

      {mode === 'list' ? (
        loading && campaignsList.length === 0 ? (
          <CampaignsListSkeleton count={4} />
        ) : (
          <FlatList
            data={campaignsList}
            keyExtractor={(item) => String(item.id)}
            refreshControl={
              <RefreshControl
                refreshing={refreshing}
                onRefresh={onRefresh}
                tintColor={tokens.accent}
              />
            }
            contentContainerStyle={{ padding: 18, gap: 12 }}
            onEndReached={loadMore}
            onEndReachedThreshold={0.4}
            ListFooterComponent={
              loadingMore ? (
                <View className="py-4 items-center justify-center">
                  <ActivityIndicator size="small" color={tokens.accent} />
                </View>
              ) : null
            }
            renderItem={({ item }) => {
              const status = item.status || 'pending';
              const isCompleted = status === 'completed' || status === 'sent';
              const isFailed = status === 'failed';
              const statusColor = isCompleted ? tokens.ok : isFailed ? tokens.danger : tokens.warn;

              return (
                <Pressable
                  onPress={() => nav.navigate('CampaignDetail', { campaignId: item.id })}
                  className="active:opacity-80"
                >
                  <Card pad={14}>
                    <View className="flex-row justify-between items-start gap-2">
                      <View className="flex-1">
                        <Text className="text-sm font-semibold text-ink dark:text-d-ink" numberOfLines={1}>
                          {item.name}
                        </Text>
                        <Text className="text-xs text-muted dark:text-d-muted mt-1 font-mono">
                          Template: {item.template?.name || 'Unknown'}
                        </Text>
                      </View>
                      <View className="px-2.5 py-0.5 rounded-full bg-surface2 dark:bg-d-surface2">
                        <Text style={{ color: statusColor }} className="text-[11px] font-bold uppercase tracking-wider">
                          {status}
                        </Text>
                      </View>
                    </View>

                    <View className="flex-row items-center justify-between border-t border-hairline dark:border-d-hairline mt-3 pt-3">
                      <View className="flex-row items-center gap-1">
                        <Users size={12} color={tokens.muted} />
                        <Text className="text-[11.5px] text-muted dark:text-d-muted">
                          {item.total_contacts || 0} recipients
                        </Text>
                      </View>
                      <View className="flex-row items-center gap-1">
                        <Calendar size={12} color={tokens.muted} />
                        <Text className="text-[11.5px] text-muted dark:text-d-muted">
                          {new Date(item.created_at).toLocaleDateString()}
                        </Text>
                      </View>
                    </View>
                  </Card>
                </Pressable>
              );
            }}
            ListEmptyComponent={
              <View className="py-20 items-center justify-center">
                <Text className="text-sm text-muted dark:text-d-muted">No campaigns created yet.</Text>
                <Pressable
                  onPress={() => setMode('create')}
                  className="mt-4 px-4 py-2 bg-accent dark:bg-d-accent rounded-lg"
                >
                  <Text className="text-accent-ink dark:text-d-accent-ink text-xs font-bold">New Broadcast</Text>
                </Pressable>
              </View>
            }
          />
        )
      ) : (
        /* Create campaign form mode */
        <View className="flex-1">
          {loading ? (
            <View className="flex-1 mt-4">
              <ListSkeleton count={6} />
            </View>
          ) : (
            <>
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
                    { id: 'later', l: 'Schedule',  s: scheduledDate.toLocaleDateString([], { month: 'short', day: 'numeric' }) + ' · ' + scheduledDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) },
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

                {schedule === 'later' && (
                  <Pressable
                    onPress={() => setShowDatePicker(true)}
                    className="mt-3.5 flex-row items-center justify-between px-3.5 py-3.5 rounded-md bg-surface dark:bg-d-surface active:bg-surface2 dark:active:bg-d-surface2 border border-hairline dark:border-d-hairline"
                  >
                    <View className="flex-row items-center gap-2.5">
                      <Calendar size={18} color={tokens.accent} />
                      <View>
                        <Text className="text-xs text-muted dark:text-d-muted">Scheduled For</Text>
                        <Text className="text-sm font-semibold text-ink dark:text-d-ink mt-0.5">
                          {scheduledDate.toLocaleString([], {
                            month: 'short',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                          })}
                        </Text>
                      </View>
                    </View>
                    <Text className="text-xs text-accent dark:text-d-accent font-medium">Change</Text>
                  </Pressable>
                )}

                {showDatePicker && (
                  <DateTimePicker
                    value={scheduledDate}
                    mode="date"
                    display="default"
                    minimumDate={new Date()}
                    onChange={(event, selectedDate) => {
                      setShowDatePicker(false);
                      if (selectedDate) {
                        const newDate = new Date(scheduledDate);
                        newDate.setFullYear(selectedDate.getFullYear(), selectedDate.getMonth(), selectedDate.getDate());
                        setScheduledDate(newDate);
                        setTimeout(() => {
                          setShowTimePicker(true);
                        }, 300);
                      }
                    }}
                  />
                )}

                {showTimePicker && (
                  <DateTimePicker
                    value={scheduledDate}
                    mode="time"
                    display="default"
                    onChange={(event, selectedTime) => {
                      setShowTimePicker(false);
                      if (selectedTime) {
                        const newDate = new Date(scheduledDate);
                        newDate.setHours(selectedTime.getHours(), selectedTime.getMinutes(), 0, 0);
                        setScheduledDate(newDate);
                      }
                    }}
                  />
                )}
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
            </>
          )}
        </View>
      )}

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

import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, ScrollView, ActivityIndicator } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation, useRoute, RouteProp } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import type { RootStackParamList } from '@/types';
import { ChevronLeft, Info, Calendar, Users, Eye, CheckCheck, FileText, AlertTriangle } from 'lucide-react-native';

import { useTokens } from '@/theme';
import { IconButton } from '@/components/Button';
import { Card } from '@/components/Card';
import { SectionLabel } from '@/components/SectionLabel';
import { ListSkeleton } from '@/components/ListItemSkeleton';
import { api } from '@/services/api';
import { CustomDialog } from '@/components/Dialog';
import { safeGoBack } from '@/navigation/navigationRef';

type RouteProps = RouteProp<RootStackParamList, 'CampaignDetail'>;

export default function CampaignDetailScreen() {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const nav = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  const route = useRoute<RouteProps>();
  const { campaignId } = route.params;

  const [loading, setLoading] = useState(true);
  const [campaign, setCampaign] = useState<any>(null);

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

  const fetchCampaignDetails = useCallback(async () => {
    setLoading(true);
    try {
      const response = await api.get(`/v1/mobile/campaigns/${campaignId}`);
      setCampaign(response);
    } catch (err: any) {
      console.warn('Failed to load campaign details:', err);
      showDialog('Error', 'Could not load campaign details from server.');
    } finally {
      setLoading(false);
    }
  }, [campaignId]);

  useEffect(() => {
    fetchCampaignDetails();
  }, [fetchCampaignDetails]);

  if (loading && !campaign) {
    return (
      <View className="flex-1 bg-bg dark:bg-d-bg mt-4">
        <ListSkeleton count={6} />
      </View>
    );
  }

  const status = campaign?.status || 'pending';
  const isCompleted = status === 'completed' || status === 'sent';
  const isFailed = status === 'failed';
  const statusColor = isCompleted ? tokens.ok : isFailed ? tokens.danger : tokens.warn;

  const deliveryRate = campaign?.delivery_percentage !== undefined ? Math.round(campaign.delivery_percentage) : 0;
  const readRate = campaign?.read_percentage !== undefined ? Math.round(campaign.read_percentage) : 0;

  const templatePreview = campaign?.template?.components?.find((c: any) => c.type === 'BODY')?.text || campaign?.template?.content || 'No template preview available.';

  return (
    <View className="flex-1 bg-bg dark:bg-d-bg" style={{ paddingTop: insets.top }}>
      {/* Header */}
      <View className="flex-row items-center gap-[10px] px-3 pb-3 border-b border-hairline dark:border-d-hairline">
        <IconButton icon={ChevronLeft} onPress={() => safeGoBack(nav, 'Main')} />
        <Text className="text-[17px] font-bold text-ink dark:text-d-ink">Campaign Details</Text>
      </View>

      <ScrollView contentContainerStyle={{ padding: 18, paddingBottom: insets.bottom + 24, gap: 16 }}>
        {/* Basic Campaign Info */}
        <Card pad={14}>
          <View className="flex-row justify-between items-start gap-2">
            <View className="flex-1">
              <Text className="text-base font-bold text-ink dark:text-d-ink">{campaign?.name}</Text>
              <Text className="text-xs text-muted dark:text-d-muted mt-1 font-mono">
                Template: {campaign?.template?.name || 'Unknown'}
              </Text>
            </View>
            <View className="px-2.5 py-0.5 rounded-full bg-surface2 dark:bg-d-surface2">
              <Text style={{ color: statusColor }} className="text-[11px] font-bold uppercase tracking-wider">
                {status}
              </Text>
            </View>
          </View>
          <View className="flex-row items-center gap-1.5 mt-3 pt-3 border-t border-hairline dark:border-d-hairline">
            <Calendar size={13} color={tokens.muted} />
            <Text className="text-xs text-muted dark:text-d-muted">
              Scheduled / Created: {campaign?.scheduled_at ? new Date(campaign.scheduled_at).toLocaleString() : new Date(campaign?.created_at).toLocaleString()}
            </Text>
          </View>
        </Card>

        {/* Campaign Metrics */}
        <SectionLabel>Performance Metrics</SectionLabel>
        <View className="flex-row flex-wrap gap-3">
          <View className="flex-1 min-w-[140px]">
            <Card pad={12}>
              <Users size={16} color={tokens.muted} />
              <Text className="text-[11px] text-muted dark:text-d-muted mt-1.5 font-bold uppercase tracking-wide">Recipients</Text>
              <Text className="text-lg font-bold text-ink dark:text-d-ink mt-0.5">{campaign?.total_contacts || 0}</Text>
            </Card>
          </View>
          <View className="flex-1 min-w-[140px]">
            <Card pad={12}>
              <Info size={16} color={tokens.muted} />
              <Text className="text-[11px] text-muted dark:text-d-muted mt-1.5 font-bold uppercase tracking-wide">Sent</Text>
              <Text className="text-lg font-bold text-ink dark:text-d-ink mt-0.5">{campaign?.sent_count || 0}</Text>
            </Card>
          </View>
        </View>

        <View className="flex-row flex-wrap gap-3">
          <View className="flex-1 min-w-[140px]">
            <Card pad={12}>
              <CheckCheck size={16} color={tokens.ok} />
              <Text className="text-lg font-bold text-ink dark:text-d-ink mt-1">{campaign?.del_count || 0}</Text>
              <Text className="text-[11px] text-muted dark:text-d-muted font-bold uppercase tracking-wide">Delivered</Text>
              <Text className="text-[11px] text-ok mt-0.5 font-semibold">{deliveryRate}% delivery rate</Text>
            </Card>
          </View>
          <View className="flex-1 min-w-[140px]">
            <Card pad={12}>
              <Eye size={16} color={tokens.accent} />
              <Text className="text-lg font-bold text-ink dark:text-d-ink mt-1">{campaign?.read_count || 0}</Text>
              <Text className="text-[11px] text-muted dark:text-d-muted font-bold uppercase tracking-wide">Read</Text>
              <Text className="text-[11px] text-accent mt-0.5 font-semibold">{readRate}% read rate</Text>
            </Card>
          </View>
        </View>

        {/* Template Content */}
        <SectionLabel>Template Content</SectionLabel>
        <Card pad={14}>
          <View className="flex-row items-center gap-2 mb-2">
            <FileText size={14} color={tokens.muted} />
            <Text className="text-xs font-bold text-muted dark:text-d-muted uppercase tracking-wide">Message Preview</Text>
          </View>
          <View className="py-[11px] px-[13px] bg-bubble-out dark:bg-d-bubble-out rounded-md">
            <Text className="text-[13px] text-ink dark:text-d-ink leading-5">
              {templatePreview}
            </Text>
          </View>
        </Card>

        {/* Message Send Log */}
        <SectionLabel>Message Delivery Log</SectionLabel>
        <Card pad={0}>
          {campaign?.messages && campaign.messages.length > 0 ? (
            campaign.messages.map((msg: any, index: number) => {
              const isMsgRead = msg.status === 'read';
              const isMsgDelivered = msg.status === 'delivered';
              const isMsgFailed = msg.status === 'failed' || msg.status === 'error';
              const logStatusColor = isMsgRead ? tokens.accent : isMsgDelivered ? tokens.ok : isMsgFailed ? tokens.danger : tokens.muted;

              return (
                <View
                  key={msg.id || index}
                  className="px-4 py-3.5 border-b border-hairline dark:border-d-hairline last:border-0"
                >
                  <View className="flex-row justify-between items-center">
                    <View className="flex-1 min-w-0 mr-2">
                      <Text className="text-sm font-semibold text-ink dark:text-d-ink" numberOfLines={1}>
                        {msg.contact?.name || 'Unknown Contact'}
                      </Text>
                      <Text className="text-xs text-muted dark:text-d-muted font-mono mt-0.5">
                        {msg.contact?.phone || 'No Phone'}
                      </Text>
                    </View>
                    <View className="items-end">
                      <View className="px-2 py-0.5 rounded-full bg-surface2 dark:bg-d-surface2 flex-row items-center gap-1">
                        <View className="w-1.5 h-1.5 rounded-full" style={{ backgroundColor: logStatusColor }} />
                        <Text className="text-[10px] font-bold uppercase tracking-wider" style={{ color: logStatusColor }}>
                          {msg.status}
                        </Text>
                      </View>
                      <Text className="text-[10.5px] text-muted dark:text-d-muted mt-1">
                        {msg.created_at ? new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : ''}
                      </Text>
                    </View>
                  </View>
                  {msg.error_message && (
                    <View className="flex-row items-center gap-1 mt-2 p-2 bg-danger/10 rounded">
                      <AlertTriangle size={11} color={tokens.danger} />
                      <Text className="text-[11px] text-danger dark:text-d-danger font-medium flex-1">
                        {msg.error_message}
                      </Text>
                    </View>
                  )}
                </View>
              );
            })
          ) : (
            <View className="p-6 items-center justify-center">
              <Text className="text-xs text-muted dark:text-d-muted italic">No delivery logs recorded yet.</Text>
            </View>
          )}
        </Card>
      </ScrollView>

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

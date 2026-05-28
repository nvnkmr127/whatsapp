import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, ScrollView, ActivityIndicator } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation, useRoute, RouteProp } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import type { RootStackParamList } from '@/types';
import { ChevronLeft, Info, Calendar, User, Database } from 'lucide-react-native';

import { useTokens } from '@/theme';
import { IconButton } from '@/components/Button';
import { Card } from '@/components/Card';
import { SectionLabel } from '@/components/SectionLabel';
import { api } from '@/services/api';
import { CustomDialog } from '@/components/Dialog';

type RouteProps = RouteProp<RootStackParamList, 'ActivityDetail'>;

export default function ActivityDetailScreen() {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const nav = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  const route = useRoute<RouteProps>();
  const { activityId } = route.params;

  const [loading, setLoading] = useState(true);
  const [activity, setActivity] = useState<any>(null);

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

  const fetchActivityDetails = useCallback(async () => {
    setLoading(true);
    try {
      const response = await api.get(`/v1/mobile/activities/${activityId}`);
      setActivity(response);
    } catch (err: any) {
      console.warn('Failed to load activity details:', err);
      showDialog('Error', 'Could not load activity log details from server.');
    } finally {
      setLoading(false);
    }
  }, [activityId]);

  useEffect(() => {
    fetchActivityDetails();
  }, [fetchActivityDetails]);

  if (loading && !activity) {
    return (
      <View className="flex-1 bg-bg dark:bg-d-bg items-center justify-center">
        <ActivityIndicator size="large" color={tokens.accent} />
        <Text className="text-xs text-muted dark:text-d-muted mt-2">Loading details...</Text>
      </View>
    );
  }

  const properties = activity?.properties ? (
    typeof activity.properties === 'string' ? JSON.parse(activity.properties) : activity.properties
  ) : null;

  return (
    <View className="flex-1 bg-bg dark:bg-d-bg" style={{ paddingTop: insets.top }}>
      {/* Header */}
      <View className="flex-row items-center gap-[10px] px-3 pb-3 border-b border-hairline dark:border-d-hairline">
        <IconButton icon={ChevronLeft} onPress={() => nav.goBack()} />
        <Text className="text-[17px] font-bold text-ink dark:text-d-ink">Log Details</Text>
      </View>

      <ScrollView contentContainerStyle={{ padding: 18, gap: 16 }}>
        {/* Main description Card */}
        <Card pad={14}>
          <View className="flex-row items-start gap-3">
            <View className="w-9 h-9 rounded-full bg-accent/10 items-center justify-center mt-0.5">
              <Info size={18} color={tokens.accent} />
            </View>
            <View className="flex-1">
              <Text className="text-xs text-muted dark:text-d-muted font-bold uppercase tracking-wider">Event Description</Text>
              <Text className="text-base font-semibold text-ink dark:text-d-ink mt-1 leading-relaxed">
                {activity?.description || 'No description provided.'}
              </Text>
            </View>
          </View>
        </Card>

        {/* Metadata Details */}
        <SectionLabel>Metadata</SectionLabel>
        <Card pad={0}>
          <View className="flex-row items-center gap-3.5 px-4 py-3.5 border-b border-hairline dark:border-d-hairline">
            <User size={16} color={tokens.muted} />
            <View className="flex-1">
              <Text className="text-xs text-muted dark:text-d-muted">Actor (User)</Text>
              <Text className="text-[14px] font-semibold text-ink dark:text-d-ink mt-0.5">
                {activity?.user?.name || 'System / Automated'}
              </Text>
            </View>
          </View>
          
          <View className="flex-row items-center gap-3.5 px-4 py-3.5 border-b border-hairline dark:border-d-hairline">
            <Calendar size={16} color={tokens.muted} />
            <View className="flex-1">
              <Text className="text-xs text-muted dark:text-d-muted">Timestamp</Text>
              <Text className="text-[14px] font-semibold text-ink dark:text-d-ink mt-0.5">
                {activity?.created_at ? new Date(activity.created_at).toLocaleString() : 'N/A'}
              </Text>
            </View>
          </View>

          {activity?.subject_type && (
            <View className="flex-row items-center gap-3.5 px-4 py-3.5">
              <Database size={16} color={tokens.muted} />
              <View className="flex-1">
                <Text className="text-xs text-muted dark:text-d-muted">Subject / Target Object</Text>
                <Text className="text-[14px] font-semibold text-ink dark:text-d-ink mt-0.5">
                  {activity.subject_type.split('\\').pop()} (#{activity.subject_id})
                </Text>
              </View>
            </View>
          )}
        </Card>

        {/* Event Properties */}
        {properties && Object.keys(properties).length > 0 && (
          <>
            <SectionLabel>Details / Attributes</SectionLabel>
            <Card pad={14}>
              {Object.entries(properties).map(([key, val]) => (
                <View key={key} className="mb-3.5 last:mb-0">
                  <Text className="text-xs text-muted dark:text-d-muted font-bold capitalize">{key.replace(/_/g, ' ')}</Text>
                  <Text className="text-[13.5px] font-medium text-ink dark:text-d-ink mt-1">
                    {typeof val === 'object' ? JSON.stringify(val, null, 2) : String(val)}
                  </Text>
                </View>
              ))}
            </Card>
          </>
        )}
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

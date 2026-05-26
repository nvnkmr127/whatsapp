// src/screens/AnalyticsScreen.tsx — KPI cards, funnel, top templates.

import React, { useState, useMemo } from 'react';
import { View, Text, ScrollView } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { CalendarRange } from 'lucide-react-native';
import { useNavigation } from '@react-navigation/native';

import { useTokens } from '@/theme';
import { KPIS, FUNNEL, TOP_TEMPLATES } from '@/data';
import { Chip } from '@/components/Chip';
import { Card } from '@/components/Card';
import { SectionLabel } from '@/components/SectionLabel';
import { Spark } from '@/components/Spark';
import { IconButton } from '@/components/Button';
import { CustomDialog } from '@/components/Dialog';

const RANGES = ['7d', '30d', '90d'] as const;
type Range = typeof RANGES[number];

const RANGE_LABEL: Record<Range, string> = {
  '7d': '7 days',
  '30d': '30 days',
  '90d': '90 days',
};

const getKpisForRange = (range: Range) => {
  const factor = range === '7d' ? 0.23 : range === '90d' ? 3.12 : 1;
  return KPIS.map((k) => {
    if (k.label === 'Conversations') {
      const val = Math.round(1284 * factor);
      return { ...k, value: val.toLocaleString() };
    }
    if (k.label === 'Messages sent') {
      const val = (32.8 * factor).toFixed(1);
      return { ...k, value: `${val}k` };
    }
    if (k.label === 'Avg response') {
      const sec = range === '7d' ? '2m 53s' : range === '90d' ? '2m 34s' : '2m 41s';
      return { ...k, value: sec };
    }
    if (k.label === 'Resolution rate') {
      const pct = range === '7d' ? '92%' : range === '90d' ? '95%' : '94%';
      return { ...k, value: pct };
    }
    return k;
  });
};

const getFunnelForRange = (range: Range) => {
  const factor = range === '7d' ? 0.23 : range === '90d' ? 3.12 : 1;
  return FUNNEL.map((f) => ({
    ...f,
    n: Math.round(f.n * factor),
  }));
};

const getTopTemplatesForRange = (range: Range) => {
  const factor = range === '7d' ? 0.23 : range === '90d' ? 3.12 : 1;
  return TOP_TEMPLATES.map((t) => ({
    ...t,
    uses: Math.round(t.uses * factor),
  }));
};

export default function AnalyticsScreen() {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const nav = useNavigation<any>();
  const [range, setRange] = useState<Range>('30d');

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

  const kpis = useMemo(() => getKpisForRange(range), [range]);
  const funnel = useMemo(() => getFunnelForRange(range), [range]);
  const topTemplates = useMemo(() => getTopTemplatesForRange(range), [range]);

  return (
    <View className="flex-1 bg-bg dark:bg-d-bg" style={{ paddingTop: insets.top }}>
      {/* Header */}
      <View className="flex-row items-end justify-between px-[18px] pt-3.5 pb-2">
        <View>
          <Text className="text-2xl font-bold tracking-[-0.3px] text-ink dark:text-d-ink">
            Analytics
          </Text>
          <Text className="text-[12px] text-muted dark:text-d-muted mt-0.5">
            Last {RANGE_LABEL[range]}
          </Text>
        </View>
        <IconButton
          icon={CalendarRange}
          onPress={() => {
            showDialog(
              'Select Date Range',
              'Choose reporting date interval:',
              [
                { text: 'Last 7 days', onPress: () => setRange('7d') },
                { text: 'Last 30 days', onPress: () => setRange('30d') },
                { text: 'Last 90 days', onPress: () => setRange('90d') },
                { text: 'Cancel', style: 'cancel' }
              ]
            );
          }}
        />
      </View>

      <View className="flex-row gap-1.25 px-3.5 py-1.5">
        {RANGES.map((r) => (
          <Chip key={r} label={RANGE_LABEL[r]} active={range === r} onPress={() => setRange(r)} />
        ))}
      </View>

      <ScrollView contentContainerStyle={{ paddingHorizontal: 18, paddingBottom: 100 }}>
        {/* KPI grid */}
        <View className="flex-row flex-wrap gap-2">
          {kpis.map((k) => (
            <View
              key={k.label}
              className="flex-grow flex-shrink basis-[48%] bg-surface dark:bg-d-surface rounded-lg p-3.5 gap-1.5"
            >
              <Text className="text-[11.5px] font-medium text-muted dark:text-d-muted">
                {k.label}
              </Text>
              <Text className="text-[22px] font-bold tracking-[-0.4px] text-ink dark:text-d-ink">
                {k.value}
              </Text>
              <View className="flex-row items-center justify-between">
                <Text
                  className={`text-[11.5px] font-medium ${
                    k.up ? 'text-ok dark:text-d-ok' : 'text-danger dark:text-d-danger'
                  }`}
                >
                  {k.up ? '↑' : '↓'} {k.delta.replace(/^[+−-]/, '')}
                </Text>
                <Spark data={k.trend} width={60} height={20} color={k.up ? tokens.ok : tokens.danger} fill={k.up ? tokens.ok : tokens.danger} />
              </View>
            </View>
          ))}
        </View>

        <SectionLabel>Conversation funnel</SectionLabel>
        <Card pad={14}>
          <View className="gap-2.5">
            {funnel.map((f, i) => (
              <View key={f.label}>
                <View className="flex-row justify-between mb-1">
                  <Text className="text-xs font-semibold text-ink dark:text-d-ink">{f.label}</Text>
                  <Text
                    className="text-xs text-muted dark:text-d-muted"
                    style={{ fontVariant: ['tabular-nums'] }}
                  >
                    <Text className="font-bold text-ink dark:text-d-ink">{f.n.toLocaleString()}</Text>
                    {' · '}{f.pct}%
                  </Text>
                </View>
                <View className="h-1.5 rounded-full bg-surface2 dark:bg-d-surface2 overflow-hidden">
                  <View
                    className="h-full bg-accent dark:bg-d-accent rounded-full"
                    style={{
                      width: `${f.pct}%`,
                      opacity: 1 - i * 0.12,
                    }}
                  />
                </View>
              </View>
            ))}
          </View>
        </Card>

        <SectionLabel action="See all" onActionPress={() => nav.navigate('Templates')}>Top templates</SectionLabel>

        <Card pad={0}>
          {topTemplates.map((tp, i, arr) => (
            <View
              key={tp.name}
              className={`flex-row items-center gap-3 px-4 py-3.5 ${
                i < arr.length - 1 ? 'border-b border-hairline dark:border-d-hairline' : ''
              }`}
            >
              <Text
                className="text-xs text-muted dark:text-d-muted w-3.5"
                style={{ fontVariant: ['tabular-nums'] }}
              >
                {i + 1}
              </Text>
              <View className="flex-1">
                <Text
                  numberOfLines={1}
                  className="text-[13px] font-medium text-ink dark:text-d-ink font-mono"
                >
                  {tp.name}
                </Text>
                <Text className="text-[11px] text-muted dark:text-d-muted mt-0.5">
                  {tp.uses.toLocaleString()} sends
                </Text>
              </View>
              <View className="items-end">
                <Text className="text-sm font-semibold text-ink dark:text-d-ink">{tp.ctr}%</Text>
                <Text className="text-[10.5px] text-muted dark:text-d-muted">CTR</Text>
              </View>
            </View>
          ))}
        </Card>
      </ScrollView>

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

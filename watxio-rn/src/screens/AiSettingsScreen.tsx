import React, { useState, useEffect } from 'react';
import { View, Text, ScrollView, TextInput, Pressable, ActivityIndicator } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import type { RootStackParamList } from '@/types';
import { ChevronLeft, Sparkles, Cpu, Key, Sliders, ShieldCheck, Check } from 'lucide-react-native';

import { useTokens } from '@/theme';
import { IconButton } from '@/components/Button';
import { Card } from '@/components/Card';
import { Toggle } from '@/components/Toggle';
import { SectionLabel } from '@/components/SectionLabel';
import { ListSkeleton } from '@/components/ListItemSkeleton';
import { api } from '@/services/api';
import { CustomDialog } from '@/components/Dialog';

export default function AiSettingsScreen() {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const nav = useNavigation<NativeStackNavigationProp<RootStackParamList>>();

  const [loading, setLoading] = useState(true);
  const [saveLoading, setSaveLoading] = useState(false);

  // AI Configuration States
  const [enabled, setEnabled] = useState(false);
  const [provider, setProvider] = useState('openai');
  const [apiKey, setApiKey] = useState('');
  const [model, setModel] = useState('gpt-4o');
  const [persona, setPersona] = useState('');
  const [useKb, setUseKb] = useState(false);
  const [kbStrict, setKbStrict] = useState(false);
  const [confidenceThreshold, setConfidenceThreshold] = useState('0.7');
  const [operatingHoursOnly, setOperatingHoursOnly] = useState(false);

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

  // Fetch current settings
  useEffect(() => {
    async function loadSettings() {
      setLoading(true);
      try {
        const data = await api.get('/v1/mobile/ai/settings');
        setEnabled(!!data.enabled);
        setProvider(data.provider || 'openai');
        setApiKey(data.api_key || '');
        setModel(data.model || 'gpt-4o');
        setPersona(data.persona || '');
        setUseKb(!!data.use_kb);
        setKbStrict(!!data.kb_strict);
        setConfidenceThreshold(String(data.confidence_threshold ?? '0.7'));
        setOperatingHoursOnly(!!data.operating_hours_only);
      } catch (err: any) {
        console.warn('Failed to fetch AI settings:', err);
        showDialog('Error', 'Could not load AI Assistant settings from server.');
      } finally {
        setLoading(false);
      }
    }

    loadSettings();
  }, []);

  // Save changes
  const handleSave = async () => {
    const thresh = parseFloat(confidenceThreshold);
    if (isNaN(thresh) || thresh < 0 || thresh > 1) {
      showDialog('Validation Error', 'Confidence threshold must be a decimal number between 0.0 and 1.0.');
      return;
    }

    setSaveLoading(true);
    try {
      await api.post('/v1/mobile/ai/settings', {
        enabled,
        provider,
        api_key: apiKey.trim(),
        model: model.trim(),
        persona: persona.trim(),
        use_kb: useKb,
        kb_strict: kbStrict,
        confidence_threshold: thresh,
        operating_hours_only: operatingHoursOnly,
      });

      showDialog('Success', 'AI Assistant settings saved successfully!', [
        {
          text: 'OK',
          onPress: () => {
            nav.goBack();
          }
        }
      ]);
    } catch (err: any) {
      showDialog('Error Saving', err.message || 'Could not save AI Assistant settings.');
    } finally {
      setSaveLoading(false);
    }
  };

  return (
    <View className="flex-1 bg-bg dark:bg-d-bg" style={{ paddingTop: insets.top }}>
      {/* Header */}
      <View className="flex-row items-center gap-[10px] px-3 pb-3 border-b border-hairline dark:border-d-hairline">
        <IconButton icon={ChevronLeft} onPress={() => nav.goBack()} />
        <Text className="text-[17px] font-bold text-ink dark:text-d-ink flex-1">AI Assistant Settings</Text>
      </View>

      {loading ? (
        <View className="flex-1 mt-4">
          <ListSkeleton count={6} />
        </View>
      ) : (
        <>
          <ScrollView contentContainerStyle={{ paddingHorizontal: 16, paddingBottom: 120, paddingTop: 10 }}>
            {/* Global toggle */}
            <Card pad={14} className="mb-4">
              <View className="flex-row items-center justify-between">
                <View className="flex-1 mr-4">
                  <View className="flex-row items-center gap-1.5">
                    <Sparkles size={16} color={enabled ? tokens.accent : tokens.muted} />
                    <Text className="text-sm font-bold text-ink dark:text-d-ink">Enable Auto Reply</Text>
                  </View>
                  <Text className="text-[11.5px] text-muted dark:text-d-muted mt-1 leading-normal">
                    Let the AI assistant reply to incoming customer queries automatically.
                  </Text>
                </View>
                <Toggle on={enabled} onChange={setEnabled} />
              </View>
            </Card>

            <SectionLabel>Provider Configuration</SectionLabel>
            <Card pad={14} className="mb-4 gap-3.5">
              {/* Provider Selection */}
              <View>
                <Text className="text-xs font-semibold text-muted dark:text-d-muted mb-2 uppercase tracking-wide">
                  Model Provider
                </Text>
                <View className="flex-row gap-2">
                  {([
                    { id: 'openai', name: 'OpenAI' },
                    { id: 'gemini', name: 'Gemini' },
                    { id: 'anthropic', name: 'Claude' },
                  ] as const).map((p) => {
                    const isSelected = provider === p.id;
                    return (
                      <Pressable
                        key={p.id}
                        onPress={() => setProvider(p.id)}
                        className={`flex-1 py-2.5 rounded-lg items-center border flex-row justify-center gap-1.5 ${
                          isSelected
                            ? 'bg-accent/10 border-accent'
                            : 'bg-surface2 dark:bg-d-surface2 border-transparent'
                        }`}
                      >
                        {isSelected && <Check size={12} color={tokens.accent} strokeWidth={3} />}
                        <Text className={`text-[12px] font-bold ${isSelected ? 'text-accent' : 'text-ink2 dark:text-d-ink2'}`}>
                          {p.name}
                        </Text>
                      </Pressable>
                    );
                  })}
                </View>
              </View>

              {/* API Key */}
              <View>
                <Text className="text-xs font-semibold text-muted dark:text-d-muted mb-1.5 uppercase tracking-wide">
                  API Key
                </Text>
                <TextInput
                  value={apiKey}
                  onChangeText={setApiKey}
                  placeholder="sk-..."
                  placeholderTextColor={tokens.faint}
                  secureTextEntry
                  autoCapitalize="none"
                  className="bg-surface2 dark:bg-d-surface2 p-3 text-sm rounded-lg text-ink dark:text-d-ink font-medium font-mono"
                />
              </View>

              {/* Model */}
              <View>
                <Text className="text-xs font-semibold text-muted dark:text-d-muted mb-1.5 uppercase tracking-wide">
                  AI Model
                </Text>
                <TextInput
                  value={model}
                  onChangeText={setModel}
                  placeholder="e.g. gpt-4o"
                  placeholderTextColor={tokens.faint}
                  autoCapitalize="none"
                  className="bg-surface2 dark:bg-d-surface2 p-3 text-sm rounded-lg text-ink dark:text-d-ink font-medium font-mono"
                />
              </View>
            </Card>

            <SectionLabel>Guardrails & KB Settings</SectionLabel>
            <Card pad={0} className="mb-4">
              {/* Use Knowledge Base */}
              <View className="flex-row items-center justify-between p-3.5 border-b border-hairline dark:border-d-hairline">
                <View className="flex-1 mr-4">
                  <Text className="text-xs font-bold text-ink dark:text-d-ink">Use Knowledge Base</Text>
                  <Text className="text-[10px] text-muted dark:text-d-muted mt-0.5 leading-normal">
                    Search and ground AI answers in uploaded team documents.
                  </Text>
                </View>
                <Toggle on={useKb} onChange={setUseKb} />
              </View>

              {/* KB Strict Mode */}
              <View className="flex-row items-center justify-between p-3.5 border-b border-hairline dark:border-d-hairline">
                <View className="flex-1 mr-4">
                  <Text className="text-xs font-bold text-ink dark:text-d-ink">Strict KB Mode</Text>
                  <Text className="text-[10px] text-muted dark:text-d-muted mt-0.5 leading-normal">
                    Only answer if the context contains explicit details; refuse otherwise.
                  </Text>
                </View>
                <Toggle on={kbStrict} onChange={setKbStrict} />
              </View>

              {/* Operating Hours Only */}
              <View className="flex-row items-center justify-between p-3.5 border-b border-hairline dark:border-d-hairline">
                <View className="flex-1 mr-4">
                  <Text className="text-xs font-bold text-ink dark:text-d-ink">Operating Hours Only</Text>
                  <Text className="text-[10px] text-muted dark:text-d-muted mt-0.5 leading-normal">
                    Enforce AI auto-responder actions only within defined operating schedules.
                  </Text>
                </View>
                <Toggle on={operatingHoursOnly} onChange={setOperatingHoursOnly} />
              </View>

              {/* Confidence Threshold */}
              <View className="p-3.5">
                <Text className="text-xs font-bold text-ink dark:text-d-ink">Confidence Threshold</Text>
                <Text className="text-[10px] text-muted dark:text-d-muted mt-0.5 leading-normal">
                  Minimum AI score requirement to auto-reply (0.0 to 1.0).
                </Text>
                <TextInput
                  value={confidenceThreshold}
                  onChangeText={setConfidenceThreshold}
                  placeholder="0.7"
                  placeholderTextColor={tokens.faint}
                  keyboardType="numeric"
                  className="bg-surface2 dark:bg-d-surface2 p-2.5 text-sm rounded-lg text-ink dark:text-d-ink font-medium mt-2 max-w-[80px]"
                />
              </View>
            </Card>

            <SectionLabel>Assistant Prompt Instructions</SectionLabel>
            <Card pad={14} className="mb-4">
              <Text className="text-xs font-semibold text-muted dark:text-d-muted mb-1.5 uppercase tracking-wide">
                Persona & Instructions (System Prompt)
              </Text>
              <TextInput
                value={persona}
                onChangeText={setPersona}
                placeholder="e.g. You are a helpful and polite customer support assistant representing Watxio..."
                placeholderTextColor={tokens.faint}
                multiline
                className="bg-surface2 dark:bg-d-surface2 p-3 text-sm rounded-lg text-ink dark:text-d-ink font-medium min-h-[100px] leading-relaxed"
              />
            </Card>
          </ScrollView>

          {/* Sticky CTA */}
          <View
            className="absolute left-0 right-0 px-4"
            style={{ bottom: insets.bottom + 12 }}
          >
            <Pressable
              onPress={handleSave}
              disabled={saveLoading}
              className="bg-accent dark:bg-d-accent py-3.5 rounded-xl items-center active:opacity-90 flex-row justify-center gap-1.5 shadow-lg shadow-accent/20"
            >
              {saveLoading ? (
                <ActivityIndicator size="small" color={tokens.accentInk} />
              ) : (
                <Text className="text-accent-ink dark:text-d-accent-ink font-bold text-sm">Save AI Settings</Text>
              )}
            </Pressable>
          </View>
        </>
      )}

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

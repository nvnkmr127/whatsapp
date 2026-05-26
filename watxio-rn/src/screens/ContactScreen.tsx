import React, { useState } from 'react';
import { View, Text, ScrollView, Pressable, TextInput, Modal } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import {
  ChevronLeft, MoreHorizontal,
  Send, Phone, BellOff, Archive, Globe, Inbox, User, CreditCard, MessageSquare, FilePen, Bell,
} from 'lucide-react-native';

import { useTokens } from '@/theme';
import { CONTACT_PROFILE } from '@/data';
import { Avatar } from '@/components/Avatar';
import { IconButton } from '@/components/Button';
import { Card } from '@/components/Card';
import { SectionLabel } from '@/components/SectionLabel';
import { CustomDialog } from '@/components/Dialog';

export default function ContactScreen() {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const nav = useNavigation();
  const p = CONTACT_PROFILE;

  const [notes, setNotes] = useState(p.notes);
  const [history, setHistory] = useState(p.history);
  const [isMuted, setIsMuted] = useState(false);
  const [isArchived, setIsArchived] = useState(false);
  const [isAddingNote, setIsAddingNote] = useState(false);
  const [newNote, setNewNote] = useState('');
  const [isEditingInfo, setIsEditingInfo] = useState(false);
  const [email, setEmail] = useState(p.email);
  const [company, setCompany] = useState(p.company);

  // Calling States
  const [isCalling, setIsCalling] = useState(false);
  const [callTime, setCallTime] = useState(0);

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


  React.useEffect(() => {
    let interval: NodeJS.Timeout;
    if (isCalling) {
      setCallTime(0);
      interval = setInterval(() => {
        setCallTime((t) => t + 1);
      }, 1000);
    }
    return () => clearInterval(interval);
  }, [isCalling]);

  const handleAddNote = () => {
    if (!newNote.trim()) return;
    setNotes((prev) => `${newNote.trim()}\n\n${prev}`);
    setHistory((prev) => [
      {
        type: 'note',
        text: `Note added: "${newNote.trim()}"`,
        time: 'Just now',
      },
      ...prev,
    ]);
    setNewNote('');
    setIsAddingNote(false);
  };

  return (
    <View className="flex-1 bg-bg dark:bg-d-bg" style={{ paddingTop: insets.top }}>
      {/* Header */}
      <View className="flex-row items-center justify-between px-3 py-2">
        <IconButton icon={ChevronLeft} onPress={() => nav.goBack()} />
        <Text className="text-muted dark:text-d-muted text-[13px] font-semibold">Contact</Text>
        <IconButton
          icon={MoreHorizontal}
          onPress={() => {
            showDialog(
              'Contact Actions',
              'Select an action for this contact:',
              [
                { text: 'Block Contact', style: 'destructive', onPress: () => showDialog('Blocked', 'Contact has been blocked.') },
                { text: 'Delete Contact', style: 'destructive', onPress: () => showDialog('Deleted', 'Contact has been deleted.') },
                { text: 'Cancel', style: 'cancel' }
              ]
            );
          }}
        />
      </View>

      <ScrollView contentContainerStyle={{ paddingBottom: 32 }}>
        {/* Hero */}
        <View className="items-center px-5 py-5 gap-2">
          <Avatar name={p.name} size={88} ring={tokens.accent} dot={tokens.ok} />
          <Text className="mt-1.5 text-[22px] font-bold text-ink dark:text-d-ink">{p.name}</Text>
          <Text className="text-[13px] text-muted dark:text-d-muted">{p.phone}</Text>
          <View className="flex-row gap-1.5 flex-wrap justify-center mt-1">
            {p.tags.map((tag) => (
              <View
                key={tag}
                className="px-2.5 py-[3px] rounded-full bg-surface2 dark:bg-d-surface2"
              >
                <Text className="text-ink2 dark:text-d-ink2 text-[11px] font-medium">{tag}</Text>
              </View>
            ))}
          </View>
        </View>

        {/* Quick actions */}
        <View className="flex-row gap-2 px-[18px] pb-4">
          {[
            {
              Icon: Send,
              label: 'Message',
              onPress: () => nav.goBack(),
              active: false,
            },
            {
              Icon: Phone,
              label: 'Call',
              onPress: () => setIsCalling(true),
              active: false,
            },
            {
              Icon: isMuted ? Bell : BellOff,
              label: isMuted ? 'Unmute' : 'Mute',
              onPress: () => {
                const nextMuted = !isMuted;
                setIsMuted(nextMuted);
                showDialog(nextMuted ? 'Muted' : 'Unmuted', `Conversations with ${p.name} have been ${nextMuted ? 'muted' : 'unmuted'}.`);
              },
              active: isMuted,
            },
            {
              Icon: Archive,
              label: isArchived ? 'Unarchive' : 'Archive',
              onPress: () => {
                const nextArchived = !isArchived;
                setIsArchived(nextArchived);
                showDialog(nextArchived ? 'Archived' : 'Unarchived', `Conversations with ${p.name} have been ${nextArchived ? 'archived' : 'unarchived'}.`);
              },
              active: isArchived,
            },
          ].map(({ Icon, label, onPress, active }) => (
            <Pressable
              key={label}
              onPress={onPress}
              className={`flex-1 py-3 rounded-md items-center gap-1.5 active:opacity-80 ${
                active
                  ? 'bg-accentSoft dark:bg-d-accentSoft border border-accent/20'
                  : 'bg-surface dark:bg-d-surface'
              }`}
            >
              <Icon size={18} color={active ? tokens.accent : tokens.ink} strokeWidth={1.6} />
              <Text
                className={`text-[11.5px] font-medium ${
                  active ? 'text-accent dark:text-d-accent font-semibold' : 'text-ink2 dark:text-d-ink2'
                }`}
              >
                {label}
              </Text>
            </Pressable>
          ))}
        </View>


        {/* Stats card */}
        <View className="px-[18px] pb-3.5">
          <View className="flex-row bg-surface dark:bg-d-surface rounded-lg overflow-hidden">
            {[
              { l: 'Orders', v: p.stats.orders },
              { l: 'LTV',    v: p.stats.ltv },
              { l: 'Since',  v: p.stats.firstSeen },
            ].map((s, i) => (
              <View
                key={s.l}
                className={`flex-1 py-4 items-center ${
                  i < 2 ? 'border-r border-hairline dark:border-d-hairline' : ''
                }`}
              >
                <Text className="text-[18px] font-bold text-ink dark:text-d-ink tracking-[-0.3px]">
                  {s.v}
                </Text>
                <Text className="text-[11px] text-muted dark:text-d-muted mt-0.5">{s.l}</Text>
              </View>
            ))}
          </View>
        </View>

        <SectionLabel
          action={isEditingInfo ? "Save" : "Edit"}
          onActionPress={() => {
            if (isEditingInfo) {
              setIsEditingInfo(false);
              showDialog('Info Saved', 'Customer info has been successfully updated.');
            } else {
              setIsEditingInfo(true);
            }
          }}
        >
          Customer info
        </SectionLabel>
        <View className="px-[18px]">
          <Card pad={0}>
            {[
              { l: 'Email',   v: email,   Icon: Globe,    set: setEmail },
              { l: 'Company', v: company, Icon: Inbox,    set: setCompany },
              { l: 'Owner',   v: 'You',     Icon: User },
            ].map((r, i, arr) => (
              <View
                key={r.l}
                className={`flex-row items-center gap-3 px-3.5 py-3 ${
                  i < arr.length - 1 ? 'border-b border-hairline dark:border-d-hairline' : ''
                }`}
              >
                <r.Icon size={16} color={tokens.muted} strokeWidth={1.6} />
                <View className="flex-1">
                  <Text className="text-[11px] text-muted dark:text-d-muted font-medium">{r.l}</Text>
                  {isEditingInfo && r.set ? (
                    <TextInput
                      value={r.v}
                      onChangeText={r.set}
                      className="text-[13.5px] text-ink dark:text-d-ink font-medium p-0"
                    />
                  ) : (
                    <Text className="text-[13.5px] text-ink dark:text-d-ink font-medium">{r.v}</Text>
                  )}
                </View>
              </View>
            ))}
          </Card>
        </View>

        <SectionLabel action="+ Add" onActionPress={() => setIsAddingNote(true)}>Notes</SectionLabel>
        <View className="px-[18px]">
          {isAddingNote && (
            <View className="bg-surface dark:bg-d-surface rounded-lg p-3.5 mb-3 gap-2 border border-hairline dark:border-d-hairline">
              <TextInput
                value={newNote}
                onChangeText={setNewNote}
                placeholder="Type your note here..."
                placeholderTextColor={tokens.muted}
                className="text-ink dark:text-d-ink text-sm bg-surface2 dark:bg-d-surface2 p-2.5 rounded-md min-h-[60px]"
                multiline
                autoFocus
              />
              <View className="flex-row justify-end gap-2">
                <Pressable
                  onPress={() => {
                    setIsAddingNote(false);
                    setNewNote('');
                  }}
                  className="px-3 py-1.5 rounded-md bg-surface2 dark:bg-d-surface2 active:opacity-80"
                >
                  <Text className="text-muted dark:text-d-muted text-xs font-semibold">Cancel</Text>
                </Pressable>
                <Pressable
                  onPress={handleAddNote}
                  className="px-3 py-1.5 rounded-md bg-accent dark:bg-d-accent active:opacity-85"
                >
                  <Text className="text-accent-ink dark:text-d-accent-ink text-xs font-semibold">Save</Text>
                </Pressable>
              </View>
            </View>
          )}
          <View className="p-4 rounded-lg bg-surface dark:bg-d-surface">
            <Text className="text-ink2 dark:text-d-ink2 text-[13.5px] leading-5">{notes}</Text>
            <Text className="text-[11px] text-muted dark:text-d-muted mt-2">You · Just now</Text>
          </View>
        </View>

        <SectionLabel>Activity</SectionLabel>
        <View className="px-[18px]">
          {history.map((h, i) => {
            const Icon =
              h.type === 'order' ? CreditCard :
              h.type === 'message' ? MessageSquare :
              h.type === 'note' ? FilePen : Bell;
            return (
              <View key={i} className="flex-row gap-3 py-3">
                <View className="w-7 h-7 rounded-full bg-surface2 dark:bg-d-surface2 items-center justify-center">
                  <Icon size={13} color={tokens.ink2} strokeWidth={1.8} />
                </View>
                <View className="flex-1">
                  <Text className="text-[13.5px] text-ink dark:text-d-ink font-medium">{h.text}</Text>
                  <Text className="text-[11.5px] text-muted dark:text-d-muted mt-0.5">{h.time}</Text>
                </View>
              </View>
            );
          })}
        </View>
      </ScrollView>

      {/* Simulated Calling Overlay */}
      {isCalling && (
        <Modal transparent visible={isCalling} animationType="slide">
          <View className="flex-1 bg-ink/95 dark:bg-black/95 items-center justify-center px-6">
            <View className="items-center gap-6">
              <Avatar name={p.name} size={110} ring={tokens.ok} />
              <View className="items-center">
                <Text className="text-white text-2xl font-bold mt-2">{p.name}</Text>
                <Text className="text-ok text-sm font-semibold tracking-wide mt-1 uppercase">
                  Calling via WhatsApp API...
                </Text>
                <Text className="text-white/60 text-sm mt-1 font-mono">
                  {Math.floor(callTime / 60)}:{(callTime % 60).toString().padStart(2, '0')}
                </Text>
              </View>
              <View className="flex-row items-center justify-center w-24 h-24 bg-ok/10 rounded-full mt-4">
                <View className="w-16 h-16 bg-ok/20 rounded-full items-center justify-center">
                  <Phone size={32} color="#FFFFFF" strokeWidth={1.8} />
                </View>
              </View>
              <Pressable
                onPress={() => setIsCalling(false)}
                className="w-16 h-16 bg-danger rounded-full items-center justify-center mt-12 active:opacity-90"
              >
                <Phone size={24} color="#FFFFFF" strokeWidth={1.8} style={{ transform: [{ rotate: '135deg' }] }} />
              </Pressable>
            </View>
          </View>
        </Modal>
      )}
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


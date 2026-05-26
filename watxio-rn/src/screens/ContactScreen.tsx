import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, ScrollView, Pressable, TextInput, Modal, ActivityIndicator } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation, useRoute, RouteProp } from '@react-navigation/native';
import {
  ChevronLeft, MoreHorizontal,
  Send, Phone, BellOff, Archive, Globe, Inbox, User, CreditCard, MessageSquare, FilePen, Bell,
} from 'lucide-react-native';

import { useTokens } from '@/theme';
import type { RootStackParamList } from '@/types';
import { Avatar } from '@/components/Avatar';
import { IconButton } from '@/components/Button';
import { Card } from '@/components/Card';
import { SectionLabel } from '@/components/SectionLabel';
import { CustomDialog } from '@/components/Dialog';
import { api } from '@/services/api';

export default function ContactScreen({ navigation, route }: any) {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const nav = navigation;
  const { conversationId, contactId } = route.params;

  const [loading, setLoading] = useState(true);
  const [contactData, setContactData] = useState<any>(null);
  const [notesList, setNotesList] = useState<any[]>([]);

  // Local editable states
  const [contactName, setContactName] = useState('');
  const [contactEmail, setContactEmail] = useState('');
  const [contactCompany, setContactCompany] = useState('');
  
  const [isMuted, setIsMuted] = useState(false);
  const [isArchived, setIsArchived] = useState(false);
  const [isAddingNote, setIsAddingNote] = useState(false);
  const [newNote, setNewNote] = useState('');
  const [isEditingInfo, setIsEditingInfo] = useState(false);

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

  const fetchContactDetails = useCallback(async () => {
    setLoading(true);
    try {
      const response = await api.get(`/v1/mobile/contacts/${contactId}`);
      const data = response.contact || {};
      setContactData(data);
      setContactName(data.name || '');

      // Parse custom attributes/schema
      const customAttrs = data.custom_attributes || {};
      setContactEmail(customAttrs.email || data.email || '');
      setContactCompany(customAttrs.company || data.company || '');

      // Fetch notes
      const notesResponse = await api.get(`/v1/mobile/conversations/${conversationId}/notes`);
      setNotesList(notesResponse || []);
    } catch (err: any) {
      console.warn('Failed to load contact data:', err);
      showDialog('Error', 'Could not load contact details from server.');
    } finally {
      setLoading(false);
    }
  }, [contactId, conversationId]);

  useEffect(() => {
    fetchContactDetails();
  }, [fetchContactDetails]);

  // Call timer effect
  useEffect(() => {
    let interval: NodeJS.Timeout;
    if (isCalling) {
      setCallTime(0);
      interval = setInterval(() => {
        setCallTime((t) => t + 1);
      }, 1000);
    }
    return () => clearInterval(interval);
  }, [isCalling]);

  const handleUpdateContact = async () => {
    setLoading(true);
    try {
      await api.put(`/v1/mobile/contacts/${contactId}`, {
        name: contactName,
        custom_attributes: {
          email: contactEmail,
          company: contactCompany,
        },
      });
      setIsEditingInfo(false);
      await fetchContactDetails();
      showDialog('Profile Updated', 'Contact information saved successfully.');
    } catch (err: any) {
      showDialog('Failed to Update', err.message || 'Error occurred while saving changes.');
    } finally {
      setLoading(false);
    }
  };

  const handleAddNote = async () => {
    if (!newNote.trim()) return;
    setLoading(true);
    try {
      await api.post(`/v1/mobile/conversations/${conversationId}/notes`, {
        content: newNote.trim(),
      });
      setNewNote('');
      setIsAddingNote(false);
      await fetchContactDetails();
      showDialog('Note Logged', 'Internal note added to conversation history.');
    } catch (err: any) {
      showDialog('Failed to Add Note', err.message || 'Could not save note.');
    } finally {
      setLoading(false);
    }
  };

  const handleInitiateCall = async () => {
    setIsCalling(true);
    try {
      await api.post('/v1/calls/initiate', {
        phone_number: contactData?.phone_number || contactName,
      });
    } catch (err: any) {
      console.warn('Call setup failed:', err);
      setIsCalling(false);

      const isPermissionErr =
        err?.code === 'NO_APPROVED_CALL_PERMISSION' ||
        err?.data?.code === 'NO_APPROVED_CALL_PERMISSION' ||
        err?.message?.includes('No approved call permission') ||
        err?.data?.message?.includes('No approved call permission') ||
        err?.data?.errors?.error?.code === 138006;

      if (isPermissionErr) {
        showDialog(
          'Call Permission Required',
          'WhatsApp requires permission from the recipient before a business can call them. Would you like to send a Call Permission Request template now?',
          [
            { text: 'Cancel', style: 'cancel' },
            {
              text: 'Send Request',
              onPress: async () => {
                try {
                  setLoading(true);
                  const res = await api.post('/v1/whatsapp/calls/request-permission', {
                    contact_id: contactId,
                  });
                  if (res.success) {
                    showDialog('Request Sent', 'Call permission request template has been sent.');
                  } else {
                    showDialog('Request Failed', res.error || 'Failed to send call permission request.');
                  }
                } catch (requestErr: any) {
                  showDialog('Request Failed', requestErr.message || 'Failed to send call permission request.');
                } finally {
                  setLoading(false);
                }
              }
            }
          ]
        );
      } else {
        showDialog('Call Failed', err.message || 'Could not initiate the call.');
      }
    }
  };

  if (loading && !contactData) {
    return (
      <View className="flex-1 bg-bg dark:bg-d-bg items-center justify-center">
        <ActivityIndicator size="large" color={tokens.accent} />
        <Text className="text-xs text-muted dark:text-d-muted mt-2">Loading contact card...</Text>
      </View>
    );
  }

  const name = contactData?.name || 'Unknown Contact';
  const phone = contactData?.phone_number || 'No Phone Number';
  const tags = contactData?.tags || [];
  const events = contactData?.contactEvents || [];

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
                {
                  text: 'Delete Contact',
                  style: 'destructive',
                  onPress: async () => {
                    setLoading(true);
                    try {
                      // Laravel template does not have direct destroy endpoint without DRAFT status
                      showDialog('Info', 'Contact deletion restricted on demo server.');
                    } catch (err) {
                      console.warn(err);
                    } finally {
                      setLoading(false);
                    }
                  },
                },
                { text: 'Cancel', style: 'cancel' }
              ]
            );
          }}
        />
      </View>

      <ScrollView contentContainerStyle={{ paddingBottom: 32 }}>
        {/* Hero */}
        <View className="items-center px-5 py-5 gap-2">
          <Avatar name={name} size={88} ring={tokens.accent} dot={contactData?.activeConversation ? tokens.ok : null} />
          {isEditingInfo ? (
            <TextInput
              value={contactName}
              onChangeText={setContactName}
              placeholder="Contact Name"
              className="mt-1.5 text-[22px] font-bold text-ink dark:text-d-ink border-b border-accent text-center min-w-[200px]"
            />
          ) : (
            <Text className="mt-1.5 text-[22px] font-bold text-ink dark:text-d-ink">{name}</Text>
          )}
          <Text className="text-[13px] text-muted dark:text-d-muted">{phone}</Text>
          <View className="flex-row gap-1.5 flex-wrap justify-center mt-1">
            {tags.map((tag: any) => (
              <View
                key={tag.id}
                className="px-2.5 py-[3px] rounded-full bg-surface2 dark:bg-d-surface2"
              >
                <Text className="text-ink2 dark:text-d-ink2 text-[11px] font-medium">{tag.name}</Text>
              </View>
            ))}
            {tags.length === 0 && (
              <Text className="text-[11px] text-muted dark:text-d-muted italic">No segment tags attached</Text>
            )}
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
              onPress: handleInitiateCall,
              active: false,
            },
            {
              Icon: isMuted ? Bell : BellOff,
              label: isMuted ? 'Unmute' : 'Mute',
              onPress: () => {
                const nextMuted = !isMuted;
                setIsMuted(nextMuted);
                showDialog(nextMuted ? 'Muted' : 'Unmuted', `Conversations with ${name} have been ${nextMuted ? 'muted' : 'unmuted'}.`);
              },
              active: isMuted,
            },
            {
              Icon: Archive,
              label: isArchived ? 'Unarchive' : 'Archive',
              onPress: async () => {
                const nextArchived = !isArchived;
                setIsArchived(nextArchived);
                try {
                  if (nextArchived) {
                    await api.post(`/v1/mobile/conversations/${conversationId}/close`);
                  } else {
                    await api.post(`/v1/mobile/conversations/${conversationId}/reopen`);
                  }
                  showDialog(nextArchived ? 'Archived' : 'Unarchived', `Conversations with ${name} have been ${nextArchived ? 'archived' : 'unarchived'}.`);
                } catch (err: any) {
                  console.warn('Archive state sync error:', err);
                }
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
              { l: 'Total Messages', v: contactData?.messages_count || 0 },
              { l: 'Opt In Status',    v: (contactData?.opt_in_status || 'Opted In').replace('_', ' ') },
              { l: 'Since',  v: new Date(contactData?.created_at).toLocaleDateString(undefined, { year: 'numeric', month: 'short' }) },
            ].map((s, i) => (
              <View
                key={s.l}
                className={`flex-1 py-4 items-center ${
                  i < 2 ? 'border-r border-hairline dark:border-d-hairline' : ''
                }`}
              >
                <Text className="text-[16px] font-bold text-ink dark:text-d-ink tracking-[-0.3px] text-center">
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
              handleUpdateContact();
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
              { l: 'Email',   v: contactEmail,   Icon: Globe,    set: setContactEmail },
              { l: 'Company', v: contactCompany, Icon: Inbox,    set: setContactCompany },
              { l: 'Owner',   v: contactData?.assigned_to ? `User #${contactData.assigned_to}` : 'None',     Icon: User },
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
                    <Text className="text-[13.5px] text-ink dark:text-d-ink font-medium">{r.v || 'Not specified'}</Text>
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

          {notesList.map((n) => (
            <View key={n.id} className="p-4 rounded-lg bg-surface dark:bg-d-surface mb-2">
              <Text className="text-ink2 dark:text-d-ink2 text-[13.5px] leading-5">{n.content}</Text>
              <Text className="text-[11px] text-muted dark:text-d-muted mt-2">
                {n.user?.name || 'Agent'} · {new Date(n.created_at).toLocaleDateString()}
              </Text>
            </View>
          ))}
          {notesList.length === 0 && (
            <View className="p-4 rounded-lg bg-surface dark:bg-d-surface items-center justify-center">
              <Text className="text-[13px] text-muted dark:text-d-muted italic">No internal notes logged.</Text>
            </View>
          )}
        </View>

        <SectionLabel>Activity Events</SectionLabel>
        <View className="px-[18px]">
          {events.map((h: any, i: number) => {
            return (
              <View key={i} className="flex-row gap-3 py-3">
                <View className="w-7 h-7 rounded-full bg-surface2 dark:bg-d-surface2 items-center justify-center">
                  <MessageSquare size={13} color={tokens.ink2} strokeWidth={1.8} />
                </View>
                <View className="flex-1">
                  <Text className="text-[13.5px] text-ink dark:text-d-ink font-medium">{h.event_type?.replace(/_/g, ' ') || 'Interaction'}</Text>
                  <Text className="text-[11.5px] text-muted dark:text-d-muted mt-0.5">
                    {h.metadata?.message || 'Contact triggered backend interaction.'} · {new Date(h.created_at).toLocaleDateString()}
                  </Text>
                </View>
              </View>
            );
          })}
          {events.length === 0 && (
            <View className="py-4 items-center justify-center">
              <Text className="text-xs text-muted dark:text-d-muted">No recent activity events recorded.</Text>
            </View>
          )}
        </View>
      </ScrollView>

      {/* Simulated Calling Overlay */}
      {isCalling && (
        <Modal transparent visible={isCalling} animationType="slide">
          <View className="flex-1 bg-ink/95 dark:bg-black/95 items-center justify-center px-6">
            <View className="items-center gap-6">
              <Avatar name={name} size={110} ring={tokens.ok} />
              <View className="items-center">
                <Text className="text-white text-2xl font-bold mt-2">{name}</Text>
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

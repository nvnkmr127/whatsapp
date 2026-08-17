import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, ScrollView, Pressable, TextInput, Modal, ActivityIndicator } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation, useRoute, RouteProp } from '@react-navigation/native';
import {
  ChevronLeft, MoreHorizontal,
  Send, Phone, BellOff, Archive, Globe, Inbox, User, CreditCard, MessageSquare, FilePen, Bell, Tag, Plus, Check,
} from 'lucide-react-native';

import { useTokens } from '@/theme';
import type { RootStackParamList } from '@/types';
import { Avatar } from '@/components/Avatar';
import { IconButton } from '@/components/Button';
import { Card } from '@/components/Card';
import { SectionLabel } from '@/components/SectionLabel';
import { CustomDialog } from '@/components/Dialog';
import { Toggle } from '@/components/Toggle';
import { ListSkeleton } from '@/components/ListItemSkeleton';
import { api } from '@/services/api';
import { safeGoBack } from '@/navigation/navigationRef';

export default function ContactScreen({ navigation, route }: any) {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const nav = navigation;
  const { conversationId, contactId, contactName: contactNameParam, contactPhone: contactPhoneParam } = route.params;

  const [loading, setLoading] = useState(true);
  const [contactData, setContactData] = useState<any>(null);
  const [notesList, setNotesList] = useState<any[]>([]);
  const [callHistory, setCallHistory] = useState<any[]>([]);

  // Local editable states
  const [contactName, setContactName] = useState(contactNameParam || '');
  const [contactEmail, setContactEmail] = useState('');
  const [contactCompany, setContactCompany] = useState('');
  
  const [isMuted, setIsMuted] = useState(false);
  const [isArchived, setIsArchived] = useState(false);
  const [isAddingNote, setIsAddingNote] = useState(false);
  const [newNote, setNewNote] = useState('');
  const [isEditingInfo, setIsEditingInfo] = useState(false);

  // Tag Management States
  const [showTagsModal, setShowTagsModal] = useState(false);
  const [availableTags, setAvailableTags] = useState<any[]>([]);
  const [newTagName, setNewTagName] = useState('');
  const [newTagColor, setNewTagColor] = useState('#10B981');
  const [creatingTag, setCreatingTag] = useState(false);
  const [loadingTags, setLoadingTags] = useState(false);

  const PRESET_COLORS = ['#10B981', '#3B82F6', '#EF4444', '#F59E0B', '#8B5CF6', '#EC4899'];

  const fetchAvailableTags = useCallback(async () => {
    setLoadingTags(true);
    try {
      const res = await api.get('/v1/mobile/contacts/tags');
      setAvailableTags(Array.isArray(res) ? res : (res?.data || []));
    } catch (err) {
      console.warn('Failed to load team tags:', err);
    } finally {
      setLoadingTags(false);
    }
  }, []);

  useEffect(() => {
    if (showTagsModal) {
      fetchAvailableTags();
    }
  }, [showTagsModal, fetchAvailableTags]);

  const handleCreateTag = async () => {
    if (!newTagName.trim()) {
      showDialog('Validation Error', 'Please enter a name for the new tag.');
      return;
    }
    setCreatingTag(true);
    try {
      const res = await api.post('/v1/mobile/contacts/tags', {
        name: newTagName.trim(),
        color: newTagColor,
      });
      if (res.success && res.tag) {
        // Automatically associate the new tag to this contact
        if (contactId) {
          await api.post(`/v1/mobile/contacts/${contactId}/tags/toggle`, {
            tag_id: res.tag.id,
          });
        }
        
        setNewTagName('');
        // Refresh lists
        fetchAvailableTags();
        fetchContactDetails();
      }
    } catch (err: any) {
      showDialog('Error Creating Tag', err.message || 'Could not create new tag.');
    } finally {
      setCreatingTag(false);
    }
  };

  const handleToggleTag = async (tagId: number) => {
    if (!contactId) {
      showDialog('Save Contact', 'Please save this contact first before assigning tags.');
      return;
    }
    try {
      const res = await api.post(`/v1/mobile/contacts/${contactId}/tags/toggle`, {
        tag_id: tagId,
      });
      if (res.success) {
        fetchContactDetails();
      }
    } catch (err: any) {
      showDialog('Error', err.message || 'Could not toggle tag.');
    }
  };

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
      if (contactId) {
        const response = await api.get(`/v1/mobile/contacts/${contactId}`);
        const data = response.contact || {};
        setContactData(data);
        setContactName(data.name || contactNameParam || '');

        // Parse custom attributes/schema
        const customAttrs = data.custom_attributes || {};
        setContactEmail(customAttrs.email || data.email || '');
        setContactCompany(customAttrs.company || data.company || '');

        // Fetch call history
        try {
          const callsRes = await api.get(`/v1/calls/contacts/${contactId}/history`);
          setCallHistory(Array.isArray(callsRes) ? callsRes : (callsRes?.data || []));
        } catch (callsErr) {
          console.warn('Failed to load call history:', callsErr);
        }
      } else {
        // Fallback for unsaved contacts
        setContactData({
          name: contactNameParam || 'Unknown Contact',
          phone_number: contactPhoneParam || '',
        });
        setContactName(contactNameParam || 'Unknown Contact');
      }

      // Fetch notes
      if (conversationId) {
        const notesResponse = await api.get(`/v1/mobile/conversations/${conversationId}/notes`);
        setNotesList(Array.isArray(notesResponse) ? notesResponse : (notesResponse?.data || []));
      }
    } catch (err: any) {
      console.warn('Failed to load contact data:', err);
      showDialog('Error', 'Could not load contact details from server.');
    } finally {
      setLoading(false);
    }
  }, [contactId, conversationId, contactNameParam, contactPhoneParam]);

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
      <View className="flex-1 bg-bg dark:bg-d-bg mt-4">
        <ListSkeleton count={6} />
      </View>
    );
  }

  const name = contactData?.name || 'Unknown Contact';
  const phone = contactData?.phone_number || 'No Phone Number';
  
  // Strictly enforce arrays to prevent .some() or .map() crashes on paginated/malformed data
  const rawTags = contactData?.tags;
  const tags = Array.isArray(rawTags) ? rawTags : (rawTags?.data || []);
  
  const rawEvents = contactData?.contactEvents;
  const events = Array.isArray(rawEvents) ? rawEvents : (rawEvents?.data || []);

  return (
    <View className="flex-1 bg-bg dark:bg-d-bg" style={{ paddingTop: insets.top }}>
      {/* Header */}
      <View className="flex-row items-center justify-between px-3 py-2">
        <IconButton icon={ChevronLeft} onPress={() => safeGoBack(nav, 'Main')} />
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
          <Pressable 
            onPress={() => setShowTagsModal(true)} 
            className="flex-row gap-1.5 flex-wrap justify-center mt-2 px-3 py-1 bg-surface dark:bg-d-surface rounded-full border border-hairline dark:border-d-hairline active:opacity-80 max-w-[90%]"
          >
            {Array.isArray(tags) && tags.map((tag: any) => (
              <View
                key={tag.id}
                style={{ 
                  borderColor: tag.color || '#10B981', 
                  backgroundColor: (tag.color || '#10B981') + '15',
                  borderWidth: 1,
                }}
                className="px-2 py-[2px] rounded-full"
              >
                <Text 
                  style={{ color: tag.color || '#10B981' }} 
                  className="text-[10px] font-semibold"
                >
                  {tag.name}
                </Text>
              </View>
            ))}
            {(!tags || tags.length === 0) ? (
              <Text className="text-[11px] text-muted dark:text-d-muted font-semibold px-1.5">
                + Add tags
              </Text>
            ) : (
              <Text className="text-[10px] text-muted dark:text-d-muted font-bold px-1.5 align-middle self-center">
                + Edit
              </Text>
            )}
          </Pressable>
        </View>

        {/* Quick actions */}
        <View className="flex-row gap-2 px-[18px] pb-4">
          {[
            {
              Icon: Send,
              label: 'Message',
              onPress: () => safeGoBack(nav, 'Main'),
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

        {/* Editable info fields */}
        <SectionLabel action={isEditingInfo ? "Save" : "Edit"} onActionPress={() => isEditingInfo ? handleUpdateContact() : setIsEditingInfo(true)}>
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
                    <Text className="text-[13.5px] text-ink dark:text-d-ink font-medium">{String(r.v || 'Not specified')}</Text>
                  )}
                </View>
              </View>
            ))}
          </Card>
        </View>

        <SectionLabel>Internal Notes</SectionLabel>
        <View className="px-[18px]">
          {isAddingNote ? (
            <View className="bg-surface dark:bg-d-surface rounded-lg p-3 mb-4 border border-accent dark:border-d-accent">
              <TextInput
                value={newNote}
                onChangeText={setNewNote}
                placeholder="Type your note here..."
                placeholderTextColor={tokens.muted}
                className="text-ink dark:text-d-ink text-[13.5px] leading-5 p-0 m-0 min-h-[60px]"
                multiline
                autoFocus
                textAlignVertical="top"
              />
              <View className="flex-row justify-end mt-3 gap-2">
                <Pressable onPress={() => { setIsAddingNote(false); setNewNote(''); }} className="px-4 py-2 rounded-full">
                  <Text className="text-muted dark:text-d-muted text-xs font-semibold">Cancel</Text>
                </Pressable>
                <Pressable onPress={handleAddNote} className="px-4 py-2 rounded-full bg-accent dark:bg-d-accent">
                  <Text className="text-white text-xs font-semibold">Save Note</Text>
                </Pressable>
              </View>
            </View>
          ) : (
            <Pressable 
              onPress={() => setIsAddingNote(true)} 
              className="flex-row items-center justify-center gap-2 py-3 rounded-lg bg-surface dark:bg-d-surface border border-hairline dark:border-d-hairline mb-4 active:opacity-75"
            >
              <FilePen size={14} color={tokens.accent} />
              <Text className="text-accent dark:text-d-accent text-xs font-semibold">Add Internal Note</Text>
            </Pressable>
          )}

          {Array.isArray(notesList) && notesList.filter(Boolean).map((n, i) => (
            <View key={n?.id || `note-${i}`} className="p-4 rounded-lg bg-surface dark:bg-d-surface mb-2">
              <Text className="text-ink2 dark:text-d-ink2 text-[13.5px] leading-5">{String(n?.content || '')}</Text>
              <Text className="text-[11px] text-muted dark:text-d-muted mt-2">
                {String(n?.user?.name || 'Agent')} · {n?.created_at ? new Date(n.created_at).toLocaleDateString() : 'Unknown Date'}
              </Text>
            </View>
          ))}
          {(!notesList || notesList.length === 0) && (
            <View className="p-4 rounded-lg bg-surface dark:bg-d-surface items-center justify-center">
              <Text className="text-[13px] text-muted dark:text-d-muted italic">No internal notes logged.</Text>
            </View>
          )}
        </View>

        <SectionLabel>Call History</SectionLabel>
        <View className="px-[18px]">
          {Array.isArray(callHistory) && callHistory.filter(Boolean).map((c: any, i) => {
            const isMissed = c?.status === 'missed' || c?.status === 'rejected';
            const isOutbound = c?.direction === 'outbound';
            
            let iconColor = tokens.ink2;
            if (isMissed) {
              iconColor = tokens.danger;
            } else if (isOutbound) {
              iconColor = tokens.accent;
            } else {
              iconColor = tokens.ok;
            }

            return (
              <View key={c?.id || `call-${i}`} className="flex-row gap-3 py-3 border-b border-hairline dark:border-d-hairline last:border-0">
                <View className="w-7 h-7 rounded-full bg-surface2 dark:bg-d-surface2 items-center justify-center">
                  <Phone size={13} color={iconColor} strokeWidth={1.8} />
                </View>
                <View className="flex-1">
                  <Text className="text-[13.5px] text-ink dark:text-d-ink font-medium capitalize">
                    {String(c?.direction || 'Unknown')} Call · {String(c?.status || 'Unknown')}
                  </Text>
                  <Text className="text-[11.5px] text-muted dark:text-d-muted mt-0.5">
                    {c?.duration_seconds ? `${c.duration_seconds}s duration` : 'No answer'} · {(c?.created_at || c?.initiated_at) ? new Date(c.created_at || c.initiated_at).toLocaleDateString() : 'Unknown Date'}
                  </Text>
                </View>
              </View>
            );
          })}
          {(!callHistory || callHistory.length === 0) && (
            <View className="p-4 rounded-lg bg-surface dark:bg-d-surface items-center justify-center">
              <Text className="text-[13px] text-muted dark:text-d-muted italic">No call history recorded.</Text>
            </View>
          )}
        </View>

        <SectionLabel>Activity Events</SectionLabel>
        <View className="px-[18px]">
          {Array.isArray(events) && events.filter(Boolean).map((h: any, i: number) => {
            const eventTypeStr = String(h?.event_type || 'Interaction').replace(/_/g, ' ');
            return (
              <View key={h?.id || `event-${i}`} className="flex-row gap-3 py-3">
                <View className="w-7 h-7 rounded-full bg-surface2 dark:bg-d-surface2 items-center justify-center">
                  <MessageSquare size={13} color={tokens.ink2} strokeWidth={1.8} />
                </View>
                <View className="flex-1">
                  <Text className="text-[13.5px] text-ink dark:text-d-ink font-medium">{eventTypeStr}</Text>
                  <Text className="text-[11.5px] text-muted dark:text-d-muted mt-0.5">
                    {String(h?.metadata?.message || 'Contact triggered backend interaction.')} · {h?.created_at ? new Date(h.created_at).toLocaleDateString() : 'Unknown Date'}
                  </Text>
                </View>
              </View>
            );
          })}
          {(!events || events.length === 0) && (
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

      {/* Tag Management Modal */}
      <Modal transparent visible={showTagsModal} animationType="slide">
        <View className="flex-1 bg-black/50 justify-end">
          <View 
            className="bg-surface dark:bg-d-surface rounded-t-2xl p-5 gap-4" 
            style={{ maxHeight: '80%', paddingBottom: insets.bottom + 16 }}
          >
            {/* Header */}
            <View className="flex-row items-center justify-between border-b border-hairline dark:border-d-hairline pb-2.5">
              <View className="flex-row items-center gap-2">
                <Tag size={16} color={tokens.accent} />
                <Text className="text-base font-bold text-ink dark:text-d-ink">Manage Contact Tags</Text>
              </View>
              <Pressable onPress={() => setShowTagsModal(false)}>
                <Text className="text-muted dark:text-d-muted font-semibold text-sm">Close</Text>
              </Pressable>
            </View>

            {/* Create Tag Section */}
            <View className="bg-surface2 dark:bg-d-surface2 p-3.5 rounded-xl gap-3">
              <Text className="text-xs font-bold text-muted dark:text-d-muted uppercase tracking-wide">
                Create New Tag
              </Text>
              <View className="flex-row gap-2">
                <TextInput
                  placeholder="e.g. VIP, Wholesale"
                  placeholderTextColor={tokens.faint}
                  value={newTagName}
                  onChangeText={setNewTagName}
                  className="flex-1 bg-surface dark:bg-d-surface px-3 py-2 text-sm rounded-lg text-ink dark:text-d-ink border border-hairline dark:border-d-hairline font-medium"
                />
                <Pressable
                  disabled={creatingTag}
                  onPress={handleCreateTag}
                  className="bg-accent dark:bg-d-accent px-4 py-2 rounded-lg items-center justify-center active:opacity-90 flex-row gap-1.5"
                >
                  {creatingTag ? (
                    <ActivityIndicator size="small" color="#FFFFFF" />
                  ) : (
                    <>
                      <Plus size={14} color="#FFFFFF" strokeWidth={2.5} />
                      <Text className="text-white text-xs font-bold">Create</Text>
                    </>
                  )}
                </Pressable>
              </View>

              {/* Color Preset Selectors */}
              <View className="flex-row gap-3 mt-1 items-center">
                <Text className="text-[11px] text-muted dark:text-d-muted font-medium">Tag Color:</Text>
                <View className="flex-row gap-2">
                  {PRESET_COLORS.map((c) => {
                    const isSelected = newTagColor === c;
                    return (
                      <Pressable
                        key={c}
                        onPress={() => setNewTagColor(c)}
                        style={{ backgroundColor: c, width: 22, height: 22, borderRadius: 11 }}
                        className="items-center justify-center relative active:scale-95"
                      >
                        {isSelected && (
                          <View className="w-4 h-4 rounded-full bg-black/20 items-center justify-center">
                            <Check size={10} color="#FFFFFF" strokeWidth={3} />
                          </View>
                        )}
                      </Pressable>
                    );
                  })}
                </View>
              </View>
            </View>

            {/* List Section */}
            <Text className="text-xs font-bold text-muted dark:text-d-muted uppercase tracking-wide mt-2">
              Available Segment Tags
            </Text>

            {loadingTags ? (
              <View className="py-10 items-center justify-center">
                <ActivityIndicator size="small" color={tokens.accent} />
              </View>
            ) : (
              <ScrollView 
                className="max-h-[300px]"
                contentContainerStyle={{ gap: 8, paddingBottom: 16 }}
              >
                {availableTags.map((tag) => {
                  const isAttached = tags.some((t: any) => t.id === tag.id);
                  return (
                    <Pressable
                      key={tag.id}
                      onPress={() => handleToggleTag(tag.id)}
                      className="flex-row items-center justify-between p-3 rounded-lg bg-surface2 dark:bg-d-surface2 border border-hairline dark:border-d-hairline active:opacity-90"
                    >
                      <View className="flex-row items-center gap-2.5">
                        <View 
                          style={{ backgroundColor: tag.color || '#10B981', width: 12, height: 12, borderRadius: 6 }} 
                        />
                        <Text className="text-sm font-semibold text-ink dark:text-d-ink">
                          {tag.name}
                        </Text>
                      </View>
                      <View 
                        className={`w-5 h-5 rounded-md items-center justify-center border ${
                          isAttached 
                            ? 'bg-accent dark:bg-d-accent border-accent' 
                            : 'border-faint dark:border-d-faint bg-surface dark:bg-d-surface'
                        }`}
                      >
                        {isAttached && <Check size={12} color="#FFFFFF" strokeWidth={3} />}
                      </View>
                    </Pressable>
                  );
                })}

                {availableTags.length === 0 && (
                  <View className="py-10 items-center justify-center">
                    <Text className="text-xs text-muted dark:text-d-muted italic text-center">
                      No tags available for this workspace. Create one above to get started!
                    </Text>
                  </View>
                )}
              </ScrollView>
            )}
          </View>
        </View>
      </Modal>

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

// src/types.ts — shared types used across screens.

export type ConversationTag = 'Sales' | 'Support' | 'B2B' | 'Bot';
export type MessageStatus = 'queued' | 'sent' | 'delivered' | 'read' | 'failed';

export interface Conversation {
  id: number;
  contact_id?: number;
  name: string;
  last: string;
  time: string;
  unread: number;
  status: MessageStatus;
  tag?: string;
  tagColor?: string;
  phone?: string;
  online?: boolean;
  pinned?: boolean;
  reply?: 'me';
  bot?: boolean;
  group?: boolean;
}

export type ChatMessageKind = 'in' | 'out' | 'date';
export interface ChatMessage {
  id?: number;
  kind: ChatMessageKind;
  text: string;
  time?: string;
  status?: MessageStatus;
  isStarred?: boolean;
  media_url?: string | null;
  media_type?: string | null;
  metadata?: any;
  reply_to_content?: string | null;
  reply_to_id?: number | null;
  reply_to_is_outbound?: boolean;
  reply_to_media_url?: string | null;
  reply_to_media_type?: string | null;
}

export interface ContactProfile {
  name: string;
  phone: string;
  email: string;
  company: string;
  tags: string[];
  notes: string;
  stats: { orders: number; ltv: string; firstSeen: string };
  history: { type: 'order' | 'message' | 'note' | 'campaign'; text: string; time: string }[];
}

export interface Template {
  id: number;
  name: string;
  cat: 'Marketing' | 'Utility' | 'Authentication';
  lang: string;
  status: 'Approved' | 'Pending' | 'Rejected';
  uses: string;
  preview: string;
}

export interface Automation {
  name: string;
  desc: string;
  icon: string;
  on: boolean;
  runs: string;
}

export interface KpiCard {
  label: string;
  value: string;
  delta: string;
  up: boolean;
  trend: number[];
}

export type RootStackParamList = {
  Onboarding: undefined;
  Main: undefined;
  Chat: { conversation: Conversation };
  Contact: { conversationId: number; contactId: number };
  Broadcast: undefined;
  Login: undefined;
  Activities: undefined;
  ActivityDetail: { activityId: number };
  CampaignDetail: { campaignId: number };
  Calls: undefined;
  Bots: undefined;
  AiSettings: undefined;
  StarredMessages: undefined;
};

export type MainTabParamList = {
  Inbox: undefined;
  Templates: undefined;
  Analytics: undefined;
  Automations: undefined;
  Settings: undefined;
};

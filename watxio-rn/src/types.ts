// src/types.ts — shared types used across screens.

export type ConversationTag = 'Sales' | 'Support' | 'B2B' | 'Bot';
export type MessageStatus = 'sent' | 'delivered' | 'read';

export interface Conversation {
  id: number;
  name: string;
  last: string;
  time: string;
  unread: number;
  status: MessageStatus;
  tag: ConversationTag;
  phone?: string;
  online?: boolean;
  pinned?: boolean;
  reply?: 'me';
  bot?: boolean;
  group?: boolean;
}

export type ChatMessageKind = 'in' | 'out' | 'date';
export interface ChatMessage {
  kind: ChatMessageKind;
  text: string;
  time?: string;
  status?: MessageStatus;
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
  Chat: { contact: Conversation };
  Contact: { conversationId: number; contactId: number };
  Broadcast: undefined;
  Login: undefined;
};

export type MainTabParamList = {
  Inbox: undefined;
  Templates: undefined;
  Analytics: undefined;
  Automations: undefined;
  Settings: undefined;
};

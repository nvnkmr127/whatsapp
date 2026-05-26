// src/data.ts — sample fixtures for screens.

import type {
  Conversation, ChatMessage, ContactProfile, Template, Automation, KpiCard,
} from './types';

export const ME = { name: 'Naveen A.', role: 'Founder · Watxio' };

export const BUSINESS = {
  name: 'Acme Coffee Roasters',
  waNumber: '+1 (415) 555-0118',
  plan: 'Business · Tier 2',
};

export const CONVERSATIONS: Conversation[] = [
  { id: 1, name: 'Priya Mehta',         last: 'Thanks! Order #4821 is on the way 🎁',          time: '9:24',  unread: 2, status: 'read', tag: 'Sales',   online: true,  pinned: true },
  { id: 2, name: 'Daniel Okafor',       last: 'Can I swap the espresso roast for filter?',     time: '9:18',  unread: 0, status: 'read', tag: 'Support', online: true,  reply: 'me' },
  { id: 3, name: 'Lab Coffee — Berlin', last: 'Wholesale terms attached.',                     time: '8:55',  unread: 0, status: 'sent', tag: 'B2B',     group: true },
  { id: 4, name: 'Aiko Tanaka',         last: 'voice message (0:42)',                          time: '8:31',  unread: 1, status: 'read', tag: 'Sales' },
  { id: 5, name: 'Mateo García',        last: 'Bot: We replied with the menu.',                time: 'Yes',   unread: 0, status: 'read', tag: 'Bot',     bot: true },
  { id: 6, name: 'Chen Wei',            last: 'Got the invoice — paying today.',               time: 'Yes',   unread: 0, status: 'read', tag: 'B2B' },
  { id: 7, name: 'Sofia Rinaldi',       last: 'Will the Tuesday tasting still happen?',        time: 'Mon',   unread: 0, status: 'read', tag: 'Sales' },
  { id: 8, name: 'Hiroshi Ueda',        last: 'Sent the certificate.',                         time: 'Mon',   unread: 0, status: 'read', tag: 'Support' },
];

export const CHAT_MESSAGES: ChatMessage[] = [
  { kind: 'date', text: 'Today' },
  { kind: 'in',  text: 'Hi! I placed an order yesterday but haven’t received tracking yet.', time: '9:12' },
  { kind: 'in',  text: 'Order #4821, the Ethiopia Yirgacheffe.', time: '9:12' },
  { kind: 'out', text: 'Hey Priya — pulling it up now.', time: '9:14', status: 'read' },
  { kind: 'out', text: 'Order #4821 shipped this morning via DHL. Tracking: 8421-9213.', time: '9:15', status: 'read' },
  { kind: 'in',  text: 'Amazing, thanks so much!', time: '9:23' },
  { kind: 'out', text: 'Of course. Anything else?', time: '9:24', status: 'sent' },
];

export const CONTACT_PROFILE: ContactProfile = {
  name: 'Priya Mehta',
  phone: '+1 (415) 555-0118',
  email: 'priya@studio.co',
  company: 'Studio Mehta',
  tags: ['VIP', 'Subscriber', 'Wholesale-interest'],
  notes: 'Prefers light roasts. Allergic to peanuts. Birthday: Mar 12.',
  stats: { orders: 11, ltv: '$842', firstSeen: 'Jan 2023' },
  history: [
    { type: 'order',    text: 'Order #4821 — $42.00',       time: 'Yesterday' },
    { type: 'message',  text: '14 messages exchanged',      time: 'This week' },
    { type: 'note',     text: 'Added to "VIP" segment',     time: 'Mon' },
    { type: 'campaign', text: 'Received: launch_announce',  time: 'Aug 14' },
  ],
};

export const TEMPLATES: Template[] = [
  { name: 'order_shipped_v2', cat: 'Utility',        lang: 'EN', status: 'Approved', uses: '2.4k',  preview: 'Your order {{1}} has shipped! Track it here: {{2}}' },
  { name: 'cart_recovery',    cat: 'Marketing',      lang: 'EN', status: 'Approved', uses: '892',   preview: 'You left {{1}} in your cart — grab it before it’s gone.' },
  { name: 'otp_login_v3',     cat: 'Authentication', lang: 'EN', status: 'Approved', uses: '14.1k', preview: '{{1}} is your Acme Coffee verification code.' },
  { name: 'review_request',   cat: 'Marketing',      lang: 'EN', status: 'Pending',  uses: '—',     preview: 'Loved your beans? Leave a 30s review and get 10% off.' },
  { name: 'invoice_reminder', cat: 'Utility',        lang: 'EN', status: 'Approved', uses: '331',   preview: 'Invoice {{1}} is due in 3 days.' },
  { name: 'launch_announce',  cat: 'Marketing',      lang: 'EN', status: 'Rejected', uses: '—',     preview: '🔥 Limited drop — 200 bags only.' },
];

export const AUTOMATIONS: Automation[] = [
  { name: 'Welcome flow',         desc: 'Greets first-time customers in <15s', icon: 'Sparkles',     on: true,  runs: '4,210 / wk' },
  { name: 'Order confirmation',   desc: 'Sends after Shopify webhook',         icon: 'CreditCard',   on: true,  runs: '1,890 / wk' },
  { name: 'Out-of-office',        desc: 'Sat–Sun 18:00–09:00 UTC',             icon: 'CalendarDays', on: true,  runs: 'always' },
  { name: 'Cart recovery',        desc: 'Re-engages abandoned carts after 6h', icon: 'Reply',        on: false, runs: 'paused' },
  { name: 'AI menu assistant',    desc: 'GPT replies for common questions',    icon: 'Bot',          on: true,  runs: '612 / wk' },
  { name: 'Escalate to human',    desc: 'When sentiment drops below 0.3',     icon: 'Users',        on: true,  runs: '38 / wk' },
];

export const CANNED_REPLIES = [
  { name: 'Hours & location',  text: 'We’re open Mon–Sat, 8am–6pm at Folsom & 7th.' },
  { name: 'Shipping ETA',      text: 'Most orders ship within 24h via DHL Express.' },
  { name: 'Wholesale enquiry', text: 'Thanks for reaching out about wholesale. Min order is 5kg.' },
  { name: 'Returns policy',    text: 'Returns accepted within 14 days, beans unopened.' },
];

export const KPIS: KpiCard[] = [
  { label: 'Conversations',   value: '1,284',  delta: '+12.4%', up: true,  trend: [8,7,9,11,10,12,14,15,13,16,18,17,19,21] },
  { label: 'Avg response',    value: '2m 41s', delta: '-18s',   up: true,  trend: [22,21,20,21,19,18,17,18,16,15,14,15,14,13] },
  { label: 'Messages sent',   value: '32.8k',  delta: '+8.1%',  up: true,  trend: [12,13,11,14,15,14,16,17,18,19,21,22,24,25] },
  { label: 'Resolution rate', value: '94%',    delta: '+1.2%',  up: true,  trend: [88,89,90,90,91,92,92,93,93,94,94,94,94,94] },
];

export const FUNNEL = [
  { label: 'Sent',      n: 32800, pct: 100  },
  { label: 'Delivered', n: 31420, pct: 95.8 },
  { label: 'Read',      n: 24180, pct: 73.7 },
  { label: 'Replied',   n: 9842,  pct: 30.0 },
];

export const TOP_TEMPLATES = [
  { name: 'order_shipped_v2', uses: 2410, ctr: 41 },
  { name: 'cart_recovery',    uses: 892,  ctr: 28 },
  { name: 'invoice_reminder', uses: 331,  ctr: 22 },
];

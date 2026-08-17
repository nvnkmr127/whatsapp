import React from 'react';
import { View } from 'react-native';
import { Clock, AlertCircle, Check, CheckCheck } from 'lucide-react-native';
import { useTokens } from '@/theme';
import type { MessageStatus } from '@/types';

interface WhatsAppStatusTickProps {
  status?: MessageStatus | string | null;
  size?: number;
  colorOverride?: string;
}

// Official WhatsApp Blue: #53BDEB (Web / Android) / #34B7F1 (iOS)
export const WA_BLUE = '#53BDEB';
export const WA_MUTED = '#8696A0';

/**
 * Pixel-perfect native WhatsApp Status Checkmarks
 * - Pending/Queued: Clock icon (🕒)
 * - Sent: Single grey checkmark (✓)
 * - Delivered: Double grey checkmark (✓✓)
 * - Read: Double blue checkmark (✓✓) in official WhatsApp Cyan Blue (#53BDEB)
 * - Failed: Red alert icon with exclamation (!)
 */
export function WhatsAppStatusTick({
  status = 'sent',
  size = 15,
  colorOverride,
}: WhatsAppStatusTickProps) {
  const { tokens } = useTokens();

  const normalizedStatus = String(status || 'sent').toLowerCase();

  // Read status ALWAYS uses official WhatsApp Cyan Blue unless explicitly styled
  const isRead = normalizedStatus === 'read' || normalizedStatus === 'seen';
  const defaultMutedColor = colorOverride || tokens?.muted || WA_MUTED;
  const strokeColor = isRead ? WA_BLUE : defaultMutedColor;

  if (normalizedStatus === 'failed') {
    return (
      <AlertCircle
        size={Math.max(12, size - 3)}
        color={tokens?.danger || '#EF4444'}
        strokeWidth={2}
      />
    );
  }

  if (
    normalizedStatus === 'queued' ||
    normalizedStatus === 'sending' ||
    normalizedStatus === 'pending'
  ) {
    return (
      <Clock
        size={Math.max(11, size - 4)}
        color={defaultMutedColor}
        strokeWidth={1.8}
      />
    );
  }

  if (normalizedStatus === 'sent') {
    return (
      <Check
        size={Math.max(13, size - 2)}
        color={strokeColor}
        strokeWidth={2.2}
      />
    );
  }

  // Delivered or Read (Double Ticks)
  return (
    <CheckCheck
      size={size}
      color={strokeColor}
      strokeWidth={2.2}
    />
  );
}

import React from 'react';
import { View } from 'react-native';
import Svg, { Path } from 'react-native-svg';
import { Clock, AlertCircle } from 'lucide-react-native';
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
 * - Pending/Queued: Clock icon
 * - Sent: Single grey checkmark (✓)
 * - Delivered: Double grey checkmark (✓✓)
 * - Read: Double blue checkmark (✓✓) in official WhatsApp Cyan Blue (#53BDEB)
 * - Failed: Red alert icon with exclamation (!)
 */
export function WhatsAppStatusTick({
  status = 'sent',
  size = 16,
  colorOverride,
}: WhatsAppStatusTickProps) {
  const { tokens } = useTokens();

  const normalizedStatus = String(status || 'sent').toLowerCase();

  // Read status ALWAYS uses official WhatsApp Cyan Blue unless explicitly styled
  const isRead = normalizedStatus === 'read' || normalizedStatus === 'seen';
  const defaultMutedColor = colorOverride || tokens.muted || WA_MUTED;
  const strokeColor = isRead ? WA_BLUE : defaultMutedColor;

  if (normalizedStatus === 'failed') {
    return (
      <View style={{ width: 15, height: 13, justifyContent: 'center', alignItems: 'center' }}>
        <AlertCircle
          size={Math.max(12, size - 2)}
          color={tokens.danger || '#EF4444'}
          strokeWidth={2}
        />
      </View>
    );
  }

  if (
    normalizedStatus === 'queued' ||
    normalizedStatus === 'sending' ||
    normalizedStatus === 'pending'
  ) {
    return (
      <View style={{ width: 15, height: 13, justifyContent: 'center', alignItems: 'center' }}>
        <Clock
          size={Math.max(11, size - 4)}
          color={defaultMutedColor}
          strokeWidth={1.8}
        />
      </View>
    );
  }

  if (normalizedStatus === 'sent') {
    return (
      <View style={{ width: 15, height: 13, justifyContent: 'center', alignItems: 'center' }}>
        <Svg width={15} height={11} viewBox="0 0 16 11" fill="none">
          <Path
            d="M11.05 1.5L4.85 7.7L1.95 4.8"
            stroke={strokeColor}
            strokeWidth={1.85}
            strokeLinecap="round"
            strokeLinejoin="round"
          />
        </Svg>
      </View>
    );
  }

  // Delivered or Read (Double Ticks)
  return (
    <View style={{ width: 17, height: 13, justifyContent: 'center', alignItems: 'center' }}>
      <Svg width={17} height={11} viewBox="0 0 16 11" fill="none">
        {/* First Checkmark */}
        <Path
          d="M9.05 1.5L2.85 7.7L0.95 5.8"
          stroke={strokeColor}
          strokeWidth={1.85}
          strokeLinecap="round"
          strokeLinejoin="round"
        />
        {/* Second Checkmark (Interlocked with offset) */}
        <Path
          d="M14.05 1.5L7.85 7.7L5.45 5.3"
          stroke={strokeColor}
          strokeWidth={1.85}
          strokeLinecap="round"
          strokeLinejoin="round"
        />
      </Svg>
    </View>
  );
}

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
  status,
  size = 16,
  colorOverride,
}: WhatsAppStatusTickProps) {
  const { tokens } = useTokens();

  if (!status) return null;

  const normalizedStatus = String(status).toLowerCase();

  // Read status ALWAYS uses official WhatsApp Cyan Blue unless explicitly styled
  const isRead = normalizedStatus === 'read' || normalizedStatus === 'seen';
  const defaultMutedColor = colorOverride || tokens.muted || WA_MUTED;
  const strokeColor = isRead ? WA_BLUE : defaultMutedColor;

  if (normalizedStatus === 'failed') {
    return (
      <AlertCircle
        size={Math.max(12, size - 2)}
        color={tokens.danger || '#EF4444'}
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
    const svgWidth = size;
    const svgHeight = Math.round(size * 0.75);

    return (
      <View style={{ width: svgWidth, height: svgHeight, justifyContent: 'center', alignItems: 'center' }}>
        <Svg width={svgWidth} height={svgHeight} viewBox="0 0 16 12" fill="none">
          <Path
            d="M2.5 6.5L6 10L13.5 2.5"
            stroke={strokeColor}
            strokeWidth={1.9}
            strokeLinecap="round"
            strokeLinejoin="round"
          />
        </Svg>
      </View>
    );
  }

  // Delivered or Read (Double Ticks)
  const doubleSvgWidth = Math.round(size * 1.15);
  const doubleSvgHeight = Math.round(size * 0.75);

  return (
    <View style={{ width: doubleSvgWidth, height: doubleSvgHeight, justifyContent: 'center', alignItems: 'center' }}>
      <Svg width={doubleSvgWidth} height={doubleSvgHeight} viewBox="0 0 18 12" fill="none">
        {/* First Checkmark */}
        <Path
          d="M1.5 6.5L5 10L12.5 2.5"
          stroke={strokeColor}
          strokeWidth={1.9}
          strokeLinecap="round"
          strokeLinejoin="round"
        />
        {/* Second Checkmark (Interlocked with 4.5px offset) */}
        <Path
          d="M6 6.5L9.5 10L17 2.5"
          stroke={strokeColor}
          strokeWidth={1.9}
          strokeLinecap="round"
          strokeLinejoin="round"
        />
      </Svg>
    </View>
  );
}

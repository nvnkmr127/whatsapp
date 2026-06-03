/**
 * Type shims for packages that ship without TypeScript declarations.
 * Each `declare module` here silences the TS2307 "cannot find module" error
 * and provides enough surface-level typing for the code that uses them.
 */

// ── rn-emoji-keyboard ─────────────────────────────────────────────────────────
declare module 'rn-emoji-keyboard' {
  import React from 'react';

  export interface EmojiType {
    emoji: string;
    slug: string;
    unicode_version: string;
    toneEnabled: boolean;
  }

  export interface EmojiPickerProps {
    open: boolean;
    onClose: () => void;
    onEmojiSelected: (emoji: EmojiType) => void;
    /** Expand to full screen (default: false) */
    expandable?: boolean;
    /** Default category shown on open */
    defaultOpen?: string;
  }

  const EmojiPicker: React.FC<EmojiPickerProps>;
  export default EmojiPicker;
}

// ── react-native-webrtc ───────────────────────────────────────────────────────
declare module 'react-native-webrtc' {
  export interface RTCIceServer {
    urls: string | string[];
    username?: string;
    credential?: string;
  }

  export interface RTCConfiguration {
    iceServers?: RTCIceServer[];
    iceTransportPolicy?: 'all' | 'relay';
    bundlePolicy?: 'balanced' | 'max-bundle' | 'max-compat';
    rtcpMuxPolicy?: 'require' | 'negotiate';
  }

  export interface RTCSessionDescriptionInit {
    type: 'offer' | 'answer' | 'pranswer' | 'rollback';
    sdp?: string;
  }

  export class RTCSessionDescription {
    constructor(init: RTCSessionDescriptionInit);
    type: string;
    sdp: string;
  }

  export class RTCPeerConnection {
    constructor(configuration?: RTCConfiguration);
    localDescription: RTCSessionDescription | null;
    remoteDescription: RTCSessionDescription | null;
    signalingState: string;
    iceGatheringState: string;
    iceConnectionState: string;
    connectionState: string;
    createOffer(options?: { offerToReceiveAudio?: boolean; offerToReceiveVideo?: boolean }): Promise<RTCSessionDescriptionInit>;
    createAnswer(): Promise<RTCSessionDescriptionInit>;
    setLocalDescription(desc: RTCSessionDescriptionInit): Promise<void>;
    setRemoteDescription(desc: RTCSessionDescription): Promise<void>;
    addTrack(track: MediaStreamTrack, ...streams: MediaStream[]): RTCRtpSender;
    close(): void;
    addEventListener(event: string, handler: () => void): void;
    removeEventListener(event: string, handler: () => void): void;
  }

  export interface MediaStream {
    id: string;
    active: boolean;
    getTracks(): MediaStreamTrack[];
    getAudioTracks(): MediaStreamTrack[];
    getVideoTracks(): MediaStreamTrack[];
  }

  export interface MediaStreamTrack {
    id: string;
    kind: 'audio' | 'video';
    enabled: boolean;
    muted: boolean;
    readyState: 'live' | 'ended';
    stop(): void;
  }

  export interface RTCRtpSender {
    track: MediaStreamTrack | null;
  }

  export interface MediaDevices {
    getUserMedia(constraints: { audio: boolean; video: boolean }): Promise<MediaStream>;
    enumerateDevices(): Promise<MediaDeviceInfo[]>;
  }

  export const mediaDevices: MediaDevices;
}

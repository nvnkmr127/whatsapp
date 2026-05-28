/**
 * WebRTC session helper for WhatsApp Business calling.
 *
 * Wraps react-native-webrtc so that ChatScreen and CallOverlayManager can share
 * a single RTCPeerConnection for the duration of a call.
 *
 * Usage:
 *   Outbound call (caller):
 *     const sdp = await CallWebRTC.session().initOutbound();
 *     // POST /v1/calls/initiate with { phone_number, sdp }
 *     // Later, when Meta delivers the remote answer via webhook:
 *     await CallWebRTC.session().applyAnswer(answerSdp);
 *
 *   Inbound call (agent answers):
 *     const sdp = await CallWebRTC.session().initInbound(offerSdp);
 *     // POST /v1/calls/{callId}/answer with { sdp }
 */

import {
  RTCPeerConnection,
  RTCSessionDescription,
  mediaDevices,
} from 'react-native-webrtc';

const ICE_SERVERS = [
  { urls: 'stun:stun.l.google.com:19302' },
  { urls: 'stun:stun1.l.google.com:19302' },
];

const ICE_GATHER_TIMEOUT_MS = 3000;

// ─────────────────────────────────────────────────────────
// SDP sanitiser (mirrors the web call-overlay implementation)
// ─────────────────────────────────────────────────────────
function sanitizeSdp(sdp: string): string {
  return (
    sdp
      .replace(/[^\x20-\x7E\r\n]/g, '')
      .replace(/\r\n|\r|\n/g, '\n')
      .split('\n')
      .map((l) => l.trim())
      .filter((l) => l)
      .join('\r\n') + '\r\n'
  );
}

// ─────────────────────────────────────────────────────────
// WebRTCSession — one instance per call
// ─────────────────────────────────────────────────────────
export class WebRTCSession {
  private pc: RTCPeerConnection | null = null;
  private localStream: any = null;

  /** Create a WebRTC offer for an outbound call. Returns the sanitised SDP. */
  async initOutbound(): Promise<string> {
    await this._setup();
    const offer = await (this.pc as any).createOffer({ offerToReceiveAudio: true });
    await (this.pc as any).setLocalDescription(offer);
    await this._waitForIce();
    return sanitizeSdp((this.pc as any).localDescription.sdp);
  }

  /**
   * Apply the remote party's SDP answer (received via the active-calls poll
   * once the webhook delivers it to the server).
   */
  async applyAnswer(sdp: string): Promise<void> {
    if (!this.pc) return;
    if ((this.pc as any).signalingState !== 'have-local-offer') return;
    const desc = new RTCSessionDescription({ type: 'answer', sdp: sanitizeSdp(sdp) });
    await (this.pc as any).setRemoteDescription(desc);
  }

  /** Accept an inbound call: consume the caller's offer, return our answer SDP. */
  async initInbound(offerSdp: string): Promise<string> {
    await this._setup();
    const offer = new RTCSessionDescription({ type: 'offer', sdp: sanitizeSdp(offerSdp) });
    await (this.pc as any).setRemoteDescription(offer);
    const answer = await (this.pc as any).createAnswer();
    await (this.pc as any).setLocalDescription(answer);
    await this._waitForIce();
    return sanitizeSdp((this.pc as any).localDescription.sdp);
  }

  /** Toggle microphone mute. */
  setMuted(muted: boolean): void {
    if (!this.localStream) return;
    this.localStream.getAudioTracks().forEach((track: any) => {
      track.enabled = !muted;
    });
  }

  /** Release all resources. */
  close(): void {
    try {
      this.localStream?.getTracks().forEach((t: any) => t.stop());
    } catch (_) {}
    this.localStream = null;
    try {
      (this.pc as any)?.close();
    } catch (_) {}
    this.pc = null;
  }

  // ── private ──────────────────────────────────────────

  private async _setup(): Promise<void> {
    this.pc = new RTCPeerConnection({ iceServers: ICE_SERVERS } as any);
    const stream = await mediaDevices.getUserMedia({ audio: true, video: false });
    this.localStream = stream;
    (stream as any).getTracks().forEach((track: any) => {
      (this.pc as any).addTrack(track, stream);
    });
  }

  private _waitForIce(): Promise<void> {
    return new Promise((resolve) => {
      const pc = this.pc as any;
      if (pc.iceGatheringState === 'complete') {
        resolve();
        return;
      }
      const handler = () => {
        if (pc.iceGatheringState === 'complete') {
          pc.removeEventListener('icegatheringstatechange', handler);
          resolve();
        }
      };
      pc.addEventListener('icegatheringstatechange', handler);
      setTimeout(resolve, ICE_GATHER_TIMEOUT_MS);
    });
  }
}

// ─────────────────────────────────────────────────────────
// Module-level singleton — shared across ChatScreen and
// CallOverlayManager without needing React context.
// ─────────────────────────────────────────────────────────
let _current: WebRTCSession | null = null;

export const CallWebRTC = {
  /** Return the existing session or create a new one. */
  session(): WebRTCSession {
    if (!_current) _current = new WebRTCSession();
    return _current;
  },

  /** True when a session is live (even before answer). */
  hasSession(): boolean {
    return _current !== null;
  },

  /** Tear down the current session (call ended/failed). */
  end(): void {
    _current?.close();
    _current = null;
  },
};

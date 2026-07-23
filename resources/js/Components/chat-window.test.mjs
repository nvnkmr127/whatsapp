// Run: npm test
//
// Covers the composer bug this was written for: picking an image left the send
// button showing a mic icon and Enter did nothing, because both keyed off
// $wire.newAttachmentData — which only lands a server roundtrip after the file
// is chosen. Local state has to flip immediately, and the send has to wait for
// the upload rather than silently dropping it.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import chatWindow from './chat-window.js';

// Alpine/Livewire surface the component touches, stubbed.
function makeComponent({ newAttachmentData = null, uploadError = null } = {}) {
    const wire = {
        newAttachmentData,
        uploadError,
        sent: [],
        deleteAttachmentCalls: 0,
        set() {},
        sendMessage() { this.sent.push('media'); },
        deleteAttachment() { this.deleteAttachmentCalls++; },
    };

    const c = chatWindow(wire, '1', '1', '1', false, false, false, false, '', []);
    c.$refs = { fileInput: { value: 'C:\\fakepath\\photo.jpg' } };
    c.$store = { chat: { sendMessage(body) { wire.sent.push(body); }, isLockedForMe: () => false } };
    c.wire = wire;

    return c;
}

const file = (over = {}) => ({ name: 'photo.jpg', size: 1024, type: 'image/jpeg', ...over });

// Node's real createObjectURL demands a Blob; these tests use plain file stubs.
globalThis.URL.createObjectURL = () => 'blob:stub';
globalThis.URL.revokeObjectURL = () => {};

test('picking a file reveals the send button before the upload finishes', () => {
    const c = makeComponent();
    assert.equal(c.canSend, false, 'empty composer shows the mic, not send');

    c.onFilePicked(file());

    assert.equal(c.hasAttachment, true);
    assert.equal(c.canSend, true, 'send button must appear immediately, not after the roundtrip');
    assert.equal(c.attachmentName, 'photo.jpg');
});

test('typing alone also enables send', () => {
    const c = makeComponent();
    c.msgBody = '  ';
    assert.equal(c.canSend, false, 'whitespace is not a message');
    c.msgBody = 'hello';
    assert.equal(c.canSend, true);
});

test('an oversized file is rejected and never arms the composer', () => {
    const c = makeComponent();
    const notifications = [];
    globalThis.window ??= globalThis;
    globalThis.window.dispatchEvent = (e) => notifications.push(e?.detail?.message);
    globalThis.CustomEvent ??= class { constructor(t, o) { Object.assign(this, o); this.type = t; } };

    c.onFilePicked(file({ size: 20 * 1024 * 1024 }));

    assert.equal(c.hasAttachment, false, 'a rejected file must not look sendable');
    assert.equal(c.canSend, false);
    assert.match(notifications.join(' '), /too large/i);
});

test('submitting with an attachment waits for the upload, then sends', async () => {
    const c = makeComponent();
    c.onFilePicked(file());

    // Upload lands mid-submit, exactly as it does over the wire.
    setTimeout(() => { c.wire.newAttachmentData = { path: 'chat-media/photo.jpg' }; }, 120);
    await c.handleSubmit();

    assert.deepEqual(c.wire.sent, ['media'], 'the send must not be dropped while uploading');
    assert.equal(c.hasAttachment, false, 'composer resets after a successful send');
    assert.equal(c.wire.deleteAttachmentCalls, 0, 'the sent file must not be deleted');
});

test('a failed upload reports instead of sending nothing', async () => {
    const c = makeComponent({ uploadError: 'File upload failed' });
    const notifications = [];
    globalThis.window.dispatchEvent = (e) => notifications.push(e?.detail?.message);

    c.onFilePicked(file());
    await c.handleSubmit();

    assert.deepEqual(c.wire.sent, [], 'nothing should be sent');
    assert.match(notifications.join(' '), /upload failed/i, 'the agent must be told');
});

test('cancelling an attachment tells the server to drop the stored file', () => {
    const c = makeComponent();
    c.onFilePicked(file());

    c.clearAttachment();

    assert.equal(c.canSend, false);
    assert.equal(c.wire.deleteAttachmentCalls, 1, 'an abandoned upload would otherwise be orphaned');
});

test('a plain text message goes through the store, not the media path', async () => {
    const c = makeComponent();
    c.msgBody = 'just text';

    await c.handleSubmit();

    assert.deepEqual(c.wire.sent, ['just text']);
    assert.equal(c.msgBody, '', 'input clears after sending');
});

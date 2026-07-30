// src/utils/text.ts — helper functions for safe text extraction

/**
 * Safely extracts a string from any input (string, object, number, boolean, array, etc.)
 * Prevents React/React Native runtime crashes caused by rendering objects as React children.
 */
export function safeExtractText(val: any): string {
  if (val === null || val === undefined) return '';
  if (typeof val === 'string') return val;
  if (typeof val === 'number' || typeof val === 'boolean') return String(val);
  if (typeof val === 'object') {
    if (typeof val.content === 'string') return val.content;
    if (typeof val.text === 'string') return val.text;
    if (typeof val.body === 'string') return val.body;
    if (typeof val.message === 'string') return val.message;
    if (val.content && typeof val.content === 'object') return safeExtractText(val.content);
    if (val.text && typeof val.text === 'object') return safeExtractText(val.text);
    try {
      return JSON.stringify(val);
    } catch (e) {
      return '';
    }
  }
  return String(val);
}

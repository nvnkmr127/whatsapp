import React from 'react';
import { View, Text, Modal, Pressable } from 'react-native';
import { useTokens } from '@/theme';

export interface DialogButton {
  text: string;
  onPress?: () => void;
  style?: 'default' | 'cancel' | 'destructive';
}

interface DialogProps {
  visible: boolean;
  title: string;
  message: string;
  buttons: DialogButton[];
  onClose: () => void;
}

export function CustomDialog({ visible, title, message, buttons, onClose }: DialogProps) {
  const { tokens } = useTokens();

  const handleButtonPress = (btn: DialogButton) => {
    onClose();
    if (btn.onPress) {
      setTimeout(() => {
        btn.onPress?.();
      }, 100);
    }
  };

  return (
    <Modal transparent visible={visible} animationType="fade" onRequestClose={onClose}>
      <Pressable onPress={onClose} className="flex-1 bg-black/40 items-center justify-center px-6">
        <Pressable onPress={() => {}} className="bg-surface dark:bg-d-surface w-full max-w-[310px] rounded-2xl p-5 shadow-xl gap-4">
          <View>
            <Text className="text-[16.5px] font-bold text-ink dark:text-d-ink mb-1.5 leading-snug">
              {title}
            </Text>
            <Text className="text-[13px] text-ink2 dark:text-d-ink2 leading-relaxed">
              {message}
            </Text>
          </View>

          <View className={buttons.length > 2 ? 'gap-2' : 'flex-row justify-end gap-2.5'}>
            {buttons.map((btn, idx) => {
              const isDestructive = btn.style === 'destructive';
              const isCancel = btn.style === 'cancel';

              let bgClass = 'bg-accent dark:bg-d-accent';
              let textClass = 'text-accent-ink dark:text-d-accent-ink';

              if (isDestructive) {
                bgClass = 'bg-danger dark:bg-d-danger';
                textClass = 'text-white';
              } else if (isCancel) {
                bgClass = 'bg-surface2 dark:bg-d-surface2';
                textClass = 'text-ink2 dark:text-d-ink2';
              }

              return (
                <Pressable
                  key={idx}
                  onPress={() => handleButtonPress(btn)}
                  className={`px-4 py-2.5 rounded-lg active:opacity-85 ${bgClass} ${
                    buttons.length === 2 ? 'flex-1' : ''
                  }`}
                >
                  <Text className={`text-[12.5px] font-bold text-center ${textClass}`}>
                    {btn.text}
                  </Text>
                </Pressable>
              );
            })}
          </View>
        </Pressable>
      </Pressable>
    </Modal>
  );
}

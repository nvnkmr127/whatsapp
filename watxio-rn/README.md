# Watxio — React Native

A WhatsApp Business inbox built with **React Native + TypeScript + NativeWind**. Ports the Sage design direction from the HTML prototype to a real native app.

## What's inside

- 9 fully-typed screens — Inbox, Chat, Contact, Broadcast composer, Templates, Analytics, Automations, Login/Onboarding, Settings
- Bottom-tab navigation (Inbox / Templates / Analytics / Flows / Me) + stack for Chat, Contact, Broadcast, Login
- Themed components: `Avatar`, `Bubble`, `Card`, `Chip`, `SectionLabel`, `Toggle`, `Spark`, `Composer`, primary / ghost / icon `Button`s
- One sage accent on neutral surfaces — palette mirrors the Tailwind config so SVG and React Navigation can read the same hex values
- Light + dark mode driven by system color scheme, with React Navigation theme synced

## Tech stack

| Layer | Choice |
| --- | --- |
| Framework | Expo SDK 51, React Native 0.74 |
| Language | TypeScript (strict) |
| Styling | NativeWind v4 (Tailwind for RN) + inline style props for dynamic tokens |
| Navigation | `@react-navigation/native` + `native-stack` + `bottom-tabs` |
| Icons | `lucide-react-native` |
| SVG | `react-native-svg` (sparklines, brand mark) |

## Run it

```bash
cd watxio-rn
npm install
npx expo start
```

Then press `i` for iOS simulator, `a` for Android, or scan the QR with Expo Go on your phone.

> **First-time setup** — if Metro complains about the NativeWind preset, run `npx expo install` once to make sure the right RN dep versions land.

## File map

```
watxio-rn/
├── App.tsx                       # root + safe-area + nav container
├── global.css                    # Tailwind directives (NativeWind input)
├── tailwind.config.js            # Sage palette + radii
├── babel.config.js               # nativewind/babel + reanimated plugin
├── metro.config.js               # withNativeWind wrapper
├── app.json                      # Expo config
├── tsconfig.json                 # @/ path alias
└── src/
    ├── theme/index.ts            # tokens + useTokens() hook
    ├── data.ts                   # sample fixtures
    ├── types.ts                  # shared TypeScript types
    ├── components/
    │   ├── Avatar.tsx
    │   ├── Bubble.tsx
    │   ├── Button.tsx            # Primary / Ghost / IconButton
    │   ├── Card.tsx
    │   ├── Chip.tsx
    │   ├── PhoneBubbleBar.tsx    # chat composer
    │   ├── SectionLabel.tsx
    │   ├── Spark.tsx             # SVG sparkline
    │   └── Toggle.tsx
    ├── navigation/AppNavigator.tsx
    └── screens/
        ├── InboxScreen.tsx
        ├── ChatScreen.tsx
        ├── ContactScreen.tsx
        ├── BroadcastScreen.tsx
        ├── TemplatesScreen.tsx
        ├── AnalyticsScreen.tsx
        ├── AutomationsScreen.tsx
        ├── LoginScreen.tsx
        └── SettingsScreen.tsx
```

## Theming notes

- `useTokens()` (in `src/theme/index.ts`) returns the active palette plus the scheme name. Read raw hex from this hook for `react-native-svg`, inline gradients, navigation themes, anything dynamic.
- The same tokens are mirrored in `tailwind.config.js` so you can use NativeWind classes for static colors: `<View className="bg-surface" />`. Dark mode classes use the `d-*` token names (e.g. `dark:bg-d-surface`).
- To force a theme (override the system), wrap the app in a context that produces a hardcoded `scheme` and feed it into `useTokens`.

## Wiring tips

- **Add a screen** → create `src/screens/<Name>Screen.tsx`, add to the `RootStackParamList` in `src/types.ts`, register in `AppNavigator.tsx`.
- **Replace dummy data** → everything in `src/data.ts` is plain TypeScript. Swap for a fetch hook, a Zustand store, React Query — your choice.
- **Real API** → wire the message send / template fetch / broadcast POST in the relevant screen handlers. The original HTML prototype mocks an OutboxService-style queue; the Flutter codebase in the design package shows the real endpoints.

## Known gaps & next steps

- **Real avatars** — currently hashed initials on a sage-tinted disc. Wire `Image` source when contact photos become available.
- **Persistent settings** — light/dark, density, etc. are currently driven by system scheme only. Add `AsyncStorage` to persist user overrides.
- **i18n** — copy is English-only. The data layer is ready for it; the strings need to move into a translation system.
- **Push** — `firebase_messaging` config and a deep-link handler are out of scope here. The original Flutter app's `NotificationService.navigationStream` is the pattern to mirror.

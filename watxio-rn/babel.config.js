module.exports = function (api) {
  api.cache(true);
  return {
    presets: [
      // NativeWind v4: jsxImportSource here is all that's needed.
      // Do NOT add 'nativewind/babel' as a second preset — it's the old v2 pattern
      // and causes duplicate transform passes with v4.
      ['babel-preset-expo', { jsxImportSource: 'nativewind' }],
    ],
    plugins: [
      'react-native-reanimated/plugin', // must be listed last
    ],
  };
};

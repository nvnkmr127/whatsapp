import 'package:share_plus/share_plus.dart';

void main() async {
  final params = ShareParams(
    text: 'test',
    files: [XFile('path')],
  );
  await SharePlus.instance.share(params);
}

import 'package:flutter/material.dart';
import 'package:shimmer/shimmer.dart';

class InboxShimmer extends StatelessWidget {
  const InboxShimmer({super.key});

  @override
  Widget build(BuildContext context) {
    return ListView.separated(
      itemCount: 10,
      separatorBuilder: (context, index) => const Divider(height: 1, indent: 80),
      itemBuilder: (context, index) {
        return Shimmer.fromColors(
          baseColor: Colors.grey[300]!,
          highlightColor: Colors.grey[100]!,
          child: ListTile(
            leading: const CircleAvatar(radius: 25, backgroundColor: Colors.white),
            title: Container(width: 100, height: 10, color: Colors.white),
            subtitle: Container(width: 200, height: 10, color: Colors.white),
          ),
        );
      },
    );
  }
}

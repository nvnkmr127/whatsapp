import 'package:flutter/material.dart';

class SimpleBarChart extends StatelessWidget {
  final List<double> values;
  final List<String> labels;
  final Color color;

  const SimpleBarChart({
    super.key,
    required this.values,
    required this.labels,
    this.color = const Color(0xFF128C7E),
  });

  @override
  Widget build(BuildContext context) {
    if (values.isEmpty) return const SizedBox();
    final double maxVal = values.reduce((a, b) => a > b ? a : b);

    return Container(
      height: 200,
      padding: const EdgeInsets.symmetric(vertical: 20),
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceEvenly,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: List.generate(values.length, (index) {
            final double percentage = maxVal == 0 ? 0 : values[index] / maxVal;
            return Padding(
              padding: const EdgeInsets.symmetric(horizontal: 12),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  Container(
                    width: 30,
                    height: 120 * percentage,
                    decoration: BoxDecoration(
                      color: color.withValues(alpha: 0.8),
                      borderRadius: const BorderRadius.vertical(top: Radius.circular(6)),
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    labels[index],
                    style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.grey),
                  ),
                ],
              ),
            );
          }),
        ),
      ),
    );
  }
}

import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';

import '../../../shared/theme/app_theme.dart';
import '../models/dashboard_summary_model.dart';

class IotDonutChart extends StatelessWidget {
  const IotDonutChart({super.key, required this.chart});

  final ChartData chart;

  static const _colors = [AppTheme.primary, Color(0xFFBA1A1A), Color(0xFFF59E0B)];

  @override
  Widget build(BuildContext context) {
    final total = chart.values.fold<double>(0, (a, b) => a + b);

    return Row(
      children: [
        SizedBox(
          width: 110,
          height: 110,
          child: PieChart(
            PieChartData(
              sectionsSpace: 2,
              centerSpaceRadius: 30,
              sections: [
                for (var i = 0; i < chart.values.length; i++)
                  PieChartSectionData(
                    value: chart.values[i] <= 0 ? 0.001 : chart.values[i],
                    color: _colors[i % _colors.length],
                    title: chart.values[i] > 0 ? chart.values[i].toInt().toString() : '',
                    radius: 22,
                    titleStyle: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white),
                  ),
              ],
            ),
          ),
        ),
        const SizedBox(width: 16),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              for (var i = 0; i < chart.labels.length; i++)
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 3),
                  child: Row(
                    children: [
                      Container(width: 10, height: 10, decoration: BoxDecoration(color: _colors[i % _colors.length], shape: BoxShape.circle)),
                      const SizedBox(width: 8),
                      Text('${chart.labels[i]}: ${chart.values[i].toInt()}', style: const TextStyle(fontSize: 13)),
                    ],
                  ),
                ),
              if (total == 0) const Text('Belum ada device.', style: TextStyle(fontSize: 12, color: AppTheme.textMuted)),
            ],
          ),
        ),
      ],
    );
  }
}

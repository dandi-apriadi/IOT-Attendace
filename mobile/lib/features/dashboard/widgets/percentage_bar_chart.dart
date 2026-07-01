import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';

import '../../../shared/theme/app_theme.dart';
import '../models/dashboard_summary_model.dart';

class PercentageBarChart extends StatelessWidget {
  const PercentageBarChart({super.key, required this.chart});

  final ChartData chart;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 170,
      child: BarChart(
        BarChartData(
          maxY: 100,
          minY: 0,
          gridData: FlGridData(
            show: true,
            drawVerticalLine: false,
            horizontalInterval: 25,
            getDrawingHorizontalLine: (_) => FlLine(color: AppTheme.navy.withValues(alpha: 0.06), strokeWidth: 1),
          ),
          borderData: FlBorderData(show: false),
          titlesData: FlTitlesData(
            leftTitles: AxisTitles(
              sideTitles: SideTitles(
                showTitles: true,
                reservedSize: 32,
                interval: 25,
                getTitlesWidget: (value, meta) => Text('${value.toInt()}%', style: const TextStyle(fontSize: 10, color: AppTheme.textMuted)),
              ),
            ),
            rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
            topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
            bottomTitles: AxisTitles(
              sideTitles: SideTitles(
                showTitles: true,
                reservedSize: 40,
                getTitlesWidget: (value, meta) {
                  final index = value.toInt();
                  if (index < 0 || index >= chart.labels.length) return const SizedBox.shrink();
                  final label = chart.labels[index];
                  return Padding(
                    padding: const EdgeInsets.only(top: 6),
                    child: Text(
                      label.length > 8 ? '${label.substring(0, 8)}…' : label,
                      style: const TextStyle(fontSize: 9, color: AppTheme.textMuted),
                    ),
                  );
                },
              ),
            ),
          ),
          barGroups: [
            for (var i = 0; i < chart.values.length; i++)
              BarChartGroupData(
                x: i,
                barRods: [
                  BarChartRodData(
                    toY: chart.values[i],
                    color: chart.values[i] >= 80
                        ? AppTheme.statusColor('Hadir')
                        : chart.values[i] >= 60
                            ? AppTheme.statusColor('Telat')
                            : AppTheme.statusColor('Alpa'),
                    width: 18,
                    borderRadius: const BorderRadius.vertical(top: Radius.circular(4)),
                  ),
                ],
              ),
          ],
        ),
      ),
    );
  }
}

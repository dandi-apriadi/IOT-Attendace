import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';

import '../../../shared/theme/app_theme.dart';
import '../models/dashboard_summary_model.dart';

class WeeklyLineChart extends StatelessWidget {
  const WeeklyLineChart({super.key, required this.chart});

  final ChartData chart;

  @override
  Widget build(BuildContext context) {
    final maxY = chart.values.isEmpty ? 5.0 : (chart.values.reduce((a, b) => a > b ? a : b) * 1.3).clamp(5, double.infinity);

    return SizedBox(
      height: 160,
      child: LineChart(
        LineChartData(
          minY: 0,
          maxY: maxY.toDouble(),
          gridData: FlGridData(
            show: true,
            drawVerticalLine: false,
            horizontalInterval: (maxY / 3).clamp(1, double.infinity).toDouble(),
            getDrawingHorizontalLine: (_) => FlLine(color: AppTheme.navy.withValues(alpha: 0.06), strokeWidth: 1),
          ),
          borderData: FlBorderData(show: false),
          titlesData: FlTitlesData(
            leftTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
            rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
            topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
            bottomTitles: AxisTitles(
              sideTitles: SideTitles(
                showTitles: true,
                reservedSize: 24,
                interval: 1,
                getTitlesWidget: (value, meta) {
                  final index = value.round();
                  if (index < 0 || index >= chart.labels.length || index != value) return const SizedBox.shrink();
                  return Padding(
                    padding: const EdgeInsets.only(top: 6),
                    child: Text(
                      chart.labels[index].substring(0, 3),
                      style: const TextStyle(fontSize: 10, color: AppTheme.textMuted),
                    ),
                  );
                },
              ),
            ),
          ),
          lineTouchData: const LineTouchData(enabled: true),
          lineBarsData: [
            LineChartBarData(
              spots: [
                for (var i = 0; i < chart.values.length; i++) FlSpot(i.toDouble(), chart.values[i]),
              ],
              isCurved: true,
              color: AppTheme.primary,
              barWidth: 3,
              dotData: const FlDotData(show: true),
              belowBarData: BarAreaData(show: true, color: AppTheme.primary.withValues(alpha: 0.12)),
            ),
          ],
        ),
      ),
    );
  }
}

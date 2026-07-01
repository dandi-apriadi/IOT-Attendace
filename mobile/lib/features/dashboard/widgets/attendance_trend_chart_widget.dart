import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';

import '../../../shared/theme/app_theme.dart';

/// Line chart for date-labeled series. Unlike [WeeklyLineChart] (always 7
/// fixed day-name points), this handles an arbitrary, user-filtered date
/// range and thins out the x-axis labels so they don't overlap.
class AttendanceTrendChartWidget extends StatelessWidget {
  const AttendanceTrendChartWidget({super.key, required this.labels, required this.values});

  final List<String> labels;
  final List<double> values;

  @override
  Widget build(BuildContext context) {
    if (values.isEmpty) {
      return const SizedBox(height: 160, child: Center(child: Text('Tidak ada data pada rentang ini.')));
    }

    final maxY = (values.reduce((a, b) => a > b ? a : b) * 1.3).clamp(5, double.infinity).toDouble();
    // Show at most ~6 labels regardless of how many days are in range.
    final labelInterval = (labels.length / 6).ceil().clamp(1, labels.length);

    return SizedBox(
      height: 180,
      child: LineChart(
        LineChartData(
          minY: 0,
          maxY: maxY,
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
                  if (index < 0 || index >= labels.length || index != value) return const SizedBox.shrink();
                  if (index % labelInterval != 0 && index != labels.length - 1) return const SizedBox.shrink();
                  return Padding(
                    padding: const EdgeInsets.only(top: 6),
                    child: Text(labels[index], style: const TextStyle(fontSize: 9.5, color: AppTheme.textMuted)),
                  );
                },
              ),
            ),
          ),
          lineTouchData: LineTouchData(
            enabled: true,
            touchTooltipData: LineTouchTooltipData(
              getTooltipItems: (spots) => spots.map((spot) {
                final idx = spot.x.toInt();
                final label = idx >= 0 && idx < labels.length ? labels[idx] : '';
                return LineTooltipItem('$label\n${spot.y.toInt()}', const TextStyle(color: Colors.white, fontSize: 12));
              }).toList(),
            ),
          ),
          lineBarsData: [
            LineChartBarData(
              spots: [for (var i = 0; i < values.length; i++) FlSpot(i.toDouble(), values[i])],
              isCurved: true,
              color: AppTheme.primary,
              barWidth: 3,
              dotData: FlDotData(show: values.length <= 20),
              belowBarData: BarAreaData(show: true, color: AppTheme.primary.withValues(alpha: 0.12)),
            ),
          ],
        ),
      ),
    );
  }
}

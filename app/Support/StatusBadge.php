<?php

namespace App\Support;

class StatusBadge
{
    /**
     * @return array{bg:string,text:string}
     */
    public static function forApproval(string $status): array
    {
        return match (strtolower($status)) {
            'pending' => ['bg' => '#FEF3C7', 'text' => '#F59E0B'],
            'approved' => ['bg' => '#E0E7FF', 'text' => '#0066CC'],
            'rejected' => ['bg' => '#FADBD8', 'text' => '#BA1A1A'],
            default => ['bg' => '#F1F3F5', 'text' => '#6b7280'],
        };
    }

    /**
     * @return array{bg:string,text:string}
     */
    public static function forAbsensi(string $status): array
    {
        return match (strtolower($status)) {
            'pending' => ['bg' => '#F1F3F5', 'text' => '#6b7280'],
            'telat' => ['bg' => '#FEF3C7', 'text' => '#F59E0B'],
            'alpa' => ['bg' => '#FADBD8', 'text' => '#BA1A1A'],
            default => ['bg' => '#E6F6EC', 'text' => '#1DB173'],
        };
    }

    /**
     * @return array<string,array{bg:string,text:string}>
     */
    public static function absensiMap(): array
    {
        return [
            'pending' => self::forAbsensi('pending'),
            'telat' => self::forAbsensi('telat'),
            'alpa' => self::forAbsensi('alpa'),
            'default' => self::forAbsensi('hadir'),
        ];
    }
}
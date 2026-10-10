<?php

namespace App\Enums;

// Sumber tunggal daftar divisi (karyawan, training, invoice).
enum Division: string
{
    case GasAnalyzer = 'Gas Analyzer';
    case IcPmr = 'I&C-PMR';
    case IcEr = 'I&C-ER';
    case Dryer = 'Dryer';
    case Safety = 'Safety';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

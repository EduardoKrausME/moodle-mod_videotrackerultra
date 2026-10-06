<?php
// This file is part of Moodle - http://moodle.org/.

namespace mod_videotrackerultra;

use invalid_parameter_exception;

/**
 * Required segment parsing and formatting helpers.
 *
 * @package   mod_videotrackerultra
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class segments {
    /**
     * Parses one segment per line using MM:SS-MM:SS, HH:MM:SS-HH:MM:SS or raw seconds.
     *
     * @param string $text Teacher-entered segment list.
     * @return array
     */
    public static function parse(string $text): array {
        $result = [];
        $lines = preg_split('/\R+/', trim($text)) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (!preg_match('/^\s*([0-9:]+)\s*[-–—]\s*([0-9:]+)\s*$/u', $line, $matches)) {
                throw new invalid_parameter_exception('Invalid required segment: ' . $line);
            }
            $start = self::time_to_seconds($matches[1]);
            $end = self::time_to_seconds($matches[2]);
            if ($end <= $start) {
                throw new invalid_parameter_exception('Segment end must be after its start: ' . $line);
            }
            $result[] = [
                'start' => $start,
                'end' => $end,
                'label' => self::format($start) . '–' . self::format($end),
            ];
        }
        return $result;
    }

    /**
     * Converts a timecode to seconds.
     *
     * @param string $value Timecode.
     * @return int
     */
    public static function time_to_seconds(string $value): int {
        $parts = array_map('intval', explode(':', trim($value)));
        if (count($parts) === 1) {
            return max(0, $parts[0]);
        }
        if (count($parts) === 2) {
            return max(0, $parts[0] * 60 + $parts[1]);
        }
        if (count($parts) === 3) {
            return max(0, $parts[0] * 3600 + $parts[1] * 60 + $parts[2]);
        }
        throw new invalid_parameter_exception('Invalid timecode: ' . $value);
    }

    /**
     * Formats seconds as a human-readable timecode.
     *
     * @param float $seconds Seconds.
     * @return string
     */
    public static function format(float $seconds): string {
        $seconds = max(0, (int)round($seconds));
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remaining = $seconds % 60;
        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $remaining);
        }
        return sprintf('%02d:%02d', $minutes, $remaining);
    }

    /**
     * Decodes stored segments.
     *
     * @param string|null $json Stored JSON.
     * @return array
     */
    public static function decode(?string $json): array {
        $decoded = json_decode((string)$json, true);
        return is_array($decoded) ? array_values($decoded) : [];
    }

    /**
     * Converts stored segments back to the activity form.
     *
     * @param string|null $json Stored JSON.
     * @return string
     */
    public static function to_text(?string $json): string {
        $lines = [];
        foreach (self::decode($json) as $segment) {
            if (isset($segment['start'], $segment['end'])) {
                $lines[] = self::format((float)$segment['start']) . '-' . self::format((float)$segment['end']);
            }
        }
        return implode("\n", $lines);
    }
}

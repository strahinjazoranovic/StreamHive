<?php

if (!function_exists('formatVideoDuration')) {
    function formatVideoDuration($durationSeconds): string
    {
        $safeDurationSeconds = is_numeric($durationSeconds)
            ? max(0, (int) $durationSeconds)
            : 0;

        if ($safeDurationSeconds >= 3600) {
            $hours = (int) floor($safeDurationSeconds / 3600);
            $minutes = (int) floor(($safeDurationSeconds % 3600) / 60);
            $seconds = $safeDurationSeconds % 60;

            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        $minutes = (int) floor($safeDurationSeconds / 60);
        $seconds = $safeDurationSeconds % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}

<?php

// Only define the function if it doesn't already exist.
if (!function_exists('formatVideoDuration')) {
    // Format a duration in seconds into MM:SS or H:MM:SS.
    function formatVideoDuration($durationSeconds): string
    {
        // Ensure the value is numeric and not negative.
        $safeDurationSeconds = is_numeric($durationSeconds)
            ? max(0, (int) $durationSeconds)
            : 0;

        // Use hour format when duration is 1 hour or more.
        if ($safeDurationSeconds >= 3600) {

            // Calculate hours.
            $hours = (int) floor($safeDurationSeconds / 3600);

            // Calculate remaining minutes.
            $minutes = (int) floor(($safeDurationSeconds % 3600) / 60);

            // Calculate remaining seconds.
            $seconds = $safeDurationSeconds % 60;

            // Return formatted H:MM:SS string.
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        // Calculate minutes.
        $minutes = (int) floor($safeDurationSeconds / 60);

        // Calculate remaining seconds.
        $seconds = $safeDurationSeconds % 60;

        // Return formatted MM:SS string.
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}
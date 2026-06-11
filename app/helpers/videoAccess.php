<?php

// Only run through this function if it hasn't already been defined, this is for every function in this file
if (!function_exists('normalizeVideoVisibilityValue')) {
    // Normalize visbility 
    function normalizeVideoVisibilityValue($visibility): string
    {
        // Stringify the visibility variable and remove any whitespaces
        $normalizedVisibility = strtolower(trim((string) $visibility));

        // If it is null or no visibility return public
        if ($normalizedVisibility === '') {
            return 'public';
        }

        // Return the value
        return $normalizedVisibility;
    }
}

if (!function_exists('getVideoAccessSecretKey')) {
    // Returns the secret key used to generate/verify unlisted access tokens.
    function getVideoAccessSecretKey(): string
    {
        // Keep the variable to null everytime the function is being run using static
        static $cachedSecretKey = null;

        // If an key already exists return the variable
        if (is_string($cachedSecretKey) && $cachedSecretKey !== '') {
            return $cachedSecretKey;
        }

        $envValues = [];
        $envFilePath = __DIR__ . '/../../.env.local';

        if (is_file($envFilePath)) {
            $parsedEnvValues = parse_ini_file($envFilePath, false, INI_SCANNER_RAW);

            if (is_array($parsedEnvValues)) {
                $envValues = $parsedEnvValues;
            }
        }

        $secretCandidates = [
            $envValues['VIDEO_SECRET_CODE'] ?? null,
        ];

        // For each secret candidate stringify and remove any whitespaces
        foreach ($secretCandidates as $secretCandidate) {
            $normalizedSecretCandidate = trim((string) $secretCandidate);

            // If it is valid store it and return that value
            if ($normalizedSecretCandidate !== '') {
                $cachedSecretKey = $normalizedSecretCandidate;
                return $cachedSecretKey;
            }
        }

        // If no key exists create a new has based on the file name, php version and os system info with sha256
        $cachedSecretKey = hash(
            'sha256',
            __FILE__ . '|' . PHP_VERSION . '|' . (string) php_uname(),
        );

        // Return fallback key
        return $cachedSecretKey;
    }
}


if (!function_exists('buildUnlistedVideoAccessToken')) {
    // Builds a deterministic access token for unlisted videos.
    function buildUnlistedVideoAccessToken(array $video): ?string
    {
        $videoId = (int) ($video['id'] ?? 0);

        if ($videoId <= 0) {
            return null;
        }

        $videoVisibility = normalizeVideoVisibilityValue($video['visibilty'] ?? null);

        if ($videoVisibility !== 'unlisted') {
            return null;
        }

        $videoFilename = trim((string) ($video['filename'] ?? ''));
        $videoCreatedAt = trim((string) ($video['created_at'] ?? ''));
        $videoOwnerId = (int) ($video['user_id'] ?? 0);
        $tokenPayload = $videoId . '|' . $videoOwnerId . '|' . $videoFilename . '|' . $videoCreatedAt;

        return hash_hmac('sha256', $tokenPayload, getVideoAccessSecretKey());
    }
}


if (!function_exists('isValidUnlistedVideoAccessToken')) {
    // Validates an incoming unlisted token against the expected server-side token.
    function isValidUnlistedVideoAccessToken(array $video, ?string $providedToken): bool
    {
        $expectedToken = buildUnlistedVideoAccessToken($video);

        if ($expectedToken === null) {
            return false;
        }

        $normalizedProvidedToken = strtolower(trim((string) $providedToken));

        if ($normalizedProvidedToken === '') {
            return false;
        }

        return hash_equals($expectedToken, $normalizedProvidedToken);
    }
}

// Builds the canonical watch URL for a video, appending a token for unlisted videos.
if (!function_exists('buildVideoWatchPath')) {
    function buildVideoWatchPath(string $basePath, array $video): string
    {
        $videoId = (int) ($video['id'] ?? 0);

        if ($videoId <= 0) {
            return rtrim($basePath, '/') . '/index.php?route=error&type=not_found';
        }

        $videoPath = rtrim($basePath, '/') . '/index.php?route=video&id=' . $videoId;
        $accessToken = buildUnlistedVideoAccessToken($video);

        if ($accessToken !== null) {
            $videoPath .= '&token=' . rawurlencode($accessToken);
        }

        return $videoPath;
    }
}

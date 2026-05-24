<?php

require_once __DIR__ . '/../app/models/video.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = ($scriptDir === '/' || $scriptDir === '.')
    ? ''
    : rtrim($scriptDir, '/');

// Redirect to admin page function
$redirectToAdmin = static function (string $status) use ($basePath): void {
    header('Location: ' . $basePath . '/index.php?route=admin&upload=' . urlencode($status));
    exit;
};

$iniSizeToBytes = static function (string $value): int {
    $trimmedValue = trim($value);

    if ($trimmedValue === '') {
        return 0;
    }

    $unit = strtolower(substr($trimmedValue, -1));
    $number = (float) $trimmedValue;

    switch ($unit) {
        case 'g':
            $number *= 1024;
            // no break
        case 'm':
            $number *= 1024;
            // no break
        case 'k':
            $number *= 1024;
            break;
    }

    return (int) $number;
};

$resolveFfprobeBinary = static function (): ?string {
    $candidateValues = [];
    $envKeys = ['FFPROBE_PATH', 'FFPROBE_BINARY'];

    foreach ($envKeys as $envKey) {
        $runtimeValue = getenv($envKey);
        if (is_string($runtimeValue) && trim($runtimeValue) !== '') {
            $candidateValues[] = trim($runtimeValue);
        }

        $serverValue = $_SERVER[$envKey] ?? null;
        if (is_string($serverValue) && trim($serverValue) !== '') {
            $candidateValues[] = trim($serverValue);
        }

        $envValue = $_ENV[$envKey] ?? null;
        if (is_string($envValue) && trim($envValue) !== '') {
            $candidateValues[] = trim($envValue);
        }
    }

    $envFilePath = __DIR__ . '/../.env.local';
    if (is_file($envFilePath)) {
        $envFileValues = parse_ini_file($envFilePath, false, INI_SCANNER_RAW);
        if (is_array($envFileValues)) {
            foreach ($envKeys as $envKey) {
                $envFileValue = $envFileValues[$envKey] ?? null;
                if (is_string($envFileValue) && trim($envFileValue) !== '') {
                    $candidateValues[] = trim($envFileValue);
                }
            }
        }
    }

    $binaryFilename = DIRECTORY_SEPARATOR === '\\' ? 'ffprobe.exe' : 'ffprobe';

    foreach ($candidateValues as $candidateValue) {
        $normalizedCandidate = trim($candidateValue, " \t\n\r\0\x0B\"'");
        if ($normalizedCandidate === '') {
            continue;
        }

        if (is_dir($normalizedCandidate)) {
            $possibleBinaryPath = rtrim($normalizedCandidate, '\\/') . DIRECTORY_SEPARATOR . $binaryFilename;
            if (is_file($possibleBinaryPath)) {
                return $possibleBinaryPath;
            }

            continue;
        }

        if (is_file($normalizedCandidate)) {
            return $normalizedCandidate;
        }
    }

    return null;
};

// Extract video duration in seconds using ffprobe
$extractVideoDurationSeconds = static function (string $videoPath) use ($resolveFfprobeBinary): ?int {
    if (!is_file($videoPath) || !function_exists('shell_exec')) {
        return null;
    }

    $escapedVideoPath = escapeshellarg($videoPath);
    $probeCommands = [];
    $configuredBinaryPath = $resolveFfprobeBinary();

    if ($configuredBinaryPath !== null) {
        $probeCommands[] = escapeshellarg($configuredBinaryPath) . ' -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ' . $escapedVideoPath . ' 2>&1';
    }

    $probeCommands[] = 'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ' . $escapedVideoPath . ' 2>&1';
    $probeCommands[] = 'ffprobe.exe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ' . $escapedVideoPath . ' 2>&1';

    foreach ($probeCommands as $probeCommand) {
        $rawDuration = shell_exec($probeCommand);

        if (!is_string($rawDuration)) {
            continue;
        }

        $durationValue = trim($rawDuration);

        if ($durationValue === '' || !is_numeric($durationValue)) {
            continue;
        }

        return max(0, (int) round((float) $durationValue));
    }

    return null;
};

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $redirectToAdmin('invalid_request');
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $basePath . '/index.php?route=login');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ' . $basePath . '/index.php?route=home');
    exit;
}
// If POST body exceeds post_max_size, PHP may drop all $_POST/$_FILES values.
$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
$postMaxBytes = $iniSizeToBytes((string) ini_get('post_max_size'));
if ($contentLength > 0 && $postMaxBytes > 0 && $contentLength > $postMaxBytes) {
    $redirectToAdmin('file_too_large');
}

if (!isset($_FILES['fileToUpload'])) {
    $redirectToAdmin('missing_file');
}

if (!isset($_FILES['thumbnailToUpload'])) {
    $redirectToAdmin('missing_thumbnail');
}

$uploadError = (int) ($_FILES['fileToUpload']['error'] ?? UPLOAD_ERR_NO_FILE);
if ($uploadError !== UPLOAD_ERR_OK) {
    if ($uploadError === UPLOAD_ERR_NO_FILE) {
        $redirectToAdmin('missing_file');
    }

    if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
        $redirectToAdmin('file_too_large');
    }

    $redirectToAdmin('upload_failed');
}

$thumbnailUploadError = (int) ($_FILES['thumbnailToUpload']['error'] ?? UPLOAD_ERR_NO_FILE);
if ($thumbnailUploadError !== UPLOAD_ERR_OK) {
    if ($thumbnailUploadError === UPLOAD_ERR_NO_FILE) {
        $redirectToAdmin('missing_thumbnail');
    }

    if ($thumbnailUploadError === UPLOAD_ERR_INI_SIZE || $thumbnailUploadError === UPLOAD_ERR_FORM_SIZE) {
        $redirectToAdmin('thumbnail_too_large');
    }

    $redirectToAdmin('thumbnail_upload_failed');
}

$videoTitle = trim((string)($_POST['videoTitle'] ?? ''));
$videoDescription = trim((string)($_POST['videoDescription'] ?? ''));
$videoCategoryRaw = trim((string)($_POST['videoCategory'] ?? ''));
$videoVisibilityRaw = trim((string)($_POST['videoVisibility'] ?? ''));

// If the videoTitle is empty redirect to admin and show missing_title error
// you can find all the other upload messages type in \app\controllers\viewController under the admin function
if ($videoTitle === '') {
    $redirectToAdmin('missing_title');
}

// Set category id to null
$categoryId = null;

// If the user selected no videoCategory show invalid_category
if ($videoCategoryRaw !== '') {
    if (!ctype_digit($videoCategoryRaw)) {
        $redirectToAdmin('invalid_category');
    }

    $categoryId = (int) $videoCategoryRaw;
}

$videoModel = new Video();
$visibilityOptions = $videoModel->getVisibilityOptions();

if ($videoVisibilityRaw === '' || !in_array($videoVisibilityRaw, $visibilityOptions, true)) {
    $redirectToAdmin('invalid_visibility');
}

// Maximize the filezie to 500MB and if it exceeds show file_too_large
$fileSize = (int) ($_FILES['fileToUpload']['size'] ?? 0);
if ($fileSize > 500000000) {
    $redirectToAdmin('file_too_large');
}

// If there is no file uploaded show missing_file
$originalName = basename((string) ($_FILES['fileToUpload']['name'] ?? ''));
if ($originalName === '') {
    $redirectToAdmin('missing_file');
}

$thumbnailOriginalName = basename((string) ($_FILES['thumbnailToUpload']['name'] ?? ''));
if ($thumbnailOriginalName === '') {
    $redirectToAdmin('missing_thumbnail');
}

$videoFileType = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$thumbnailFileType = strtolower(pathinfo($thumbnailOriginalName, PATHINFO_EXTENSION));

// Allowed types for uploading an video
$allowedTypes = [
    'mp4',
    'webm',
    'ogg',
    'mov',
    'avi',
    'mkv',
    'flv',
    'wmv',
    'm4v',
];

// If the user uploaded an file type that is not in the array show invalid_type
if (!in_array($videoFileType, $allowedTypes, true)) {
    $redirectToAdmin('invalid_type');
}

// Allowed types for uploading an thumbnail
$allowedThumbnailTypes = [
    'jpg',
    'jpeg',
    'png',
    'webp',
    'gif',
];

// If the file size exceeds 500MB show file_too_large error
$thumbnailSize = (int) ($_FILES['thumbnailToUpload']['size'] ?? 0);
if ($thumbnailSize > 5000000) {
    $redirectToAdmin('thumbnail_too_large');
}

// If the user uploaded an file type that is not in the array off allowed types show invalid_thumbnail_type
if (!in_array($thumbnailFileType, $allowedThumbnailTypes, true)) {
    $redirectToAdmin('invalid_thumbnail_type');
}

$videoTempFilePath = (string) ($_FILES['fileToUpload']['tmp_name'] ?? '');
if ($videoTempFilePath === '') {
    $redirectToAdmin('upload_failed');
}

$durationSeconds = $extractVideoDurationSeconds($videoTempFilePath);
if ($durationSeconds === null) {
    $redirectToAdmin('duration_extraction_failed');
}

$fileNameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);
$sanitizedBaseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fileNameWithoutExtension);

if ($sanitizedBaseName === null || $sanitizedBaseName === '') {
    $sanitizedBaseName = 'video';
}

$storedFilename = $sanitizedBaseName . '_' . uniqid('', true) . '.' . $videoFileType;
$thumbnailNameWithoutExtension = pathinfo($thumbnailOriginalName, PATHINFO_FILENAME);
$sanitizedThumbnailBaseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $thumbnailNameWithoutExtension);

if ($sanitizedThumbnailBaseName === null || $sanitizedThumbnailBaseName === '') {
    $sanitizedThumbnailBaseName = 'thumbnail';
}

$storedThumbnailFilename = $sanitizedThumbnailBaseName . '_' . uniqid('', true) . '.' . $thumbnailFileType;
$videoTargetDir = __DIR__ . '/uploads/';
$videoTargetFile = $videoTargetDir . $storedFilename;
$thumbnailTargetDir = __DIR__ . '/uploads/thumbnails/';
$thumbnailTargetFile = $thumbnailTargetDir . $storedThumbnailFilename;

if (!is_dir($videoTargetDir) && !mkdir($videoTargetDir, 0777, true) && !is_dir($videoTargetDir)) {
    $redirectToAdmin('upload_failed');
}
if (!is_dir($thumbnailTargetDir) && !mkdir($thumbnailTargetDir, 0777, true) && !is_dir($thumbnailTargetDir)) {
    $redirectToAdmin('thumbnail_upload_failed');
}

// If that file already exists in the storage show file_exists
if (file_exists($videoTargetFile)) {
    $redirectToAdmin('file_exists');
}
if (file_exists($thumbnailTargetFile)) {
    $redirectToAdmin('file_exists');
}

// If the file failed to upload show upload_failed
if (!move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $videoTargetFile)) {
    $redirectToAdmin('upload_failed');
}
if (!move_uploaded_file($_FILES['thumbnailToUpload']['tmp_name'], $thumbnailTargetFile)) {
    if (file_exists($videoTargetFile)) {
        unlink($videoTargetFile);
    }

    $redirectToAdmin('thumbnail_upload_failed');
}

// Create a new video object with createVideo function(which is defined in model video)
$videoInserted = $videoModel->createVideo(
    (int) $_SESSION['user_id'],
    $videoTitle,
    $videoDescription === '' ? null : $videoDescription,
    $storedFilename,
    $categoryId,
    $videoVisibilityRaw,
    $durationSeconds,
    $storedThumbnailFilename
);

if (!$videoInserted) {
    if (file_exists($videoTargetFile)) {
        unlink($videoTargetFile);
    }

    if (file_exists($thumbnailTargetFile)) {
        unlink($thumbnailTargetFile);
    }

    $redirectToAdmin('database_error');
}

$redirectToAdmin('success');
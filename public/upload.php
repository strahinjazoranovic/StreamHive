<?php
//
// you can find all upload messages type in \app\controllers\viewController under the admin function
//

require_once __DIR__ . '/../app/models/video.php';
require_once __DIR__ . '/../app/models/comment.php';

// If there is no session found start an session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define scriptDir and basePath
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = ($scriptDir === '/' || $scriptDir === '.')
    ? ''
    : rtrim($scriptDir, '/');

// Handle comment submission
if (
    strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' &&
    isset($_POST['submit']) &&
    !isset($_FILES['fileToUpload'])
) {

    // If the user is not logged in, redirect to login page
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . $basePath . '/index.php?route=login');
        exit;
    }

    // Define parameters
    $videoId = (int) ($_POST['videoId'] ?? 0);
    $userId = (int) ($_SESSION['user_id']);
    $commentText = trim((string) ($_POST['comment'] ?? ''));

    // If comment is empty return to the video where the uesr is located without inserting
    if ($commentText === '') {
        header('Location: ' . $basePath . '/index.php?route=video&id=' . $videoId);
        exit;
    }

    // Create a new comment instance
    $commentModel = new Comment();

    // use the createComment function and create an new comment with the following parameters
    $commentInserted = $commentModel->createComment(
        $videoId,
        $userId,
        $commentText
    );
}

// Redirect to admin page function
$redirectToAdmin = static function (string $status) use ($basePath): void {
    header('Location: ' . $basePath . '/index.php?route=admin&upload=' . urlencode($status));
    exit;
};

// Converts a php ini size value to bytes.
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

// FFProbe binary resolver function
$resolveFfprobeBinary = static function (): ?string {
    $candidateValues = [];
    $envKeys = ['FFPROBE_PATH'];

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

// If the request method is get and not post redirect to admin with invalid request
if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $redirectToAdmin('invalid_request');
}

// If the user_id is not found in session redirect the user to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $basePath . '/index.php?route=login');
    exit;
}

// Check if current user is admin and if not send him to home
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ' . $basePath . '/index.php?route=home');
    exit;
}

// If POST body exceeds post_max_size(500MB), PHP may drop all $_POST/$_FILES values.
$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
$postMaxBytes = $iniSizeToBytes((string) ini_get('post_max_size'));
if ($contentLength > 0 && $postMaxBytes > 0 && $contentLength > $postMaxBytes) {
    $redirectToAdmin('file_too_large');
}

// If no file is uploaded redirect to admin with missing file
if (!isset($_FILES['fileToUpload'])) {
    $redirectToAdmin('missing_file');
}

// If no thumbnail is uploaded redirect to admin with missing file
if (!isset($_FILES['thumbnailToUpload'])) {
    $redirectToAdmin('missing_thumbnail');
}

// Handle upload validation for file
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

// Handle upload validation for thumbnail
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

// Map video array that gets posted
$videoTitle = trim((string)($_POST['videoTitle'] ?? ''));
$videoDescription = trim((string)($_POST['videoDescription'] ?? ''));
$videoCategoryRaw = trim((string)($_POST['videoCategory'] ?? ''));
$videoVisibilityRaw = trim((string)($_POST['videoVisibility'] ?? ''));

// If the videoTitle is empty redirect to admin and show missing_title error
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

// Create a new video instance
$videoModel = new Video();

// Fetch visibility options with the function made in the video model
$visibilityOptions = $videoModel->getVisibilityOptions();

// Redirect if visibility is empty or not in the allowed options list
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

// Ensure a thumbnail filename was provided before proceeding
$thumbnailOriginalName = basename((string) ($_FILES['thumbnailToUpload']['name'] ?? ''));
if ($thumbnailOriginalName === '') {
    $redirectToAdmin('missing_thumbnail');
}

// Extract and normalize file extensions for video and thumbnail uploads
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

// If the user uploaded an file type that is not in the array of allowed types show invalid_thumbnail_type
if (!in_array($thumbnailFileType, $allowedThumbnailTypes, true)) {
    $redirectToAdmin('invalid_thumbnail_type');
}

// Ensure a temporary uploaded video file exists before processing
$videoTempFilePath = (string) ($_FILES['fileToUpload']['tmp_name'] ?? '');
if ($videoTempFilePath === '') {
    $redirectToAdmin('upload_failed');
}

// If the duration of the video is null show duration_extraction_failed error
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

// If that video file already exists in the storage show file_exists
if (file_exists($videoTargetFile)) {
    $redirectToAdmin('file_exists');
}

// If that thumbnail file already exists in the storage show file_exists
if (file_exists($thumbnailTargetFile)) {
    $redirectToAdmin('file_exists');
}

// If the file failed to upload show upload_failed
if (!move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $videoTargetFile)) {
    $redirectToAdmin('upload_failed');
}

// If the thumbnail failed to upload show upload_failed
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

// Roll back uploaded files if database insert fails and then redirect with error
if (!$videoInserted) {
    if (file_exists($videoTargetFile)) {
        unlink($videoTargetFile);
    }

    if (file_exists($thumbnailTargetFile)) {
        unlink($thumbnailTargetFile);
    }

    $redirectToAdmin('database_error');
}

// If everything excuted correctly show succes message
$redirectToAdmin('success');
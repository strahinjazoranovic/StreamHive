<?php
require_once __DIR__ . '/controller.php';
require_once __DIR__ . '/../models/video.php';
require_once __DIR__ . '/../models/comment.php';
require_once __DIR__ . '/../models/category.php';
require_once __DIR__ . '/../models/like.php';
require_once __DIR__ . '/../models/user.php';

class viewController extends Controller
{
    private function ensureSessionStarted()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    public function index()
    {
        $this->ensureSessionStarted();
        $videoModel = new Video();
        $videos = $videoModel->getAllVideos();
        $isLoggedIn = isset($_SESSION['user_id']);

        $this->render('home/index', [
            'basePath' => $this->getBasePath(),
            'videos' => $videos,
            'isLoggedIn' => $isLoggedIn,
        ]);
    }

    public function video()
    {
        $this->ensureSessionStarted();
        $isLoggedIn = isset($_SESSION['user_id']);
        $videoId = (int) ($_GET['id'] ?? 0);

        if ($videoId <= 0) {
            header('Location: ' . $this->getBasePath() . '/index.php?route=home');
            exit;
        }

        $videoModel = new Video();
        $video = $videoModel->getVideoByIdForShare($videoId);

        if ($video === null) {
            header('Location: ' . $this->getBasePath() . '/index.php?route=home');
            exit;
        }

        // If the view was incremented update the video array by +1
        $viewWasIncremented = $videoModel->incrementVideoViewsByIdForShare($videoId);
        if ($viewWasIncremented) {
            $video['views'] = (int) ($video['views'] ?? 0) + 1;
        }

        $sidebarVideos = array_values(array_filter(
            $videoModel->getAllVideos(),
            static function ($listedVideo) use ($videoId): bool {
                return (int) ($listedVideo['id'] ?? 0) !== $videoId;
            }
        ));

        $commentModel = new Comment();
        $comments = $commentModel->fetchComments($videoId, $isLoggedIn ? (int) $_SESSION['user_id'] : 0);
        $userReactionType = null;

        if ($isLoggedIn) {
            $likeModel = new Like();
            $userReactionType = $likeModel->getUserVideoReaction($videoId, (int) $_SESSION['user_id']);
        }

        $this->render('home/video', [
            'basePath' => $this->getBasePath(),
            'comments' => $comments,
            'video' => $video,
            'sidebarVideos' => $sidebarVideos,
            'isLoggedIn' => $isLoggedIn,
            'userReactionType' => $userReactionType,
        ]);
    }

    public function admin()
    {
        $this->ensureSessionStarted();
        $isLoggedIn = isset($_SESSION['user_id']);

        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $this->getBasePath() . '/index.php?route=login');
            exit;
        }

        if (($_SESSION['role'] ?? '') !== 'admin') {
            header('Location: ' . $this->getBasePath() . '/index.php?route=home');
            exit;
        }

        $videoModel = new Video();
        $uploadedVideos = $videoModel->getVideosByUserId((int) $_SESSION['user_id']);
        $visibilityOptions = $videoModel->getVisibilityOptions();

        $categoryModel = new category();
        $categories = $categoryModel->getAllCategories();

        $uploadStatus = trim((string)($_GET['upload'] ?? ''));

        // All the upload messages for the admin
        $uploadMessages = [
            'success' => 'Video uploaded successfully.',
            'updated' => 'Video updated successfully.',
            'deleted' => 'Video deleted successfully.',
            'missing_file' => 'Please choose a video file first.',
            'missing_thumbnail' => 'Please choose a thumbnail image.',
            'file_exists' => 'A video with this filename already exists.',
            'file_too_large' => 'Your file is too large. Keep it under 500MB and within your server upload limit.',
            'invalid_type' => 'Only video formats are allowed.',
            'invalid_thumbnail_type' => 'Only image formats are allowed for thumbnails.',
            'thumbnail_too_large' => 'Thumbnail is too large. Keep it under 5MB.',
            'thumbnail_upload_failed' => 'Thumbnail upload failed due to a server error.',
            'duration_extraction_failed' => 'Unable to read video duration.',
            'missing_title' => 'Please enter a title for the video.',
            'invalid_category' => 'Selected category is invalid.',
            'invalid_visibility' => 'Selected visibility is invalid.',
            'invalid_video' => 'The selected video could not be found.',
            'update_failed' => 'Updating the video failed.',
            'delete_failed' => 'Deleting the video failed.',
            'delete_file_failed' => 'Video record deleted, but removing the uploaded file failed.',
            'delete_thumbnail_file_failed' => 'Video updated or deleted, but removing the thumbnail file failed.',
            'update_thumbnail_cleanup_failed' => 'Video updated, but removing the previous thumbnail file failed.',
            'upload_failed' => 'Upload failed due to a server error.',
            'database_error' => 'Video was uploaded, but saving to the database failed.',
            'unauthorized' => 'Please log in to upload videos.',
            'invalid_request' => 'Invalid request method.',
        ];
        $uploadMessage = $uploadMessages[$uploadStatus] ?? '';
        $successStatuses = ['success', 'updated', 'deleted'];
        $uploadMessageType = in_array($uploadStatus, $successStatuses, true)
            ? 'success'
            : ($uploadMessage !== '' ? 'error' : '');

        $commentModel = new Comment();
        $videoIds = array_map(
            static function (array $video): int {
                return (int) ($video['id'] ?? 0);
            },
            $uploadedVideos
        );
        $comments = $commentModel->fetchCommentCountsByVideoIds($videoIds);

        $this->render('home/admin', [
            'basePath' => $this->getBasePath(),
            'isLoggedIn' => $isLoggedIn,
            'comments' => $comments,
            'uploadedVideos' => $uploadedVideos,
            'categories' => $categories,
            'visibilityOptions' => $visibilityOptions,
            'uploadMessage' => $uploadMessage,
            'uploadMessageType' => $uploadMessageType,
        ]);
    }

    public function manageVideo()
    {
        $this->ensureSessionStarted();

        $redirectToAdmin = function (string $status): void {
            header('Location: ' . $this->getBasePath() . '/index.php?route=admin&upload=' . urlencode($status));
            exit;
        };

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $redirectToAdmin('invalid_request');
        }

        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $this->getBasePath() . '/index.php?route=login');
            exit;
        }

        if (($_SESSION['role'] ?? '') !== 'admin') {
            header('Location: ' . $this->getBasePath() . '/index.php?route=home');
            exit;
        }

        $videoIdRaw = trim((string)($_POST['videoId'] ?? ''));
        if ($videoIdRaw === '' || !ctype_digit($videoIdRaw)) {
            $redirectToAdmin('invalid_video');
        }

        $videoId = (int) $videoIdRaw;
        $userId = (int) $_SESSION['user_id'];
        $action = trim((string)($_POST['action'] ?? ''));

        $videoModel = new Video();
        $existingVideo = $videoModel->getVideoByIdAndUserId($videoId, $userId);

        if ($existingVideo === null) {
            $redirectToAdmin('invalid_video');
        }

        if ($action === 'delete') {
            $filename = trim((string)($existingVideo['filename'] ?? ''));
            $thumbnail = trim((string)($existingVideo['thumbnail'] ?? ''));
            $deleteOk = $videoModel->deleteVideoByIdAndUserId($videoId, $userId);

            if (!$deleteOk) {
                $redirectToAdmin('delete_failed');
            }
            $videoFileDeleteFailed = false;
            $thumbnailFileDeleteFailed = false;

            if ($filename !== '') {
                $filePath = __DIR__ . '/../../public/uploads/' . $filename;

                if (file_exists($filePath) && !unlink($filePath)) {
                    $videoFileDeleteFailed = true;
                }
            }

            if ($thumbnail !== '') {
                $thumbnailPath = __DIR__ . '/../../public/uploads/thumbnails/' . $thumbnail;

                if (file_exists($thumbnailPath) && !unlink($thumbnailPath)) {
                    $thumbnailFileDeleteFailed = true;
                }
            }

            if ($videoFileDeleteFailed) {
                $redirectToAdmin('delete_file_failed');
            }

            if ($thumbnailFileDeleteFailed) {
                $redirectToAdmin('delete_thumbnail_file_failed');
            }

            $redirectToAdmin('deleted');
        }

        if ($action !== 'update') {
            $redirectToAdmin('invalid_request');
        }

        $videoTitle = trim((string)($_POST['videoTitle'] ?? ''));
        $videoDescription = trim((string)($_POST['videoDescription'] ?? ''));
        $videoCategoryRaw = trim((string)($_POST['videoCategory'] ?? ''));
        $videoVisibilityRaw = trim((string)($_POST['videoVisibility'] ?? ''));

        if ($videoTitle === '') {
            $redirectToAdmin('missing_title');
        }

        $categoryId = null;
        if ($videoCategoryRaw !== '') {
            if (!ctype_digit($videoCategoryRaw)) {
                $redirectToAdmin('invalid_category');
            }

            $categoryId = (int) $videoCategoryRaw;
        }

        $visibilityOptions = $videoModel->getVisibilityOptions();
        if ($videoVisibilityRaw === '' || !in_array($videoVisibilityRaw, $visibilityOptions, true)) {
            $redirectToAdmin('invalid_visibility');
        }

        $newThumbnailFilename = null;
        $thumbnailUploadError = (int) ($_FILES['thumbnailToUpload']['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($thumbnailUploadError !== UPLOAD_ERR_NO_FILE) {
            if ($thumbnailUploadError !== UPLOAD_ERR_OK) {
                if ($thumbnailUploadError === UPLOAD_ERR_INI_SIZE || $thumbnailUploadError === UPLOAD_ERR_FORM_SIZE) {
                    $redirectToAdmin('thumbnail_too_large');
                }

                $redirectToAdmin('thumbnail_upload_failed');
            }

            $thumbnailOriginalName = basename((string) ($_FILES['thumbnailToUpload']['name'] ?? ''));
            if ($thumbnailOriginalName === '') {
                $redirectToAdmin('missing_thumbnail');
            }

            $thumbnailFileType = strtolower(pathinfo($thumbnailOriginalName, PATHINFO_EXTENSION));
            $allowedThumbnailTypes = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (!in_array($thumbnailFileType, $allowedThumbnailTypes, true)) {
                $redirectToAdmin('invalid_thumbnail_type');
            }

            $thumbnailSize = (int) ($_FILES['thumbnailToUpload']['size'] ?? 0);
            if ($thumbnailSize > 5000000) {
                $redirectToAdmin('thumbnail_too_large');
            }

            $thumbnailNameWithoutExtension = pathinfo($thumbnailOriginalName, PATHINFO_FILENAME);
            $sanitizedThumbnailBaseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $thumbnailNameWithoutExtension);

            if ($sanitizedThumbnailBaseName === null || $sanitizedThumbnailBaseName === '') {
                $sanitizedThumbnailBaseName = 'thumbnail';
            }

            $newThumbnailFilename = $sanitizedThumbnailBaseName . '_' . uniqid('', true) . '.' . $thumbnailFileType;
            $thumbnailTargetDir = __DIR__ . '/../../public/uploads/thumbnails/';
            $thumbnailTargetFile = $thumbnailTargetDir . $newThumbnailFilename;

            if (!is_dir($thumbnailTargetDir) && !mkdir($thumbnailTargetDir, 0777, true) && !is_dir($thumbnailTargetDir)) {
                $redirectToAdmin('thumbnail_upload_failed');
            }

            if (!move_uploaded_file($_FILES['thumbnailToUpload']['tmp_name'], $thumbnailTargetFile)) {
                $redirectToAdmin('thumbnail_upload_failed');
            }
        }

        $updateOk = $videoModel->updateVideoByIdAndUserId(
            $videoId,
            $userId,
            $videoTitle,
            $videoDescription === '' ? null : $videoDescription,
            $categoryId,
            $videoVisibilityRaw,
            $newThumbnailFilename
        );

        if (!$updateOk) {
            if ($newThumbnailFilename !== null) {
                $newThumbnailPath = __DIR__ . '/../../public/uploads/thumbnails/' . $newThumbnailFilename;

                if (file_exists($newThumbnailPath)) {
                    unlink($newThumbnailPath);
                }
            }
            $redirectToAdmin('update_failed');
        }

        if ($newThumbnailFilename !== null) {
            $oldThumbnail = trim((string)($existingVideo['thumbnail'] ?? ''));

            if ($oldThumbnail !== '' && $oldThumbnail !== $newThumbnailFilename) {
                $oldThumbnailPath = __DIR__ . '/../../public/uploads/thumbnails/' . $oldThumbnail;

                if (file_exists($oldThumbnailPath) && !unlink($oldThumbnailPath)) {
                    $redirectToAdmin('update_thumbnail_cleanup_failed');
                }
            }
        }

        $redirectToAdmin('updated');
    }

    public function subscriptions(){
        $this->ensureSessionStarted();
        $videoModel = new Video();
        $videos = $videoModel->getAllVideos();
        // $videos = $videoModel->getAllSubscribedVideos();
        // $subscriptions = $subscriptionModel->getUserSubscriptions($_SESSION['user_id']);
        $isLoggedIn = isset($_SESSION['user_id']);

        $this->render('home/subscription', [
            'basePath' => $this->getBasePath(),
            'videos' => $videos,
            'isLoggedIn' => $isLoggedIn,
        ]);
    }

    public function library(){
        $this->ensureSessionStarted();
        $videoModel = new Video();
        $videos = $videoModel->getAllVideos();
        // $likedVideos = $videoModel->getLikedVideosByUser($_SESSION['user_id']);
        // $watchLaterVideos = $videoModel->getWatchLaterVideosByUser($_SESSION['user_id']);
        $isLoggedIn = isset($_SESSION['user_id']);

        $this->render('home/library', [
            'basePath' => $this->getBasePath(),
            'videos' => $videos,
            'isLoggedIn' => $isLoggedIn,
        ]);
    }

    public function history(){
        $this->ensureSessionStarted();
        $videoModel = new Video();
        $videos = $videoModel->getAllVideos();
        // $historyVideos = $videoModel->getHistoryVideosByUser($_SESSION['user_id']);
        $isLoggedIn = isset($_SESSION['user_id']);

        $this->render('home/history', [
            'basePath' => $this->getBasePath(),
            'videos' => $videos,
            'isLoggedIn' => $isLoggedIn,
        ]);
    }

    public function user(){
        $this->ensureSessionStarted();
        $isLoggedIn = isset($_SESSION['user_id']);
        $channelUserId = (int) ($_GET['id'] ?? 0);

        if ($channelUserId <= 0) {
            header('Location: ' . $this->getBasePath() . '/index.php?route=home');
            exit;
        }

        $sort = strtolower(trim((string)($_GET['sort'] ?? 'latest')));
        $allowedSorts = ['latest', 'popular', 'oldest'];

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'latest';
        }

        $userModel = new user();
        $channelUser = $userModel->getUserById($channelUserId);

        if ($channelUser === null) {
            header('Location: ' . $this->getBasePath() . '/index.php?route=home');
            exit;
        }

        $videoModel = new Video();
        $videos = $videoModel->getPublicVideosByUserId($channelUserId, $sort);

        $this->render('home/user', [
            'basePath' => $this->getBasePath(),
            'isLoggedIn' => $isLoggedIn,
            'videos' => $videos,
            'channelUser' => $channelUser,
            'selectedSort' => $sort,
        ]);
    }
}

<?php
// Import controller
require_once __DIR__ . '/controller.php';

// Import models
require_once __DIR__ . '/../models/video.php';
require_once __DIR__ . '/../models/comment.php';
require_once __DIR__ . '/../models/category.php';
require_once __DIR__ . '/../models/like.php';
require_once __DIR__ . '/../models/user.php';
require_once __DIR__ . '/../models/subscription.php';
require_once __DIR__ . '/../models/watchLater.php'; 
require_once __DIR__ . '/../models/watchHistory.php';

// View controller for rendering pages and handling page related logic which extends the controller class
class viewController extends Controller
{
    // Ensure session is started before accessing session variables
    private function ensureSessionStarted()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Function that decides if the view should increment or not using sessions
    private function shouldIncrementVideoViewForSession(int $videoId): bool
    {
        // Initialize the viewed videos session array if it doesn't exist
        if (!isset($_SESSION['viewed_videos']) || !is_array($_SESSION['viewed_videos'])) {
            $_SESSION['viewed_videos'] = [];
        }

        // Do not increment the view count if this video has already been viewed. This line avoids users giving views to an video while they have already watched it
        if (($_SESSION['viewed_videos'][$videoId] ?? false) === true) {
            return false;
        }

        // Mark the video as viewed and allow the view count to be incremented
        $_SESSION['viewed_videos'][$videoId] = true;

        return true;
    }

    // Home page with the video feed
    public function index()
    {
        // Ensure session is always started before entering an page, this line will be found on the top of every page function
        $this->ensureSessionStarted();
        $videoModel = new Video();
        $videos = $videoModel->getAllVideos();
        $isLoggedIn = isset($_SESSION['user_id']);

        // Render the home page with all data
        $this->render('home/index', [
            'basePath' => $this->getBasePath(),
            'videos' => $videos,
            'isLoggedIn' => $isLoggedIn,
        ]);
    }

    // Video page with the video player, comments and information about the video
    public function video()
    {
        $this->ensureSessionStarted();
        $isLoggedIn = isset($_SESSION['user_id']);
        $videoId = (int) ($_GET['id'] ?? 0);

        // If the video id is not valid make the user return to an error page
        if ($videoId <= 0) {
            header('Location: ' . $this->getBasePath() . '/index.php?route=error&type=not_found&code=404');
            exit;
        }

        // Use the video model to make an new instance and to fetch an video using its id
        $videoModel = new Video();
        $video = $videoModel->getVideoById($videoId);

        // If the video id doesn't exist make the user return to an error page
        if ($video === null) {
            header('Location: ' . $this->getBasePath() . '/index.php?route=error&type=not_found&code=404');
            exit;
        }

        // Default view to false so it can turn to true after an user has watched it
        $viewWasIncremented = false;

        // If the function was true increment the view on the videoId
        if ($this->shouldIncrementVideoViewForSession($videoId)) {
            $viewWasIncremented = $videoModel->incrementVideoViewsByIdForShare($videoId);

            // If the viewwasincremented and the user is logged in update the views in video by +1 and save the video in watchHistory
            if ($viewWasIncremented && $isLoggedIn) {
                $video['views'] = (int) ($video['views'] ?? 0) + 1;
                $watchHistoryModel = new watchHistory();
                $watchHistoryModel->addWatchedVideo((int) $_SESSION['user_id'], $videoId);

            } else {
                // If the code above failed unset the viewed_videos variable from the videoId
                unset($_SESSION['viewed_videos'][$videoId]);
            }
        }

        // Sidebar videos that use an filter to fetch so that you don't get the video fetched that you are watching
        $sidebarVideos = array_values(array_filter(
            $videoModel->getAllVideos(),
            static function ($listedVideo) use ($videoId): bool {
                return (int) ($listedVideo['id'] ?? 0) !== $videoId;
            }
        ));

        // Comments for the video
        $commentModel = new Comment();
        $comments = $commentModel->fetchComments($videoId, $isLoggedIn ? (int) $_SESSION['user_id'] : 0);

        // Define current user and channel id
        $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
        $channelUserId = (int) ($video['user_id'] ?? 0);

        // Bind reaction and subscription status to null and false for the current user
        $userReactionType = null;
        $userSubscribe = false;

        // Allow subscription only if channel exists and user is not the channel owner
        $canSubscribe = $channelUserId > 0 && $currentUserId !== $channelUserId;

        // Watch later for the video
        $watchLaterVideos = [];
        if ($isLoggedIn) {
            $watchLaterModel = new watchLaterVideos();
            $watchLaterVideos = $watchLaterModel->getUserWatchLaterVideos((int) $_SESSION['user_id']);
        }

        // Subscriber count for the channel
        $subscriptionModel = new Subscription();
        $subscriberCount = $channelUserId > 0
            ? $subscriptionModel->getSubscriptionCount($channelUserId)
            : 0;

        // If the user is logged in fetch their likes, dislike and subscription status
        if ($isLoggedIn) {
            $likeModel = new Like();
            $userReactionType = $likeModel->getUserVideoReaction($videoId, (int) $_SESSION['user_id']);

            // Subscription status for the current channel
            if ($canSubscribe) {
                $userSubscribe = $subscriptionModel->isUserSubscribed($currentUserId, $channelUserId);
            }
        }

        // Render the video page with all data
        $this->render('home/video', [
            'basePath' => $this->getBasePath(),
            'comments' => $comments,
            'video' => $video,
            'sidebarVideos' => $sidebarVideos,
            'isLoggedIn' => $isLoggedIn,
            'userReactionType' => $userReactionType,
            'userSubscribe' => $userSubscribe,
            'subscriberCount' => $subscriberCount,
            'canSubscribe' => $canSubscribe,
            'watchLaterVideos' => $watchLaterVideos,
        ]);
    }

    // Admin page for uploading videos and managing them
    public function admin()
    {
        $this->ensureSessionStarted();
        $isLoggedIn = isset($_SESSION['user_id']);

        // If the user is not logged in send him to the login page
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $this->getBasePath() . '/index.php?route=login');
            exit;
        }

        // If an user is not an admin send him to the error page
        if (($_SESSION['role'] ?? '') !== 'admin') {
            header('Location: ' . $this->getBasePath() . '/index.php?route=error&type=admin_required&code=1001  ');
            exit;
        }

        // Create a new instance from the videoModel and get videos using the logged in userId to fetch the videos made by the user
        $videoModel = new Video();
        $uploadedVideos = $videoModel->getVideosByUserId((int) $_SESSION['user_id']);

        // Fetch the visibilityOptions for every video
        $visibilityOptions = $videoModel->getVisibilityOptions();

        // Create a new instance from the categoryModel and fetch all categories
        $categoryModel = new category();
        $categories = $categoryModel->getAllCategories();

        // Ftech the upload status
        $uploadStatus = trim((string)($_GET['upload'] ?? ''));

        // All the upload messages for the admin, these messages are used for displaying error and succes messages
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

        // Create a new instance with commentModel and fetch the comment count so you can display the count for the admin on every video
        $commentModel = new Comment();
        $comments = $commentModel->fetchCommentCountsByVideoIds($videoIds);

        // Map out the videoId using the uploadedVideos variable
        $videoIds = array_map(
            static function (array $video): int {
                return (int) ($video['id'] ?? 0);
            },
            $uploadedVideos
        );   

        // Render the admin page with all data
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

    // Page with subscribed channels and their videos
    public function subscriptions(){
        $this->ensureSessionStarted();
        $isLoggedIn = isset($_SESSION['user_id']);

        // If the user is not logged in return him to the login page
        if (!$isLoggedIn) {
            header('Location: ' . $this->getBasePath() . '/index.php?route=login');
            exit;
        }

        // Create a new instance with subscriptionModel and 
        $subscriptionModel = new Subscription();

        // Read the view parameter from the URL query string and default to 'videos' if not provided
        $selectedView = strtolower(trim((string)($_GET['view'] ?? 'videos')));

        // Define allowed views so it is easy to switch between videos and profiles
        $allowedViews = ['videos', 'profiles'];

        // If the value is not allowed, fall back to the default view which is videos
        if (!in_array($selectedView, $allowedViews, true)) {
            $selectedView = 'videos';
        }

        // Fetch subscribed videos
        $videos = $subscriptionModel->getSubscribedVideosByUserId((int) $_SESSION['user_id']);

        // Fetch creator profiles
        $profiles = $subscriptionModel->getSubscribedCreatorsByUserId((int) $_SESSION['user_id']);

        // Render the subscripition page with all data
        $this->render('home/subscription', [
            'basePath' => $this->getBasePath(),
            'videos' => $videos,
            'profiles' => $profiles,
            'selectedView' => $selectedView,
            'isLoggedIn' => $isLoggedIn,
        ]);
    }

    // Page with watch later
    public function library(){
        $this->ensureSessionStarted();
        $isLoggedIn = isset($_SESSION['user_id']);

        // Create a new instance using the watchLaterModel and fetch all watchLaterVideos with an userId
        $watchLaterModel = new watchLaterVideos();
        $videos = $watchLaterModel->getUserWatchLaterVideos((int) ($_SESSION['user_id'] ?? 0));

        // Render the library page with all data
        $this->render('home/library', [
            'basePath' => $this->getBasePath(),
            'videos' => $videos,
            'isLoggedIn' => $isLoggedIn,
        ]);
    }

    // History page
    public function history(){
        $this->ensureSessionStarted();
        $isLoggedIn = isset($_SESSION['user_id']);

        // Create a new instance using the watchHistoryModel and fetch all watched videos with userId
        $watchHistoryModel = new watchHistory();
        $videos = $watchHistoryModel->getUserWatchedVideos((int) ($_SESSION['user_id'] ?? 0));

        // Render the history page with all data
        $this->render('home/history', [
            'basePath' => $this->getBasePath(),
            'videos' => $videos,
            'isLoggedIn' => $isLoggedIn,
        ]);
    }

    // Profile pages for users with their videos and information
    public function user(){
        $this->ensureSessionStarted();
        $isLoggedIn = isset($_SESSION['user_id']);
        $channelUserId = (int) ($_GET['id'] ?? 0);

        // If the channel id is not valid make the user return to an error page
        if ($channelUserId <= 0) {
            header('Location: ' . $this->getBasePath() . '/index.php?route=error&type=not_found&code=404');
            exit;
        }

        // Create a new instance using userModel and fetch user by his id
        $userModel = new user();
        $channelUser = $userModel->getUserById($channelUserId);

        // If the channel id do exist make the user return to an error page
        if ($channelUser === null) {
            header('Location: ' . $this->getBasePath() . '/index.php?route=error&type=not_found&code=404');
            exit;
        }

        // Create a new instance using videoModel and fetch public videos using userId to display all videos made by the channel/user
        $videoModel = new Video();
        $videos = $videoModel->getPublicVideosByUserId($channelUserId);

        // 
        $currentUserId = (int) ($_SESSION['user_id'] ?? 0);

        // Create a new instance using subscripitionModel and fetch the subscription count to show it on the channel page
        $subscriptionModel = new Subscription();
        $subscriberCount = $subscriptionModel->getSubscriptionCount($channelUserId);

        // Default userSubscribe to false
        $userSubscribe = false;

        // Check whether the logged-in user is subscribed to this channel.
        if ($isLoggedIn && $currentUserId > 0 && $currentUserId !== $channelUserId) {
            $userSubscribe = $subscriptionModel->isUserSubscribed($currentUserId, $channelUserId);
        }

        // Render the profile page with all data
        $this->render('home/user', [
            'basePath' => $this->getBasePath(),
            'isLoggedIn' => $isLoggedIn,
            'videos' => $videos,
            'channelUser' => $channelUser,
            'subscriberCount' => $subscriberCount,
            'userSubscribe' => $userSubscribe,
        ]);
    }

    // Edit, delete and update videos on admin page
    public function manageVideo()
    {
        $this->ensureSessionStarted();

        // RedirectToAdmin function that redirects an user to the admin page with an status
        $redirectToAdmin = function (string $status): void {
            header('Location: ' . $this->getBasePath() . '/index.php?route=admin&upload=' . urlencode($status));
            exit;
        };

        // If the method is not post redirect the admin
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $redirectToAdmin('invalid_request');
        }

        // If the user is not logged in redirect him to login
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $this->getBasePath() . '/index.php?route=login');
            exit;
        }

        // If an user is not an admin send him to the error page
        if (($_SESSION['role'] ?? '') !== 'admin') {
            header('Location: ' . $this->getBasePath() . '/index.php?route=error&type=admin_required&code=1001');
            exit;
        }

        // Define raw videoId
        $videoIdRaw = trim((string)($_POST['videoId'] ?? ''));

        // Redirect if the video ID is missing or contains non-numeric characters.
        if ($videoIdRaw === '' || !ctype_digit($videoIdRaw)) {
            $redirectToAdmin('invalid_video');
        }

        // Define videoId using VideoIdRaw
        $videoId = (int) $videoIdRaw;
        $userId = (int) $_SESSION['user_id'];
        $action = trim((string)($_POST['action'] ?? ''));

        // Create a new instance using videoModel and fetch video by videoId and userId to define the exisiting video
        $videoModel = new Video();
        $existingVideo = $videoModel->getVideoByIdAndUserId($videoId, $userId);

        // If the existing video is null redirect to admin with invalid_video error
        if ($existingVideo === null) {
            $redirectToAdmin('invalid_video');
        }

        // Action for deleting videos
        if ($action === 'delete') {
            // Define parameters for delete action
            $filename = trim((string)($existingVideo['filename'] ?? ''));
            $thumbnail = trim((string)($existingVideo['thumbnail'] ?? ''));
            $deleteOk = $videoModel->deleteVideoByIdAndUserId($videoId, $userId);

            // If delete fails redirect to admin
            if (!$deleteOk) {
                $redirectToAdmin('delete_failed');
            }

            // Default to false for both
            $videoFileDeleteFailed = false;
            $thumbnailFileDeleteFailed = false;

            // If filename fails
            if ($filename !== '') {
                $filePath = __DIR__ . '/../../public/uploads/' . $filename;

                if (file_exists($filePath) && !unlink($filePath)) {
                    $videoFileDeleteFailed = true;
                }
            }

            // If thumbnail fails
            if ($thumbnail !== '') {
                // Define the path for uploading an thumbnail
                $thumbnailPath = __DIR__ . '/../../public/uploads/thumbnails/' . $thumbnail;

                // If deletion fails -> mark thumbnailFileDeleteFailed as true
                if (file_exists($thumbnailPath) && !unlink($thumbnailPath)) {
                    $thumbnailFileDeleteFailed = true;
                }
            }

            // if videofiledelete redirect the user to admin with delete file failed
            if ($videoFileDeleteFailed) {
                $redirectToAdmin('delete_file_failed');
            }

            // if thumbnailfiledeletefailed redirect the user to admin with thumbnail file failed
            if ($thumbnailFileDeleteFailed) {
                $redirectToAdmin('delete_thumbnail_file_failed');
            }

            // If the code above failed redirect to admin with deleted
            $redirectToAdmin('deleted');
        }

        // Ensure the only action that is allowed is update and if the user has an other action redirect him to admin with invalid request
        if ($action !== 'update') {
            $redirectToAdmin('invalid_request');
        }

        $videoTitle = trim((string)($_POST['videoTitle'] ?? ''));
        $videoDescription = trim((string)($_POST['videoDescription'] ?? ''));
        $videoCategoryRaw = trim((string)($_POST['videoCategory'] ?? ''));
        $videoVisibilityRaw = trim((string)($_POST['videoVisibility'] ?? ''));

        // If videoTitle is empty redirect the user to admin and show missing_title this stops the user from inputting nothing
        if ($videoTitle === '') {
            $redirectToAdmin('missing_title');
        }

        // Bind categoryId to null
        $categoryId = null;

        // If a category is provided, validate that it contains only digits with ctype_digit
        if ($videoCategoryRaw !== '') {
            if (!ctype_digit($videoCategoryRaw)) {
                $redirectToAdmin('invalid_category');
            }

            $categoryId = (int) $videoCategoryRaw;
        }

        // Use videoModel to fetch the visbilityOptions for videos
        $visibilityOptions = $videoModel->getVisibilityOptions();

        // Validate video visibility input
        if ($videoVisibilityRaw === '' || !in_array($videoVisibilityRaw, $visibilityOptions, true)) {
            $redirectToAdmin('invalid_visibility');
        }

        // Bind to null
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

            // If the thumbnail size is above 500MB redirect the user to admin and show thumbnail too large this stops the user from uploading too big photos
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

        // If all code ran redirectToAdmin and show updated message
        $redirectToAdmin('updated');
    }

    // Error page
    public function error()
    {
        $this->ensureSessionStarted();
        $isLoggedIn = isset($_SESSION['user_id']);

        // Render the error page with all data
        $this->render('home/error', [
            'basePath' => $this->getBasePath(),
            'isLoggedIn' => $isLoggedIn,
        ]);
    }
}

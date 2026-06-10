<?php
require_once __DIR__ . '/controller.php';
require_once __DIR__ . '/../models/video.php';
require_once __DIR__ . '/../models/comment.php';
require_once __DIR__ . '/../models/like.php';
require_once __DIR__ . '/../models/subscription.php';
require_once __DIR__ . '/../models/watchLater.php';

class buttonController extends Controller
{
    private function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function watchLater(): void
    {
        $this->ensureSessionStarted();

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json([
                'success' => false,
                'message' => 'Invalid request method.',
            ], 405);
            return;
        }

        if (!isset($_SESSION['user_id'])) {
            $this->json([
                'success' => false,
                'message' => 'Please log in first.',
                'redirect' => $this->getBasePath() . '/index.php?route=login',
            ], 401);
            return;
        }

        $videoId = (int) ($_POST['videoId'] ?? 0);

        if ($videoId <= 0) {
            $this->json([
                'success' => false,
                'message' => 'Invalid video ID.',
            ], 422);
            return;
        }

        $watchLaterModel = new watchLaterVideos();
        $isSaved = $watchLaterModel->toggleWatchLaterVideo((int) $_SESSION['user_id'], $videoId);

        if ($isSaved === null) {
            $this->json([
                'success' => false,
                'message' => 'Unable to update watch later list.',
            ], 500);
            return;
        }

        $this->json([
            'success' => true,
            'isSaved' => $isSaved,
            'message' => $isSaved
                ? 'Video added to watch later list successfully.'
                : 'Video removed from watch later list successfully.',
        ]);
    }

    // Manage the subscriptions
    public function subscription(): void 
    {
        $this->ensureSessionStarted();

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json([
                'success' => false,
                'message' => 'Invalid request method.',
            ], 405);
            return;
        }

        if (!isset($_SESSION['user_id'])) {
            $this->json([
                'success' => false,
                'message' => 'Please log in first.',
                'redirect' => $this->getBasePath() . '/index.php?route=login',
            ], 401);
            return;
        }

        $creatorId = (int) ($_POST['channelId'] ?? 0);

        if ($creatorId <= 0) {
            $this->json([
                'success' => false,
                'message' => 'Invalid channel ID.',
            ], 422);
            return;
        }

        $subscriptionModel = new Subscription();
        $subscriberId = (int) $_SESSION['user_id'];
        $isSubscribed = $subscriptionModel->setUserSubscribe($subscriberId, $creatorId);

        if ($isSubscribed === null && $subscriberId === $creatorId) {
            $this->json([
                'success' => false,
                'message' => 'You cannot subscribe to your own channel.',
            ], 422);
            return;
        }

        if ($isSubscribed === null) {
            $this->json([
                'success' => false,
                'message' => 'Unable to update subscription.',
            ], 500);
            return;
        }

        $subscriberCount = $subscriptionModel->getSubscriptionCount($creatorId);

        $this->json([
            'success' => true,
            'isSubscribed' => $isSubscribed,
            'subscriberCount' => $subscriberCount,
        ]);
    }

    // Manage reactions on video
    public function video(): void
    {
        $this->ensureSessionStarted();

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json([
                'success' => false,
                'message' => 'Invalid request method.',
            ], 405);
            return;
        }

        if (!isset($_SESSION['user_id'])) {
            $this->json([
                'success' => false,
                'message' => 'Please log in first.',
                'redirect' => $this->getBasePath() . '/index.php?route=login',
            ], 401);
            return;
        }

        $videoId = (int) ($_POST['videoId'] ?? 0);
        $reaction = trim((string) ($_POST['reaction'] ?? ''));

        if ($videoId <= 0 || !in_array($reaction, ['like', 'dislike'], true)) {
            $this->json([
                'success' => false,
                'message' => 'Invalid reaction payload.',
            ], 422);
            return;
        }

        $videoModel = new Video();
        $video = $videoModel->getVideoById($videoId);

        if ($video === null) {
            $this->json([
                'success' => false,
                'message' => 'Video not found.',
            ], 404);
            return;
        }

        $reactionModel = new Like();
        $userId = (int) $_SESSION['user_id'];
        $nextReactionType = $reaction === 'like';
        $currentReaction = $reactionModel->setUserVideoReaction($videoId, $userId, $nextReactionType);
        $counts = $reactionModel->getVideoReactionCounts($videoId);

        // Return JSON with likes and dislikes for videos and comments
        $this->json([
            'success' => true,
            'likes' => (int) ($counts['likes'] ?? 0),
            'dislikes' => (int) ($counts['dislikes'] ?? 0),
            'currentReaction' => $currentReaction === null
                ? null
                : ($currentReaction ? 'like' : 'dislike'),
        ]);
    }

    // Manage reactions on comment
    public function comment(): void
    {
        $this->ensureSessionStarted();

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json([
                'success' => false,
                'message' => 'Invalid request method.',
            ], 405);
            return;
        }

        if (!isset($_SESSION['user_id'])) {
            $this->json([
                'success' => false,
                'message' => 'Please log in first.',
                'redirect' => $this->getBasePath() . '/index.php?route=login',
            ], 401);
            return;
        }

        $commentId = (int) ($_POST['commentId'] ?? 0);
        $reaction = trim((string) ($_POST['reaction'] ?? ''));

        if ($commentId <= 0 || !in_array($reaction, ['like', 'dislike'], true)) {
            $this->json([
                'success' => false,
                'message' => 'Invalid reaction payload.',
            ], 422);
            return;
        }

        $commentModel = new Comment();
        $comment = $commentModel->getCommentByIdForShare($commentId);

        if ($comment === null) {
            $this->json([
                'success' => false,
                'message' => 'Comment not found.',
            ], 404);
            return;
        }

        $reactionModel = new Like();
        $userId = (int) $_SESSION['user_id'];
        $nextReactionType = $reaction === 'like';
        $currentReaction = $reactionModel->setUserCommentReaction($commentId, $userId, $nextReactionType);
        $counts = $reactionModel->getCommentReactionCounts($commentId);

        $this->json([
            'success' => true,
            'likes' => (int) ($counts['likes'] ?? 0),
            'dislikes' => (int) ($counts['dislikes'] ?? 0),
            'currentReaction' => $currentReaction === null
                ? null
                : ($currentReaction ? 'like' : 'dislike'),
        ]);
    }
}

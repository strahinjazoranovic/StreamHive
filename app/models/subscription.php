<?php

require_once __DIR__ . '/../../core/Database.php';

class Subscription
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Fetch subscriptions by subscriber_id
    public function getUserSubscription(int $subscriberId): array
    {
        $query = "SELECT * FROM subscriptions WHERE subscriber_id = ?";

        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return [];
        }

        mysqli_stmt_bind_param($stmt, "i", $subscriberId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $subscriptions = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $subscriptions[] = $row;
            }
        }

        mysqli_stmt_close($stmt);

        return $subscriptions;
    }

    // Fetch to check is the user is subscribed to the creator
    public function isUserSubscribed(int $subscriberId, int $creatorId): bool
    {
        $query = "SELECT 1 FROM subscriptions WHERE subscriber_id = ? AND creator_id = ? LIMIT 1";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "ii", $subscriberId, $creatorId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $isSubscribed = $result && mysqli_fetch_assoc($result) !== null;
        mysqli_stmt_close($stmt);

        return $isSubscribed;
    }

    // Fetch the subscription count and return it as an row
    public function getSubscriptionCount(int $creatorId): int
    {
        $query = "SELECT COUNT(*) AS total_subscribers FROM subscriptions WHERE creator_id = ?";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return 0;
        }

        mysqli_stmt_bind_param($stmt, "i", $creatorId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return (int) ($row['total_subscribers'] ?? 0);
    }

    // Post an subscription on a creator
    public function setUserSubscribe(int $subscriberId, int $creatorId): ?bool
    {
        if ($subscriberId <= 0 || $creatorId <= 0 || $subscriberId === $creatorId) {
            return null;
        }

        $isCurrentlySubscribed = $this->isUserSubscribed($subscriberId, $creatorId);

        if ($isCurrentlySubscribed) {
            $deleteOk = $this->deleteUserSubscription($subscriberId, $creatorId);
            return $deleteOk ? false : null;
        }

        $query = "INSERT INTO subscriptions (subscriber_id, creator_id, created_at) VALUES (?, ?, NOW())";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            $fallbackQuery = "INSERT INTO subscriptions (subscriber_id, creator_id) VALUES (?, ?)";
            $stmt = mysqli_prepare($this->db, $fallbackQuery);
        }

        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, "ii", $subscriberId, $creatorId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok ? true : null;
    }

    // Fetch subscribed videos for an user/subscriber
    public function getSubscribedVideosByUserId(int $subscriberId): array
    {
        $query = "SELECT DISTINCT v.*, u.username FROM subscriptions s INNER JOIN videos v ON v.user_id = s.creator_id LEFT JOIN users u ON u.id = v.user_id WHERE s.subscriber_id = ? AND (v.visibilty = 'public' OR v.visibilty IS NULL) ORDER BY v.created_at DESC";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return [];
        }

        mysqli_stmt_bind_param($stmt, "i", $subscriberId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $videos = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $videos[] = $row;
            }
        }

        mysqli_stmt_close($stmt);

        return $videos;
    }

    // Fetch all subscribed accounts for an user/subscriber
    public function getSubscribedCreatorsByUserId(int $subscriberId): array
    {
        $query = "SELECT u.id, u.username, u.created_at, COUNT(DISTINCT v.id) AS public_video_count, COUNT(DISTINCT s2.subscriber_id) AS subscriber_count FROM subscriptions s INNER JOIN users u ON u.id = s.creator_id LEFT JOIN videos v ON v.user_id = u.id AND (v.visibilty = 'public' OR v.visibilty IS NULL) LEFT JOIN subscriptions s2 ON s2.creator_id = u.id WHERE s.subscriber_id = ? GROUP BY u.id, u.username, u.created_at ORDER BY u.username ASC";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return [];
        }

        mysqli_stmt_bind_param($stmt, "i", $subscriberId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $creators = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $creators[] = $row;
            }
        }

        mysqli_stmt_close($stmt);

        return $creators;
    }

    // Delete subscriber from an creator
    private function deleteUserSubscription(int $subscriberId, int $creatorId): bool
    {
        $query = "DELETE FROM subscriptions WHERE subscriber_id = ? AND creator_id = ?";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "ii", $subscriberId, $creatorId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $ok;
    }
}
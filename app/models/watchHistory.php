<?php

require_once __DIR__ . '/../../core/Database.php';

class watchHistory
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Get all watched videos for an user
    public function getUserWatchedVideos(int $userId): array
    {
        $query = "SELECT wh.video_id, v.*, u.username, c.name AS category_name FROM watch_history wh INNER JOIN videos v ON v.id = wh.video_id LEFT JOIN users u ON u.id = v.user_id LEFT JOIN category c ON c.id = v.category_id WHERE wh.user_id = ? ORDER BY wh.created_at DESC, wh.video_id DESC";

        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return [];
        }
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $watchHistory = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $watchHistory[] = $row;
            }
        }

        mysqli_stmt_close($stmt);

        return $watchHistory;
    }

    // Add watched video for an user
    public function addWatchedVideo(int $userId, int $videoId): ?bool
    {
        $alreadyExists = $this->isVideoSavedForUser($userId, $videoId);

        if ($alreadyExists === null) {
            return null;
        }

        if ($alreadyExists) {
            return $this->updateWatchedVideoTimestamp($userId, $videoId);
        }

        $insertQuery = "INSERT INTO watch_history (user_id, video_id, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)";
        $insertStatement = mysqli_prepare($this->db, $insertQuery);

        if (!$insertStatement) {
            return null;
        }

        mysqli_stmt_bind_param($insertStatement, "ii", $userId, $videoId);
        $result = mysqli_stmt_execute($insertStatement);
        mysqli_stmt_close($insertStatement);

        return $result;
    }

    private function updateWatchedVideoTimestamp(int $userId, int $videoId): ?bool
    {
        $updateQuery = "UPDATE watch_history SET created_at = CURRENT_TIMESTAMP WHERE user_id = ? AND video_id = ?";
        $updateStatement = mysqli_prepare($this->db, $updateQuery);

        if (!$updateStatement) {
            return null;
        }

        mysqli_stmt_bind_param($updateStatement, "ii", $userId, $videoId);
        $result = mysqli_stmt_execute($updateStatement);
        mysqli_stmt_close($updateStatement);

        return $result;
    }

    private function isVideoSavedForUser(int $userId, int $videoId): ?bool
    {
        $checkQuery = "SELECT 1 FROM watch_history WHERE user_id = ? AND video_id = ? LIMIT 1";
        $checkStatement = mysqli_prepare($this->db, $checkQuery);

        if (!$checkStatement) {
            return null;
        }

        mysqli_stmt_bind_param($checkStatement, "ii", $userId, $videoId);
        mysqli_stmt_execute($checkStatement);
        $existingResult = mysqli_stmt_get_result($checkStatement);
        $alreadyExists = $existingResult && mysqli_fetch_assoc($existingResult) !== null;
        mysqli_stmt_close($checkStatement);

        return $alreadyExists;
    }
}
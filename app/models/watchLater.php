<?php

require_once __DIR__ . '/../../core/Database.php';

class watchLaterVideos
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Fetch all watch later videos for an user
    public function getUserWatchLaterVideos(int $userId): array
    {
        $query = "SELECT wl.video_id, v.*, u.username FROM watch_later wl INNER JOIN videos v ON v.id = wl.video_id LEFT JOIN users u ON u.id = v.user_id WHERE wl.user_id = ? ORDER BY wl.video_id DESC";
        $statement = mysqli_prepare($this->db, $query);

        if (!$statement) {
            return [];
        }

        mysqli_stmt_bind_param($statement, "i", $userId);
        mysqli_stmt_execute($statement);
        $result = mysqli_stmt_get_result($statement);

        $videosWatchLater = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $videosWatchLater[] = $row;
            }
        }

        mysqli_stmt_close($statement);

        return $videosWatchLater;
    }

    // Add a video to watch later list for an user
    public function addWatchLaterVideo(int $userId, int $videoId): ?bool
    {
        $alreadyExists = $this->isVideoSavedForUser($userId, $videoId);

        if ($alreadyExists === null) {
            return null;
        }

        if ($alreadyExists) {
            return true;
        }

        $insertQuery = "INSERT INTO watch_later (user_id, video_id) VALUES (?, ?)";
        $insertStatement = mysqli_prepare($this->db, $insertQuery);

        if (!$insertStatement) {
            return null;
        }

        mysqli_stmt_bind_param($insertStatement, "ii", $userId, $videoId);
        $inserted = mysqli_stmt_execute($insertStatement);
        mysqli_stmt_close($insertStatement);

        return $inserted ? true : null;
    }

    // Toggle watch later state and return the next state (true = saved, false = removed)
    public function toggleWatchLaterVideo(int $userId, int $videoId): ?bool
    {
        $alreadyExists = $this->isVideoSavedForUser($userId, $videoId);

        if ($alreadyExists === null) {
            return null;
        }

        if ($alreadyExists) {
            $deleteQuery = "DELETE FROM watch_later WHERE user_id = ? AND video_id = ?";
            $deleteStatement = mysqli_prepare($this->db, $deleteQuery);

            if (!$deleteStatement) {
                return null;
            }

            mysqli_stmt_bind_param($deleteStatement, "ii", $userId, $videoId);
            $deleted = mysqli_stmt_execute($deleteStatement);
            mysqli_stmt_close($deleteStatement);

            return $deleted ? false : null;
        }

        $added = $this->addWatchLaterVideo($userId, $videoId);

        if ($added !== true) {
            return null;
        }

        return true;
    }

    private function isVideoSavedForUser(int $userId, int $videoId): ?bool
    {
        $checkQuery = "SELECT 1 FROM watch_later WHERE user_id = ? AND video_id = ? LIMIT 1";
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
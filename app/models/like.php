<?php

require_once __DIR__ . '/../../core/Database.php';

class Like
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Fetch user reaction(like or dislike) on an video
    public function getUserVideoReaction(int $videoId, int $userId): ?bool
    {
        $query = "SELECT type FROM likes WHERE video_id = ? AND user_id = ? AND comment_id IS NULL ORDER BY id DESC LIMIT 1";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, "ii", $videoId, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        if (!$row) {
            return null;
        }

        return (int) ($row['type'] ?? 0) === 1;
    }

    // Post an user reaction on a video with the correct type(true or false)
    public function setUserVideoReaction(int $videoId, int $userId, bool $reactionType): ?bool
    {
        $currentReaction = $this->getUserVideoReaction($videoId, $userId);
        $deleteOk = $this->deleteUserVideoReactions($videoId, $userId);

        if (!$deleteOk) {
            return $currentReaction;
        }

        if ($currentReaction !== null && $currentReaction === $reactionType) {
            return null;
        }

        $query = "INSERT INTO likes (user_id, video_id, comment_id, type) VALUES (?, ?, NULL, ?)";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return null;
        }

        $reactionTypeAsInt = $reactionType ? 1 : 0;
        mysqli_stmt_bind_param($stmt, "iii", $userId, $videoId, $reactionTypeAsInt);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $ok ? $reactionType : null;
    }

    // Fetch video reaction count for an video
    public function getVideoReactionCounts(int $videoId): array
    {
        $query = "SELECT SUM(CASE WHEN type = 1 THEN 1 ELSE 0 END) AS likes_count, SUM(CASE WHEN type = 0 THEN 1 ELSE 0 END) AS dislikes_count FROM likes WHERE video_id = ? AND comment_id IS NULL";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return [
                'likes' => 0,
                'dislikes' => 0,
            ];
        }

        mysqli_stmt_bind_param($stmt, "i", $videoId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return [
            'likes' => (int) ($row['likes_count'] ?? 0),
            'dislikes' => (int) ($row['dislikes_count'] ?? 0),
        ];
    }

    // Delete user reaction from a video
    private function deleteUserVideoReactions(int $videoId, int $userId): bool
    {
        $query = "DELETE FROM likes WHERE video_id = ? AND user_id = ? AND comment_id IS NULL";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "ii", $videoId, $userId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $ok;
    }

    // Fetch user reaction for an comment
    public function getUserCommentReaction(int $commentId, int $userId): ?bool
    {
        $query = "SELECT type FROM likes WHERE comment_id = ? AND user_id = ? AND video_id IS NULL ORDER BY id DESC LIMIT 1";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, "ii", $commentId, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        if (!$row) {
            return null;
        }

        return (int) ($row['type'] ?? 0) === 1;
    }

    // Post an user reaction for an comment with the correct type so true = (like) or false = (dislike)
    public function setUserCommentReaction(int $commentId, int $userId, bool $reactionType): ?bool
    {
        $currentReaction = $this->getUserCommentReaction($commentId, $userId);
        $deleteOk = $this->deleteUserCommentReactions($commentId, $userId);

        if (!$deleteOk) {
            return $currentReaction;
        }

        if ($currentReaction !== null && $currentReaction === $reactionType) {
            return null;
        }

        $query = "INSERT INTO likes (user_id, video_id, comment_id, type) VALUES (?, NULL, ?, ?)";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return null;
        }

        $reactionTypeAsInt = $reactionType ? 1 : 0;
        mysqli_stmt_bind_param($stmt, "iii", $userId, $commentId, $reactionTypeAsInt);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $ok ? $reactionType : null;
    }

    // Fetch comment reaction count for an comment
    public function getCommentReactionCounts(int $commentId): array
    {
        $query = "SELECT SUM(CASE WHEN type = 1 THEN 1 ELSE 0 END) AS likes_count, SUM(CASE WHEN type = 0 THEN 1 ELSE 0 END) AS dislikes_count FROM likes WHERE comment_id = ? AND video_id IS NULL";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return [
                'likes' => 0,
                'dislikes' => 0,
            ];
        }

        mysqli_stmt_bind_param($stmt, "i", $commentId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return [
            'likes' => (int) ($row['likes_count'] ?? 0),
            'dislikes' => (int) ($row['dislikes_count'] ?? 0),
        ];
    }

    // Delete user reaction from a comment
    private function deleteUserCommentReactions(int $commentId, int $userId): bool
    {
        $query = "DELETE FROM likes WHERE comment_id = ? AND user_id = ? AND video_id IS NULL";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "ii", $commentId, $userId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $ok;
    }
}

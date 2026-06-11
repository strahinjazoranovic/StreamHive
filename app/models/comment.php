<?php

require_once __DIR__ . '/../../core/Database.php';

class Comment
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Returns SQL SELECT fragment for comment reaction counts
    private function getCommentReactionSelectClause(): string
    {
        // Return the first null value from an list with COALESCE
        return "COALESCE(comment_reactions.likes_count, 0) AS likes, COALESCE(comment_reactions.dislikes_count, 0) AS dislikes";
    }

    // Returns SQL JOIN fragment for video reaction counts
    private function getCommentReactionJoinClause(): string
    {
        return "LEFT JOIN (SELECT l.comment_id, SUM(CASE WHEN l.type = 1 THEN 1 ELSE 0 END) AS likes_count, SUM(CASE WHEN l.type = 0 THEN 1 ELSE 0 END) AS dislikes_count FROM likes l WHERE l.comment_id IS NOT NULL GROUP BY l.comment_id) comment_reactions ON comment_reactions.comment_id = c.id ";
    }

    // Fetch comments for a specific video
    public function fetchComments(int $videoId, int $currentUserId = 0): array
    {
        if ($currentUserId > 0) {
            $query = "SELECT c.*, u.username, " . $this->getCommentReactionSelectClause() . ", current_user_comment_reaction.type AS current_user_reaction_type FROM comments c LEFT JOIN users u ON u.id = c.user_id " . $this->getCommentReactionJoinClause() . "LEFT JOIN (SELECT l1.comment_id, l1.type FROM likes l1 INNER JOIN (SELECT comment_id, MAX(id) AS max_id FROM likes WHERE user_id = ? AND video_id IS NULL AND comment_id IS NOT NULL GROUP BY comment_id) latest_reactions ON latest_reactions.max_id = l1.id) current_user_comment_reaction ON current_user_comment_reaction.comment_id = c.id WHERE c.video_id = ? ORDER BY c.created_at DESC";
        } else {
            $query = "SELECT c.*, u.username, " . $this->getCommentReactionSelectClause() . ", NULL AS current_user_reaction_type FROM comments c LEFT JOIN users u ON u.id = c.user_id " . $this->getCommentReactionJoinClause() . "WHERE c.video_id = ? ORDER BY c.created_at DESC";
        }

        $stmt = mysqli_prepare($this->db, $query);
        if (!$stmt) {
            return [];
        }
        if ($currentUserId > 0) {
            mysqli_stmt_bind_param($stmt, "ii", $currentUserId, $videoId);
        } else {
            mysqli_stmt_bind_param($stmt, "i", $videoId);
        }

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $comments = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $comments[] = $row;
            }
        }

        mysqli_stmt_close($stmt);
        return $comments;
    }

    // Retrieves a comment by ID only if its associated video is publicly shareable
    public function getCommentByIdForShare(int $commentId): ?array
    {
        $query = "SELECT c.* FROM comments c INNER JOIN videos v ON v.id = c.video_id WHERE c.id = ? AND (v.visibilty = 'public' OR v.visibilty = 'unlisted' OR v.visibilty IS NULL) LIMIT 1";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, "i", $commentId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $comment = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return $comment ?: null;
    }

    // Fetch comment counts for the admin page
    public function fetchCommentCountsByVideoIds(array $videoIds): array
    {
        $normalizedVideoIds = [];

        foreach ($videoIds as $videoId) {
            $normalizedVideoId = (int) $videoId;

            if ($normalizedVideoId > 0) {
                $normalizedVideoIds[$normalizedVideoId] = $normalizedVideoId;
            }
        }

        if (count($normalizedVideoIds) === 0) {
            return [];
        }

        $query = "SELECT video_id, COUNT(*) AS total_comments FROM comments WHERE video_id IN (" . implode(',', $normalizedVideoIds) . ") GROUP BY video_id";
        $result = mysqli_query($this->db, $query);

        if (!$result) {
            return [];
        }

        $commentCounts = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $commentCounts[(int) ($row['video_id'] ?? 0)] = (int) ($row['total_comments'] ?? 0);
        }

        return $commentCounts;
    }

    // Create a new comment under an video
    public function createComment(int $videoId, int $userId, string $content): bool
    {
        $query = "INSERT INTO comments (video_id, user_id, content) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, "iis", $videoId, $userId, $content);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $ok;
    }

    // Post an update made by the user on a comment
    public function updateCommentByIdAndUserId(
        int $commentId,
        int $userId,
        string $content
    ): bool {
        $query = "UPDATE comments SET content = ? WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "sii", $content, $commentId, $userId);
        $ok = mysqli_stmt_execute($stmt);
        $affectedRows = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        return $ok && $affectedRows > 0;
    }

    // Delete a single comment under an video
    public function deleteCommentByIdAndUserId(
        int $commentId,
        int $userId)
    : bool {
        $query = "DELETE FROM comments WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "ii", $commentId, $userId);
        $ok = mysqli_stmt_execute($stmt);
        $affectedRows = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        return $ok && $affectedRows > 0;
    }
}
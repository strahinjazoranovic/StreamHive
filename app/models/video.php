<?php

require_once __DIR__ . '/../../core/Database.php';

class Video
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Fetch all videos that should be visible in public feeds
    public function getAllVideos()
    {
        // Fetch only public videos (and older null values as public fallback)
        $query = "SELECT v.*, u.username FROM videos v LEFT JOIN users u ON u.id = v.user_id WHERE (v.visibilty = 'public' OR v.visibilty IS NULL) ORDER BY v.created_at DESC";

        $result = mysqli_query($this->db, $query);

        $videos = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $videos[] = $row;
        }

        return $videos;
    }

    // Create an video
    public function createVideo(
        int $userId,
        string $title,
        ?string $description,
        string $filename,
        ?int $categoryId,
        string $visibility,
        int $durationSeconds,
        string $thumbnail
    ): bool {
        $cleanDescription = $description !== null && trim($description) !== ''
            ? trim($description)
            : null;

        if ($categoryId === null) {
            $query = "INSERT INTO videos (user_id, title, description, filename, views, category_id, duration_seconds, thumbnail, visibilty, created_at) VALUES (?, ?, ?, ?, 0, NULL, ?, ?, ?, NOW())";
            $stmt = mysqli_prepare($this->db, $query);

            if (!$stmt) {
                return false;
            }

            mysqli_stmt_bind_param($stmt, "isssiss", $userId, $title, $cleanDescription, $filename, $durationSeconds, $thumbnail, $visibility);
        } else {
            $query = "INSERT INTO videos (user_id, title, description, filename, views, category_id, duration_seconds, thumbnail, visibilty, created_at) VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, NOW())";
            $stmt = mysqli_prepare($this->db, $query);

            if (!$stmt) {
                return false;
            }

            mysqli_stmt_bind_param($stmt, "isssiiss", $userId, $title, $cleanDescription, $filename, $categoryId, $durationSeconds, $thumbnail, $visibility);
        }

        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $ok;
    }

    // Function for fetching videos made by the user that is logged in
    public function getVideosByUserId(int $userId): array
    {
        
        $query = "SELECT v.*, c.name AS category_name FROM videos v LEFT JOIN category c ON c.id = v.category_id WHERE v.user_id = ? ORDER BY v.created_at DESC";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return [];
        }

        mysqli_stmt_bind_param($stmt, "i", $userId);
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

    // Fetch a single video by id for its owner
    public function getVideoByIdAndUserId(int $videoId, int $userId): ?array
    {
        $query = "SELECT * FROM videos WHERE id = ? AND user_id = ? LIMIT 1";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, "ii", $videoId, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $video = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return $video ?: null;
    }

    // Update a single video by id for its owner
    public function updateVideoByIdAndUserId(
        int $videoId,
        int $userId,
        string $title,
        ?string $description,
        ?int $categoryId,
        string $visibility,
        ?string $thumbnail = null
    ): bool {
        $cleanDescription = $description !== null && trim($description) !== ''
            ? trim($description)
            : null;

        if ($categoryId === null && $thumbnail === null) {
            $query = "UPDATE videos SET title = ?, description = ?, category_id = NULL, visibilty = ? WHERE id = ? AND user_id = ?";
            $stmt = mysqli_prepare($this->db, $query);

            if (!$stmt) {
                return false;
            }

            mysqli_stmt_bind_param($stmt, "sssii", $title, $cleanDescription, $visibility, $videoId, $userId);
        } elseif ($categoryId === null) {
            $query = "UPDATE videos SET title = ?, description = ?, category_id = NULL, visibilty = ?, thumbnail = ? WHERE id = ? AND user_id = ?";
            $stmt = mysqli_prepare($this->db, $query);

            if (!$stmt) {
                return false;
            }

            mysqli_stmt_bind_param($stmt, "ssssii", $title, $cleanDescription, $visibility, $thumbnail, $videoId, $userId);
        } elseif ($thumbnail === null) {
            $query = "UPDATE videos SET title = ?, description = ?, category_id = ?, visibilty = ? WHERE id = ? AND user_id = ?";
            $stmt = mysqli_prepare($this->db, $query);

            if (!$stmt) {
                return false;
            }

            mysqli_stmt_bind_param($stmt, "ssisii", $title, $cleanDescription, $categoryId, $visibility, $videoId, $userId);
        } else {
            $query = "UPDATE videos SET title = ?, description = ?, category_id = ?, visibilty = ?, thumbnail = ? WHERE id = ? AND user_id = ?";
            $stmt = mysqli_prepare($this->db, $query);

            if (!$stmt) {
                return false;
            }

            mysqli_stmt_bind_param($stmt, "ssissii", $title, $cleanDescription, $categoryId, $visibility, $thumbnail, $videoId, $userId);
        }

        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $ok;
    }

    // Delete a single video by id for its owner
    public function deleteVideoByIdAndUserId(int $videoId, int $userId): bool
    {
        $query = "DELETE FROM videos WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "ii", $videoId, $userId);
        $ok = mysqli_stmt_execute($stmt);
        $affectedRows = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        return $ok && $affectedRows > 0;
    }

    // Get all visibility enum values from videos table
    public function getVisibilityOptions(): array
    {
        $result = mysqli_query($this->db, "SHOW COLUMNS FROM videos LIKE 'visibilty'");

        if (!$result) {
            return ['public', 'unlisted', 'private'];
        }

        $column = mysqli_fetch_assoc($result);
        $type = (string) ($column['Type'] ?? '');

        if (!preg_match("/^enum\\((.*)\\)$/", $type, $matches)) {
            return ['public', 'unlisted', 'private'];
        }

        $optionsRaw = (string) ($matches[1] ?? '');
        $options = str_getcsv($optionsRaw, ',', "'", '\\');
        $cleanOptions = [];

        foreach ($options as $option) {
            $normalizedOption = trim((string) $option);

            if ($normalizedOption !== '') {
                $cleanOptions[] = $normalizedOption;
            }
        }

        if (count($cleanOptions) === 0) {
            return ['public', 'unlisted', 'private'];
        }

        return $cleanOptions;
    }

    // Fetch one video for direct-watch pages (public + unlisted only)
    public function getVideoByIdForShare(int $videoId): ?array
    {
        $query = "SELECT v.*, u.username, c.name AS category_name FROM videos v LEFT JOIN users u ON u.id = v.user_id LEFT JOIN category c ON c.id = v.category_id WHERE v.id = ? AND (v.visibilty = 'public' OR v.visibilty = 'unlisted' OR v.visibilty IS NULL) LIMIT 1";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, "i", $videoId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $video = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return $video ?: null;
    }
}
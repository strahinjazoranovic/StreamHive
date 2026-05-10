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

    public function getAllVideos()
    {
        // Fetch all videos along with the username of the uploader using a LEFT JOIN  query
        $query = "SELECT v.*, u.username FROM videos v LEFT JOIN users u ON u.id = v.user_id";

        $result = mysqli_query($this->db, $query);

        $videos = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $videos[] = $row;
        }

        return $videos;
    }
}
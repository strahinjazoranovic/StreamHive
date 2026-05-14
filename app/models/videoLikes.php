<?php

require_once __DIR__ . '/../../core/Database.php';

class videoLikes
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAllVideosLiked()
    {
        // Fetch all likes on videos
        // $query = "";

        $result = mysqli_query($this->db, $query);

        $videosLikes = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $videosLikes[] = $row;
        }

        return $videosLikes;
    }
}
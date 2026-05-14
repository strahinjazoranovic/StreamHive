<?php

require_once __DIR__ . '/../../core/Database.php';

class watchedVideos
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAllWatchedVideos()
    {
        // Fetch all watched videos for the user
        // $query = "";

        $result = mysqli_query($this->db, $query);

        $videosWatched = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $videosWatched[] = $row;
        }

        return $videosWatched;
    }
}
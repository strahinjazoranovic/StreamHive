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

    public function getAllWatchLaterVideos()
    {
        // Fetch all watch latervideos for the user
        // $query = "";

        $result = mysqli_query($this->db, $query);

        $videosWatchLater = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $videosWatchLater[] = $row;
        }

        return $videosWatchLater;
    }
}
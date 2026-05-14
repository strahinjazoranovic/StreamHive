<?php

require_once __DIR__ . '/../../core/Database.php';

class commentLikes
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAllCommentLikes()
    {
        // Fetch all likes on comments
        // $query = "";

        $result = mysqli_query($this->db, $query);

        $commentLikes = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $commentLikes[] = $row;
        }

        return $commentLikes;
    }
}
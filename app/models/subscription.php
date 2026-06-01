<?php

require_once __DIR__ . '/../../core/Database.php';

class subscriptions
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAllSubscriptions()
    {
        // Fetch all subcriptions for the user
        // $query = "";

        $result = mysqli_query($this->db, $query);

        $subscriptions = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $subscriptions[] = $row;
        }

        return $subscriptions;
    }
}
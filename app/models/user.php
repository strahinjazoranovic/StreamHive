<?php

require_once __DIR__ . '/../../core/Database.php';

class User
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAllUsers()
    {
        // Query for fetching all users from the database
        $query = "SELECT * FROM users";

        $result = mysqli_query($this->db, $query);

        $users = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }

        return $users;
    }
}
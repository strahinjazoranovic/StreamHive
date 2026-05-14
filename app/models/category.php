<?php

require_once __DIR__ . '/../../core/Database.php';

class category
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAllCategories()
    {
        // Fetch all categories
        // $query = "SELECT * FROM categories";

        $result = mysqli_query($this->db, $query);

        $categories = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }

        return $categories;
    }
}
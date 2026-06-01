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

    // Fetch all categories by order of name
    public function getAllCategories()
    {
        $query = "SELECT id, name FROM category ORDER BY name ASC";

        $result = mysqli_query($this->db, $query);

        $categories = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }

        return $categories;
    }
}
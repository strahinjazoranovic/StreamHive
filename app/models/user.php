<?php

require_once __DIR__ . '/../../core/Database.php';

class user
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Fetch all users
    public function getAllUsers()
    {
        $query = "SELECT * FROM users";

        $result = mysqli_query($this->db, $query);

        $users = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }

        return $users;
    }

    // Fetch just the user id
    public function getUserById(int $userId): ?array
    {
        $query = "SELECT id, username, role, created_at FROM users WHERE id = ? LIMIT 1";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return $user ?: null;
    }
}
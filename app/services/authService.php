<?php
// Finds a user by email and returns their data as an associative array, or null if not found
function findUserByEmail(mysqli $connection, string $email): ?array
{
    // Prepare an SQL query to safely select a user by email
    $stmt = mysqli_prepare(
        $connection,
        "SELECT id, email, password, role, username, created_at FROM users WHERE email = ? LIMIT 1"
    );

    // If preparation fails, return null
    if (!$stmt) {
        return null;
    }

    // Bind email parameter to the query
    mysqli_stmt_bind_param($stmt, "s", $email);

    // Execute the prepared statement
    mysqli_stmt_execute($stmt);

    // Get result set from executed query
    $result = mysqli_stmt_get_result($stmt);

    // Fetch user as associative array (or null if not found)
    $user = $result ? mysqli_fetch_assoc($result) : null;

    // Close the statement to free resources
    mysqli_stmt_close($stmt);

    // Return user data if found, otherwise null
    return $user ?: null;
}


// Checks if a user exists with the given email
function emailExists(mysqli $connection, string $email): bool
{
    // Reuse findUserByEmail to determine existence
    return findUserByEmail($connection, $email) !== null;
}


// Checks if a username already exists in the database
function usernameExists(mysqli $connection, string $username): bool
{
    // Prepare SQL statement to check username existence
    $stmt = mysqli_prepare(
        $connection,
        "SELECT id FROM users WHERE username = ? LIMIT 1"
    );

    // If preparation fails, assume username does not exist (safe fallback)
    if (!$stmt) {
        return false;
    }

    // Bind username parameter
    mysqli_stmt_bind_param($stmt, "s", $username);

    // Execute query
    mysqli_stmt_execute($stmt);

    // Get result set
    $result = mysqli_stmt_get_result($stmt);

    // Check if any row was returned
    $exists = $result && mysqli_num_rows($result) > 0;

    // Close statement
    mysqli_stmt_close($stmt);

    // Return whether username exists
    return $exists;
}


// Verifies login credentials using email and password
function verifyLogin(mysqli $connection, string $email, string $password): ?array
{
    // Retrieve user by email
    $user = findUserByEmail($connection, $email);

    // If no user found, fail login
    if (!$user) {
        return null;
    }

    // Verify password against hashed password in DB
    if (!password_verify($password, $user["password"])) {
        return null;
    }

    // Return user data if authentication succeeds
    return $user;
}


// Creates a new user and returns the inserted user ID
function createUser(
    mysqli $connection,
    string $username,
    string $email,
    string $password,
    string $role = "user"
): ?int {
    // Hash password before storing for security
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Prepare insert statement for new user
    $stmt = mysqli_prepare(
        $connection,
        "INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())"
    );

    // If preparation fails, return null
    if (!$stmt) {
        return null;
    }

    // Bind parameters to query
    mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $hashedPassword, $role);

    // Execute insert query
    $ok = mysqli_stmt_execute($stmt);

    // If execution fails, close statement and return null
    if (!$ok) {
        mysqli_stmt_close($stmt);
        return null;
    }

    // Get ID of newly inserted user (auto-increment primary key)
    $insertedId = (int) mysqli_insert_id($connection);

    // Close statement
    mysqli_stmt_close($stmt);

    // Return new user ID
    return $insertedId;
}
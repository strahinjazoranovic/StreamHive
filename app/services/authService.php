<?php

function findUserByEmail(mysqli $connection, string $email): ?array
{
    $stmt = mysqli_prepare(
        $connection,
        "SELECT id, email, password, role, username, created_at FROM users WHERE email = ? LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = $result ? mysqli_fetch_assoc($result) : null;

    mysqli_stmt_close($stmt);

    return $user ?: null;
}

function emailExists(mysqli $connection, string $email): bool
{
    return findUserByEmail($connection, $email) !== null;
}

function usernameExists(mysqli $connection, string $username): bool
{
    $stmt = mysqli_prepare(
        $connection,
        "SELECT id FROM users WHERE username = ? LIMIT 1"
    );

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $exists = $result && mysqli_num_rows($result) > 0;

    mysqli_stmt_close($stmt);

    return $exists;
}

function verifyLogin(mysqli $connection, string $email, string $password): ?array
{
    $user = findUserByEmail($connection, $email);

    if (!$user) {
        return null;
    }

    if (!password_verify($password, $user["password"])) {
        return null;
    }

    return $user;
}

function createUser(
    mysqli $connection,
    string $username,
    string $email,
    string $password,
    string $role = "user"
): ?int {
    // Hash the password before storing it in the database
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Use a prepared statement to prevent SQL injection
    $stmt = mysqli_prepare(
        $connection,
        "INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())"
    );

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $hashedPassword, $role);
    $ok = mysqli_stmt_execute($stmt);

    if (!$ok) {
        mysqli_stmt_close($stmt);
        return null;
    }

    $insertedId = (int) mysqli_insert_id($connection);

    mysqli_stmt_close($stmt);

    return $insertedId;
}

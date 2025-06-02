<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

function register($username, $password, $role) {
    global $conn;
    
    // Check if username already exists
    $sql = "SELECT id FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_fetch_assoc($result)) {
        return false; // Username already exists
    }
    
    // Hash password and insert new user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $username, $hashed_password, $role);
    
    return mysqli_stmt_execute($stmt);
}

function login($username, $password) {
    global $conn;
    
    $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            return true;
        }
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isCashier() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'cashier';
}

function isKitchen() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'kitchen';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /Pos/index.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: /Pos/index.php");
        exit();
    }
}

function requireCashier() {
    requireLogin();
    if (!isCashier() && !isAdmin()) {
        header("Location: /Pos/index.php");
        exit();
    }
}

function requireKitchen() {
    requireLogin();
    if (!isKitchen() && !isAdmin()) {
        header("Location: /Pos/index.php");
        exit();
    }
}

function logout() {
    session_destroy();
    header("Location: /Pos/index.php");
    exit();
}

// Handle logout request
if (isset($_GET['logout'])) {
    logout();
}
?> 
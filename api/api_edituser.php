<?php
session_start();
include '../config.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit();
}

// Check auth
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: ../login.php');
    exit();
}

// Only admin can edit other users
$actor_type = $_SESSION['user_type'];
$actor_id = $_SESSION['user_id'];

// Get target user id
$std_id = isset($_POST['std_id']) ? $_POST['std_id'] : '';

// If not admin, can only edit self
if ($actor_type !== 'admin') {
    $std_id = $actor_id;
}

if (empty($std_id)) {
    header('Location: ../admin/index.php?error=invalid_id');
    exit();
}

// Collect fields to update (only allow safe columns)
$fields = [];
$types = '';
$values = [];

if (isset($_POST['std_name']) && trim($_POST['std_name']) !== '') {
    $fields[] = 'std_name = ?';
    $types .= 's';
    $values[] = trim($_POST['std_name']);
}

if (isset($_POST['std_lastname']) && trim($_POST['std_lastname']) !== '') {
    $fields[] = 'std_lastname = ?';
    $types .= 's';
    $values[] = trim($_POST['std_lastname']);
}

if (isset($_POST['std_password']) && trim($_POST['std_password']) !== '') {
    $fields[] = 'std_password = ?';
    $types .= 's';
    $values[] = password_hash(trim($_POST['std_password']), PASSWORD_DEFAULT);
}

if (isset($_POST['std_gmail']) && trim($_POST['std_gmail']) !== '') {
    $fields[] = 'std_gmail = ?';
    $types .= 's';
    $values[] = trim($_POST['std_gmail']);
}

if (empty($fields)) {
    header('Location: ../admin/index.php?error=no_fields');
    exit();
}

// Build and execute prepared statement
$sql = 'UPDATE tb_student SET ' . implode(', ', $fields) . ' WHERE std_id = ? LIMIT 1';
$types .= 's';
$values[] = $std_id;

$stmt = $conn->prepare($sql);
if (!$stmt) {
    header('Location: ../admin/index.php?error=db_error');
    exit();
}

// Bind parameters dynamically
$bind_names = [];
$bind_names[] = $types;
for ($i = 0; $i < count($values); $i++) {
    $bind_name = 'bind' . $i;
    $$bind_name = $values[$i];
    $bind_names[] = &$$bind_name;
}

call_user_func_array([$stmt, 'bind_param'], $bind_names);

if (!$stmt->execute()) {
    $stmt->close();
    header('Location: ../admin/index.php?error=execute_failed');
    exit();
}

$affected = $stmt->affected_rows;
$stmt->close();

// Redirect back with success message
if ($affected > 0) {
    header('Location: ../admin/index.php?msg=updated');
} else {
    header('Location: ../admin/index.php?msg=no_changes');
}
exit();
?>
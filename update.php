<?php
require 'db.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id =$_GET['id'];

$stmt =$pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user =$stmt->fetch();

if (!$user) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $program = trim($_POST['program']);
    $year_level = trim($_POST['year_level']);

    if (!empty($name) && !empty($email) && !empty($program) && !empty($year_level)) {
        $stmt =$pdo->prepare("UPDATE users SET name = ?, email = ?, program = ?, year_level = ? WHERE id = ?");
        if ($stmt->execute([$name, $email,$program, $year_level,$id])) {
            header("Location: index.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student Record</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="form-body">
    <div class="form-card">
        <h2>Edit Student Record</h2>
        <form method="POST" class="styled-form">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>

            <div class="form-group">
                <label>Program</label>
                <input type="text" name="program" value="<?= htmlspecialchars($user['program']) ?>" required>
            </div>

            <div class="form-group">
                <label>Year Level</label>
                <select name="year_level" required>
                    <option value="1st Year" <?= $user['year_level'] === '1st Year' ? 'selected' : '' ?>>1st Year</option>
                    <option value
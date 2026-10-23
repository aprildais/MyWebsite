<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $program = trim($_POST['program']);
    $year_level = trim($_POST['year_level']);

    if (!empty($name) && !empty($email) && !empty($program) && !empty($year_level)) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, program, year_level) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $program, $year_level])) {
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
    <title>Add New Student</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background-color: #080c14;
            color: #e2e8f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .card {
            width: 100%;
            max-width: 450px;
            background: rgba(15, 23, 42, 0.9);
            padding: 30px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        h2 {
            color: #ffffff;
            font-size: 1.4rem;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 6px;
            font-weight: 500;
        }

        input, select {
            width: 100%;
            padding: 12px 14px;
            background: rgba(10, 15, 26, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            color: #ffffff;
            font-size: 0.95rem;
            outline: none;
        }

        input:focus, select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.3);
        }

        select option {
            background-color: #0f172a;
            color: #ffffff;
        }

        .btn-container {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .btn-submit {
            flex: 1;
            padding: 12px;
            background: #2563eb;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
        }

        .btn-submit:hover {
            background: #1d4ed8;
        }

        .btn-cancel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            color: #94a3b8;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Add New Student</h2>
        <form method="POST" action="create.php">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" placeholder="Enter Full Name" required>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="Enter Email Address" required>
            </div>

            <div class="form-group">
                <label>Program</label>
                <input type="text" name="program" placeholder="e.g., BSIT, BSCS" required>
            </div>

            <div class="form-group">
                <label>Year Level</label>
                <select name="year_level" required>
                    <option value="" disabled selected>Select Year Level</option>
                    <option value="1st Year">1st Year</option>
                    <option value="2nd Year">2nd Year</option>
                    <option value="3rd Year">3rd Year</option>
                    <option value="4th Year">4th Year</option>
                </select>
            </div>

            <div class="btn-container">
                <button type="submit" class="btn-submit">Submit</button>
                <a href="index.php" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
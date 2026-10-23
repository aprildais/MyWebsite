<?php
require 'db.php';

// Kunin ang active page mula sa URL parameter (Default ay 'dashboard')
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// --- DELETE LOGIC WITH AUTO-INCREMENT RESET ---
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);

    // Check kung zero/wala nang natitirang student sa database
    $countStmt = $pdo->query("SELECT COUNT(*) FROM users");
    $rowCount = $countStmt->fetchColumn();

    if ($rowCount == 0) {
        // Kapag na-delete silang lahat, i-reset ang ID counter pabalik sa 1
        $pdo->exec("ALTER TABLE users AUTO_INCREMENT = 1");
    }

    header("Location: index.php?page=students");
    exit;
}

// --- READ LOGIC (SEARCH & FILTER) ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE name LIKE ? OR email LIKE ? OR program LIKE ? ORDER BY id ASC");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY id ASC");
}
$users = $stmt->fetchAll();

// Statistics Counts
$total_students = count($users);

// Reports Analytics Computations
$programCounts = ['BSIT' => 0, 'BSCS' => 0, 'BSAIS' => 0, 'BSBA' => 0, 'Others' => 0];
foreach ($users as $u) {
    $prog = strtoupper(trim($u['program']));
    if (array_key_exists($prog, $programCounts)) {
        $programCounts[$prog]++;
    } else {
        $programCounts['Others']++;
    }
}

// Highest count for scaling bar charts dynamically
$maxCount = max(array_values($programCounts));
if ($maxCount == 0) $maxCount = 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Admin - Student Management</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Program Filter Tabs Styling */
        .program-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .tab-btn {
            background: rgba(255, 255, 255, 0.08);
            color: #94a3b8;
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .tab-btn:hover {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }

        .schedule-section {
            display: none;
        }

        .schedule-section.active {
            display: block;
        }

        /* Analytics Dashboard Cards Grid */
        .visual-analytics-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-top: 20px;
            margin-bottom: 25px;
        }

        @media (max-width: 900px) {
            .visual-analytics-container {
                grid-template-columns: 1fr;
            }
        }

        .chart-card {
            background: rgba(10, 15, 26, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .chart-card h3 {
            color: #ffffff;
            font-size: 1.05rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        /* Vertical Bar Chart Styles */
        .bar-chart {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            height: 180px;
            padding-top: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .bar-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            width: 50px;
            height: 100%;
            justify-content: flex-end;
        }

        .bar-fill {
            width: 32px;
            background: linear-gradient(180deg, #818cf8 0%, #3b82f6 100%);
            border-radius: 8px 8px 4px 4px;
            transition: height 0.5s ease-in-out;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
            min-height: 4px;
        }

        .bar-label {
            color: #94a3b8;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .bar-value {
            color: #a5b4fc;
            font-size: 0.75rem;
            font-weight: 700;
        }

        /* Circular Progress Ring Styles */
        .circle-container {
            position: relative;
            width: 160px;
            height: 160px;
            margin: 10px auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .circle-svg {
            transform: rotate(-90deg);
            width: 100%;
            height: 100%;
        }

        .circle-bg {
            fill: none;
            stroke: rgba(255, 255, 255, 0.08);
            stroke-width: 10;
        }

        .circle-progress {
            fill: none;
            stroke: url(#gradient);
            stroke-width: 10;
            stroke-linecap: round;
            transition: stroke-dashoffset 0.6s ease;
        }

        .circle-text {
            position: absolute;
            text-align: center;
        }

        .circle-text .percentage {
            font-size: 1.8rem;
            font-weight: 700;
            color: #ffffff;
            display: block;
        }

        .circle-text .sublabel {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        /* Progress Bar Visual Representation inside Table */
        .progress-bar-bg {
            width: 100%;
            height: 10px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #6366f1 0%, #3b82f6 100%);
            border-radius: 20px;
            transition: width 0.4s ease-in-out;
            box-shadow: 0 0 10px rgba(99, 102, 241, 0.5);
        }

        /* Settings Forms Grid */
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .settings-card {
            background: rgba(10, 15, 26, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 24px;
        }

        .settings-card h3 {
            color: #ffffff;
            font-size: 1.1rem;
            margin-bottom: 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 10px;
        }

        .form-group-settings {
            margin-bottom: 16px;
        }

        .form-group-settings label {
            display: block;
            color: #94a3b8;
            font-size: 0.85rem;
            margin-bottom: 6px;
            font-weight: 500;
        }

        .form-group-settings input, .form-group-settings select {
            width: 100%;
            padding: 10px 14px;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 8px;
            color: #ffffff;
            font-size: 0.9rem;
            outline: none;
        }

        .form-group-settings input:focus, .form-group-settings select:focus {
            border-color: #3b82f6;
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="brand">
                <h2>Smart Admin</h2>
            </div>
            <nav class="nav-menu">
                <a href="index.php?page=dashboard" class="nav-item <?= $page === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
                <a href="index.php?page=students" class="nav-item <?= $page === 'students' ? 'active' : '' ?>">Students</a>
                <a href="index.php?page=programs" class="nav-item <?= $page === 'programs' ? 'active' : '' ?>">Programs</a>
                <a href="index.php?page=schedules" class="nav-item <?= $page === 'schedules' ? 'active' : '' ?>">Schedules</a>
                <a href="index.php?page=reports" class="nav-item <?= $page === 'reports' ? 'active' : '' ?>">Reports</a>
                <a href="index.php?page=settings" class="nav-item <?= $page === 'settings' ? 'active' : '' ?>">Settings</a>
            </nav>
            <div class="sidebar-footer">
                <a href="javascript:void(0)" onclick="openLogoutModal()" class="nav-item logout">Log Out</a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Top Search Bar -->
            <header class="top-bar">
                <form method="GET" action="index.php" class="search-form">
                    <input type="hidden" name="page" value="<?= htmlspecialchars($page) ?>">
                    <input type="text" name="search" placeholder="Search student name, email, or program..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn-search">Search</button>
                    <?php if ($search !== ''): ?>
                        <a href="index.php?page=<?= $page ?>" class="btn-clear">Clear</a>
                    <?php endif; ?>
                </form>
                <div class="user-profile">
                    <span>Admin User</span>
                </div>
            </header>

            <!-- DYNAMIC CONTENT SWITCHING -->
            <?php if ($page === 'dashboard' || $page === 'students'): ?>
                <!-- Banner & Quick Stats -->
                <section class="dashboard-header">
                    <div class="welcome-card">
                        <h2>Student Portal Overview</h2>
                        <p>Manage student records, enrollment programs, and academic details efficiently.</p>
                    </div>
                    <div class="stat-card">
                        <h3>Total Students</h3>
                        <p class="stat-number"><?= $total_students ?></p>
                    </div>
                </section>

                <!-- Student Records Table -->
                <section class="table-container">
                    <div class="table-header">
                        <h3>Student Records</h3>
                        <div class="header-actions">
                            <a href="create.php" class="btn-primary">+ Add New Student</a>
                            <button onclick="window.print()" class="btn-secondary">Export / Print</button>
                        </div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Program</th>
                                <th>Year Level</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($users) > 0): ?>
                                <?php 
                                $rowNumber = 1; 
                                foreach ($users as $user): 
                                ?>
                                    <tr>
                                        <td><?= $rowNumber++ ?></td>
                                        <td><?= htmlspecialchars($user['name']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><span class="badge-program"><?= htmlspecialchars($user['program']) ?></span></td>
                                        <td><?= htmlspecialchars($user['year_level']) ?></td>
                                        <td class="action-cell">
                                            <a href="update.php?id=<?= $user['id'] ?>" class="btn-edit">Edit</a>
                                            <button onclick="openDeleteModal(<?= $user['id'] ?>)" class="btn-delete">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #64748b; padding: 30px;">
                                        No student records found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </section>

            <?php elseif ($page === 'programs'): ?>
                <section class="table-container">
                    <h2>Academic Programs</h2>
                    <p style="color: #94a3b8; margin-top: 10px;">Overview of available academic courses and departments.</p>
                    <table style="margin-top: 20px;">
                        <thead>
                            <tr>
                                <th>Program Code</th>
                                <th>Course Title</th>
                                <th>Department</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>BSIT</td><td>Bachelor of Science in Information Technology</td><td>College of Computer Studies</td></tr>
                            <tr><td>BSCS</td><td>Bachelor of Science in Computer Science</td><td>College of Computer Studies</td></tr>
                            <tr><td>BSIS</td><td>Bachelor of Science in Information Systems</td><td>College of Computer Studies</td></tr>
                            <tr><td>BSAIS</td><td>Bachelor of Science in Accounting Information Systems</td><td>College of Business & Accountancy</td></tr>
                            <tr><td>BSBA</td><td>Bachelor of Science in Business Administration</td><td>College of Business & Accountancy</td></tr>
                        </tbody>
                    </table>
                </section>

            <?php elseif ($page === 'schedules'): ?>
                <section class="table-container">
                    <h2>Class Schedules</h2>
                    <p style="color: #94a3b8; margin-top: 10px; margin-bottom: 20px;">Select a program below to view its current semester timetable.</p>
                    
                    <!-- Program Selection Tabs -->
                    <div class="program-tabs">
                        <button class="tab-btn active" onclick="showSchedule('bsit', this)">BSIT</button>
                        <button class="tab-btn" onclick="showSchedule('bscs', this)">BSCS</button>
                        <button class="tab-btn" onclick="showSchedule('bsais', this)">BSAIS</button>
                        <button class="tab-btn" onclick="showSchedule('bsba', this)">BSBA</button>
                    </div>

                    <!-- BSIT Schedules -->
                    <div id="bsit" class="schedule-section active">
                        <h3 style="color: #60a5fa; margin-bottom: 15px; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                            <span class="badge-program">BSIT</span> Bachelor of Science in Information Technology
                        </h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Subject Title</th>
                                    <th>Day(s)</th>
                                    <th>Time</th>
                                    <th>Room / Lab</th>
                                    <th>Instructor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>IT 311</td>
                                    <td>Web Systems and Technologies</td>
                                    <td>Mon / Wed</td>
                                    <td>08:00 AM - 10:30 AM</td>
                                    <td>ComLab 2</td>
                                    <td>Mr. Peter Paul Martinez</td>
                                </tr>
                                <tr>
                                    <td>IT 313</td>
                                    <td>Database Management Systems</td>
                                    <td>Fri</td>
                                    <td>01:00 PM - 05:00 PM</td>
                                    <td>ComLab 2</td>
                                    <td>Mr. Christopher Benedict Manning</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- BSCS Schedules -->
                    <div id="bscs" class="schedule-section">
                        <h3 style="color: #60a5fa; margin-bottom: 15px; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                            <span class="badge-program">BSCS</span> Bachelor of Science in Computer Science
                        </h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Subject Title</th>
                                    <th>Day(s)</th>
                                    <th>Time</th>
                                    <th>Room / Lab</th>
                                    <th>Instructor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>CS 212</td>
                                    <td>Data Structures and Algorithms</td>
                                    <td>Tue / Thu</td>
                                    <td>10:30 AM - 01:00 PM</td>
                                    <td>ComLab 1</td>
                                    <td>Mr. Rogen Asistol</td>
                                </tr>
                                <tr>
                                    <td>GE 108</td>
                                    <td>Ethics and Professional Conduct</td>
                                    <td>Wed</td>
                                    <td>01:00 PM - 04:00 PM</td>
                                    <td>Room 305</td>
                                    <td>Mr. Jed Samson</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- BSAIS Schedules -->
                    <div id="bsais" class="schedule-section">
                        <h3 style="color: #60a5fa; margin-bottom: 15px; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                            <span class="badge-program">BSAIS</span> Bachelor of Science in Accounting Information Systems
                        </h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Subject Title</th>
                                    <th>Day(s)</th>
                                    <th>Time</th>
                                    <th>Room / Lab</th>
                                    <th>Instructor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>AIS 201</td>
                                    <td>Accounting Information Systems Controls</td>
                                    <td>Mon / Wed</td>
                                    <td>10:00 AM - 12:00 PM</td>
                                    <td>ComLab 3</td>
                                    <td>Ms. Elena Torralba</td>
                                </tr>
                                <tr>
                                    <td>ACC 302</td>
                                    <td>Financial Accounting and Reporting</td>
                                    <td>Tue / Thu</td>
                                    <td>01:00 PM - 03:30 PM</td>
                                    <td>Room 203</td>
                                    <td>Mr. Roberto Fernandez</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- BSBA Schedules -->
                    <div id="bsba" class="schedule-section">
                        <h3 style="color: #60a5fa; margin-bottom: 15px; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                            <span class="badge-program">BSBA</span> Bachelor of Science in Business Administration
                        </h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Subject Title</th>
                                    <th>Day(s)</th>
                                    <th>Time</th>
                                    <th>Room / Lab</th>
                                    <th>Instructor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>BM 101</td>
                                    <td>Principles of Management</td>
                                    <td>Mon / Thu</td>
                                    <td>10:00 AM - 12:00 PM</td>
                                    <td>Room 201</td>
                                    <td>Ms. Maria Santos</td>
                                </tr>
                                <tr>
                                    <td>MKT 202</td>
                                    <td>Marketing Management</td>
                                    <td>Tue / Fri</td>
                                    <td>02:00 PM - 04:00 PM</td>
                                    <td>Room 204</td>
                                    <td>Mr. Juan Dela Cruz</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

            <?php elseif ($page === 'reports'): ?>
                <section class="table-container">
                    <div class="table-header">
                        <div>
                            <h2>Reports & Analytics</h2>
                            <p style="color: #94a3b8; margin-top: 5px;">System analytics and downloadable student enrollment logs.</p>
                        </div>
                        <button onclick="window.print()" class="btn-primary">Download Summary Report</button>
                    </div>

                    <!-- Visual Charts Section (Bar Chart & Circular Ring) -->
                    <div class="visual-analytics-container">
                        <!-- Vertical Bar Chart -->
                        <div class="chart-card">
                            <h3>Program Enrollment Breakdown</h3>
                            <div class="bar-chart">
                                <?php foreach (['BSIT', 'BSCS', 'BSAIS', 'BSBA'] as $progCode): ?>
                                    <?php 
                                        $cnt = $programCounts[$progCode];
                                        $heightPercent = $maxCount > 0 ? round(($cnt / $maxCount) * 100) : 0;
                                    ?>
                                    <div class="bar-wrapper">
                                        <span class="bar-value"><?= $cnt ?></span>
                                        <div class="bar-fill" style="height: <?= max($heightPercent, 5) ?>%;"></div>
                                        <span class="bar-label"><?= $progCode ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Circular Progress Ring (Capacity / Enrollment Ratio) -->
                        <div class="chart-card" style="text-align: center;">
                            <h3>Total Capacity Goal</h3>
                            <?php 
                                $capacityTarget = 50; // Target maximum students for portal
                                $capacityPercentage = $capacityTarget > 0 ? min(round(($total_students / $capacityTarget) * 100), 100) : 0;
                                $dashOffset = 283 - (283 * $capacityPercentage / 100);
                            ?>
                            <div class="circle-container">
                                <svg class="circle-svg" viewBox="0 0 100 100">
                                    <defs>
                                        <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="#a855f7" />
                                            <stop offset="100%" stop-color="#3b82f6" />
                                        </linearGradient>
                                    </defs>
                                    <circle class="circle-bg" cx="50" cy="50" r="45"></circle>
                                    <circle class="circle-progress" cx="50" cy="50" r="45"
                                            stroke-dasharray="283"
                                            stroke-dashoffset="<?= $dashOffset ?>"></circle>
                                </svg>
                                <div class="circle-text">
                                    <span class="percentage"><?= $capacityPercentage ?>%</span>
                                    <span class="sublabel"><?= $total_students ?> Enrolled</span>
                                </div>
                            </div>
                            <p style="color: #94a3b8; font-size: 0.8rem; margin-top: 10px;">Target Limit: <?= $capacityTarget ?> Students</p>
                        </div>
                    </div>

                    <!-- Program Enrollment Summary Table with Progress Bars -->
                    <h3 style="color: #ffffff; font-size: 1.05rem; margin-top: 25px; margin-bottom: 15px;">Program Distribution Summary</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Program Code</th>
                                <th>Department</th>
                                <th>Total Enrolled Students</th>
                                <th>Percentage</th>
                                <th style="width: 30%;">Visual Representation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (['BSIT', 'BSCS', 'BSAIS', 'BSBA'] as $progCode): ?>
                                <?php 
                                    $cnt = $programCounts[$progCode]; 
                                    $pct = $total_students > 0 ? round(($cnt / $total_students) * 100, 1) : 0;
                                    $dept = ($progCode === 'BSIT' || $progCode === 'BSCS') ? 'College of Computer Studies' : 'College of Business & Accountancy';
                                ?>
                                <tr>
                                    <td><span class="badge-program"><?= $progCode ?></span></td>
                                    <td><?= $dept ?></td>
                                    <td><?= $cnt ?></td>
                                    <td><strong><?= $pct ?>%</strong></td>
                                    <td>
                                        <div class="progress-bar-bg">
                                            <div class="progress-bar-fill" style="width: <?= $pct ?>%;"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>

            <?php elseif ($page === 'settings'): ?>
                <section class="table-container">
                    <h2>Admin Settings</h2>
                    <p style="color: #94a3b8; margin-top: 5px; margin-bottom: 20px;">Manage administrator accounts and portal preferences.</p>

                    <div class="settings-grid">
                        <!-- Profile Settings -->
                        <div class="settings-card">
                            <h3>Admin Profile</h3>
                            <form onsubmit="event.preventDefault(); alert('Profile settings saved!');">
                                <div class="form-group-settings">
                                    <label>Administrator Name</label>
                                    <input type="text" value="Admin User">
                                </div>
                                <div class="form-group-settings">
                                    <label>Email Address</label>
                                    <input type="email" value="admin@smartportal.edu.ph">
                                </div>
                                <div class="form-group-settings">
                                    <label>New Password</label>
                                    <input type="password" placeholder="Leave blank to keep unchanged">
                                </div>
                                <button type="submit" class="btn-primary" style="margin-top: 10px; cursor: pointer;">Save Profile</button>
                            </form>
                        </div>

                        <!-- System Configuration -->
                        <div class="settings-card">
                            <h3>System Configuration</h3>
                            <form onsubmit="event.preventDefault(); alert('System configuration updated!');">
                                <div class="form-group-settings">
                                    <label>Current Semester</label>
                                    <select>
                                        <option selected>1st Semester 2026-2027</option>
                                        <option>2nd Semester 2026-2027</option>
                                        <option>Summer Term 2026</option>
                                    </select>
                                </div>
                                <div class="form-group-settings">
                                    <label>Max Enrollment Capacity Limit</label>
                                    <input type="number" value="50">
                                </div>
                                <div class="form-group-settings">
                                    <label>Portal Interface Mode</label>
                                    <select>
                                        <option selected>Dark Glassmorphism (Default)</option>
                                        <option>Midnight Blue Dark</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn-primary" style="margin-top: 10px; cursor: pointer;">Update System Settings</button>
                            </form>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>

    <!-- Custom Dark Delete Confirmation Modal -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal-card">
            <h3>Confirm Deletion</h3>
            <p>Are you sure you want to delete this student record? This action cannot be undone.</p>
            <div class="modal-buttons">
                <a id="confirmDeleteBtn" href="#" class="btn-modal-delete">Delete</a>
                <button onclick="closeDeleteModal()" class="btn-modal-cancel">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Custom Dark Logout Confirmation Modal -->
    <div id="logoutModal" class="modal-overlay">
        <div class="modal-card">
            <h3>Log Out</h3>
            <p>Are you sure you want to exit the admin dashboard?</p>
            <div class="modal-buttons">
                <a href="index.php" class="btn-modal-delete" style="background: rgba(59, 130, 246, 0.2); color: #60a5fa; border-color: rgba(59, 130, 246, 0.4);">Log Out</a>
                <button onclick="closeLogoutModal()" class="btn-modal-cancel">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function openDeleteModal(studentId) {
            document.getElementById('confirmDeleteBtn').href = 'index.php?delete_id=' + studentId;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }

        function openLogoutModal() {
            document.getElementById('logoutModal').classList.add('active');
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.remove('active');
        }

        // Program Schedule Filter Switcher
        function showSchedule(programId, btnElement) {
            // Itago ang lahat ng schedule tables
            const sections = document.querySelectorAll('.schedule-section');
            sections.forEach(section => section.classList.remove('active'));

            // Alisin ang active class sa lahat ng buttons
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));

            // Ipakita ang napiling program table at i-highlight ang button
            document.getElementById(programId).classList.add('active');
            btnElement.classList.add('active');
        }
    </script>
</body>
</html>
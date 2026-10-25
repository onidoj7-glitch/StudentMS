
<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->query("SELECT COUNT(*) FROM students");
$totalStudents = $stmt->fetchColumn();

$stmt = $pdo->query(
    "SELECT * FROM students ORDER BY id DESC LIMIT 5"
);
$recentStudents = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Student Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
</head>
<body>
    <div class="app">
        <aside class="sidebar">
            <h2 class="brand">Student<span>MS</span></h2>

            <nav>
                <a href="index.php" class="nav-link active">
                    Dashboard
                </a>
                <a href="students.php" class="nav-link">
                    Students
                </a>
            </nav>

            <a href="logout.php" class="nav-link logout-link">
                Logout
            </a>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div>
                    <h1>Dashboard</h1>
                    <p>Welcome back, <?= htmlspecialchars(
                        $_SESSION["username"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>!</p>
                </div>

                <button id="theme-toggle" type="button">
                    Dark Mode
                </button>
            </header>

            <section class="stats-grid">
                <article class="stat-card">
                    <p>Total Students</p>
                    <h2><?= (int) $totalStudents ?></h2>
                    <span>Registered student records</span>
                </article>
            </section>

            <section class="content-card">
                <div class="section-heading">
                    <h2>Recently Added Students</h2>
                    <a href="students.php" class="primary-link">
                        Manage Students
                    </a>
                </div>

                <?php if (count($recentStudents) > 0): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Course</th>
                                    <th>Year Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentStudents as $student): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($student["student_id"], ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars($student["first_name"] . " " . $student["last_name"], ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars($student["course"], ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= (int) $student["year_level"] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
<?php else: ?>
    <p>No students found.</p>
<?php endif; ?>
</section>
</main>
</div>
</body>
</html>


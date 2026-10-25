
<?php
session_start();
require_once "database.php";

// Protect this page from users who are not logged in.
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Create a token to help protect forms from forged requests.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_token"];
$message = "";
$error = "";
$editStudent = null;

// Helper function for safe HTML output.
function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

// Handle form submissions.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $submittedToken = $_POST["csrf_token"] ?? "";

    if (!is_string($submittedToken) ||
        !hash_equals($csrfToken, $submittedToken)) {
        http_response_code(403);
        exit("Invalid request token. Refresh the page and try again.");
    }

    $action = $_POST["action"] ?? "";

    if ($action === "add" || $action === "edit") {
        $id = filter_var(
            $_POST["id"] ?? null,
            FILTER_VALIDATE_INT
        );

        $studentId = trim($_POST["student_id"] ?? "");
        $firstName = trim($_POST["first_name"] ?? "");
        $lastName = trim($_POST["last_name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $course = trim($_POST["course"] ?? "");
        $yearLevel = filter_var(
            $_POST["year_level"] ?? null,
            FILTER_VALIDATE_INT
        );

        
        if (
            $studentId === "" ||
            $firstName === "" ||
            $lastName === "" ||
            $email === "" ||
            $course === "" ||
            strlen($studentId) > 30 ||
            strlen($firstName) > 100 ||
            strlen($lastName) > 100 ||
            strlen($course) > 100 ||
            strlen($email) > 150
        ) {
            $error = "Please fill in all required fields and check the allowed lengths.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address, such as name@example.com.";
        } elseif (
            $yearLevel === false ||
            $yearLevel === null ||
            $yearLevel < 1 ||
            $yearLevel > 4
        ) {
            $error = "Please select a valid year level from 1 to 4.";
        } elseif (
            $action === "edit" &&
            ($id === false || $id === null || $id < 1)
        ) {
            $error = "Invalid student record.";
        } else {
            try {
                if ($action === "add") {
                    $stmt = $pdo->prepare(
                        "INSERT INTO students
                        (student_id, first_name, last_name,
                         email, course, year_level)
                        VALUES (?, ?, ?, ?, ?, ?)"
                    );

                    $stmt->execute([
                        $studentId,
                        $firstName,
                        $lastName,
                        $email === "" ? null : $email,
                        $course,
                        $yearLevel
                    ]);

                    $message = "Student added successfully!";
                } else {
                    $stmt = $pdo->prepare(
                        "UPDATE students
                         SET student_id = ?, first_name = ?,
                             last_name = ?, email = ?,
                             course = ?, year_level = ?
                         WHERE id = ?"
                    );

                    $stmt->execute([
                        $studentId,
                        $firstName,
                        $lastName,
                        $email === "" ? null : $email,
                        $course,
                        $yearLevel,
                        $id
                    ]);

                    $message = "Student updated successfully!";
                }
            } catch (PDOException $exception) {
                if ($exception->getCode() === "23000") {
                    $error = "That student ID is already in use.";
                } else {
                    error_log($exception->getMessage());
                    $error = "Unable to save the student. Please try again.";
                }
            }
        }
    } elseif ($action === "delete") {
        $id = filter_var(
            $_POST["id"] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($id === false || $id < 1) {
            $error = "Invalid student record.";
        } else {
            $stmt = $pdo->prepare(
                "DELETE FROM students WHERE id = ?"
            );
            $stmt->execute([$id]);

            $message = $stmt->rowCount() > 0
                ? "Student deleted successfully!"
                : "Student record was not found.";
        }
    }
}

// Load a student for editing.
if (isset($_GET["edit"])) {
    $editId = filter_var(
        $_GET["edit"],
        FILTER_VALIDATE_INT
    );

    if ($editId !== false && $editId > 0) {
        $stmt = $pdo->prepare(
            "SELECT * FROM students WHERE id = ?"
        );
        $stmt->execute([$editId]);
        $editStudent = $stmt->fetch() ?: null;
    }
}

// Search by student ID, first name, last name, or course.


$search = trim($_GET["search"] ?? "");
$courseFilter = trim($_GET["course"] ?? "");

// Get the available courses for the filter.
$courseStmt = $pdo->query(
    "SELECT DISTINCT course FROM students ORDER BY course ASC"
);
$courses = $courseStmt->fetchAll(PDO::FETCH_COLUMN);

// Build the search conditions.
$conditions = [];
$params = [];

if ($search !== "") {
    $conditions[] = "(student_id LIKE ?
        OR first_name LIKE ?
        OR last_name LIKE ?
        OR course LIKE ?)";

    $term = "%" . $search . "%";
    $params = [$term, $term, $term, $term];
}

if ($courseFilter !== "") {
    $conditions[] = "course = ?";
    $params[] = $courseFilter;
}

// Build the query without the pagination limit first.
$sql = "SELECT * FROM students";

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY id DESC";

// Count students matching the search and course filter.
$countSql = "SELECT COUNT(*) FROM students";

if (!empty($conditions)) {
    $countSql .= " WHERE " . implode(" AND ", $conditions);
}

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalStudents = (int) $countStmt->fetchColumn();

// Calculate pagination.
$perPage = 10;
$page = max(1, (int) ($_GET["page"] ?? 1));
$totalPages = max(1, (int) ceil($totalStudents / $perPage));

// Keep the page number within the valid range.
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Fetch only the students for this page.
$sql .= " LIMIT " . $perPage . " OFFSET " . $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students | Student Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <h2 class="brand">Student<span>MS</span></h2>

        <nav>
            <a href="index.php" class="nav-link">Dashboard</a>
            <a href="students.php" class="nav-link active">Students</a>
        </nav>

        <a href="logout.php" class="nav-link logout-link">Logout</a>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Student Records</h1>
                <p>Manage your student information.</p>
            </div>

            <button id="theme-toggle" type="button">Dark Mode</button>
        </header>

        <?php if ($message !== ""): ?>
            <p class="success-message"><?= e($message) ?></p>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <p class="error-message"><?= e($error) ?></p>
        <?php endif; ?>

        <section class="content-card student-form-card">
            <h2><?= $editStudent ? "Edit Student" : "Add New Student" ?></h2>

            <form method="POST" action="students.php" class="student-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="<?= $editStudent ? "edit" : "add" ?>">

                <?php if ($editStudent): ?>
                    <input type="hidden" name="id" value="<?= (int) $editStudent["id"] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="student_id">Student ID *</label>
                    <input
                        id="student_id"
                        name="student_id"
                        maxlength="30"
                        required
                        value="<?= e($editStudent["student_id"] ?? "") ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input
                        id="first_name"
                        name="first_name"
                        maxlength="100"
                        required
                        value="<?= e($editStudent["first_name"] ?? "") ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input
                        id="last_name"
                        name="last_name"
                        maxlength="100"
                        required
                        value="<?= e($editStudent["last_name"] ?? "") ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email</label>   
<input
    type="email"
    id="email"
    name="email"
    maxlength="150"
    required
    placeholder="name@example.com"
    value="<?= e($editStudent["email"] ?? "") ?>"
>
                </div>

                <div class="form-group">
                    <label for="course">Course *</label>
                    <input
                        id="course"
                        name="course"
                        maxlength="100"
                        required
                        placeholder="e.g. BSIT"
                        value="<?= e($editStudent["course"] ?? "") ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="year_level">Year Level *</label>
                    <select id="year_level" name="year_level" required>
                        <option value="">Select year</option>
                        <?php for ($year = 1; $year <= 4; $year++): ?>
                            <option
                                value="<?= $year ?>"
                                <?= isset($editStudent["year_level"]) &&
                                    (int) $editStudent["year_level"] === $year
                                    ? "selected" : "" ?>
                            >
                                Year <?= $year ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit">
                        <?= $editStudent ? "Save Changes" : "Add Student" ?>
                    </button>

                    <?php if ($editStudent): ?>
                        <a class="cancel-link" href="students.php">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="content-card">
            <div class="section-heading">
                <h2>All Students (<?= count($students) ?>)</h2>

                
<form method="GET" action="students.php" class="search-form">
    <input
        type="search"
        name="search"
        placeholder="Search students..."
        value="<?= e($search) ?>"
        aria-label="Search by student ID, name, or course"
    >

    <select name="course" aria-label="Filter by course">
        <option value="">All Courses</option>

        <?php foreach ($courses as $course): ?>
            <option
                value="<?= e($course) ?>"
                <?= $courseFilter === $course ? "selected" : "" ?>
            >
                <?= e($course) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Search</button>

    <?php if ($search !== "" || $courseFilter !== ""): ?>
        <a class="cancel-link" href="students.php">Clear</a>
    <?php endif; ?>
</form>

            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Course</th>
                            <th>Year</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($students) > 0): ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?= e($student["student_id"]) ?></td>
                                <td><?= e($student["first_name"] . " " . $student["last_name"]) ?></td>
                                <td><?= e($student["email"] ?? "") ?></td>
                                <td><?= e($student["course"]) ?></td>
                                <td><?= (int) $student["year_level"] ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a
                                            class="edit-link"
                                            href="students.php?edit=<?= (int) $student["id"] ?>"
                                        >Edit</a>

                                        <form
                                            method="POST"
                                            action="students.php"
                                            class="delete-form"
                                            onsubmit="return confirm('Delete this student record?');"
                                        >
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $student["id"] ?>">
                                            <button type="submit" class="delete-button">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="empty-message">
                                No student records found.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>                 
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?<?= http_build_query([
            "search" => $search,
            "course" => $courseFilter,
            "page" => $page - 1
        ]) ?>">Previous</a>
    <?php endif; ?>

    <span>Page <?= $page ?> of <?= $totalPages ?></span>

    <?php if ($page < $totalPages): ?>
        <a href="?<?= http_build_query([
            "search" => $search,
            "course" => $courseFilter,
            "page" => $page + 1
        ]) ?>">Next</a>
    <?php endif; ?>
</div>
            </div>
        </section>
    </main>
</div>
</body>
</html>

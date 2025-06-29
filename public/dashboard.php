<?php
include '../config/db_config.php';

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

// Handle test creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["create_test"])) {
    $test_name = trim($_POST["test_name"]);

    if (!empty($test_name)) {
        $sql = "INSERT INTO tests (test_name, created_by) VALUES (?, ?)";

        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $test_name, $_SESSION["id"]);

            if (mysqli_stmt_execute($stmt)) {
                $test_id = mysqli_insert_id($conn);
                header("location: manage_test.php?test_id=" . $test_id);
                exit;
            } else {
                echo "<script>alert('Error creating test.');</script>";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Handle delete
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_test"])) {
    $test_id = intval($_POST["delete_test_id"]);
    $sql = "DELETE FROM tests WHERE test_id = ? AND created_by = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $test_id, $_SESSION["id"]);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    header("location: dashboard.php");
    exit;
}

// Handle rename
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["rename_test"])) {
    $test_id = intval($_POST["rename_test_id"]);
    $new_test_name = trim($_POST["new_test_name"]);
    
    if (!empty($new_test_name)) {
        $sql = "UPDATE tests SET test_name = ? WHERE test_id = ? AND created_by = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sii", $new_test_name, $test_id, $_SESSION["id"]);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
    header("location: dashboard.php");
    exit;
}

// Fetch all tests created by this staff member
$tests = [];
$sql = "SELECT * FROM tests WHERE created_by = ? ORDER BY created_at DESC";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $tests[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brilliant IAS Academy - Staff Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
        <link rel="icon" href="../assets/logo.jpg" type="image/x-icon">

    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
        }

        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: 0.5px;
        }

        .navbar-brand img {
            height: 40px;
            margin-right: 10px;
        }

        .page-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 12px 12px 0 0 !important;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
        }

        .card-body {
            padding: 1.5rem;
        }

        .test-card {
            background: linear-gradient(135deg, #ecf0f1, #e3f2fd);
            border-left: 6px solid var(--secondary-color);
            border-radius: 15px;
            transition: all 0.3s ease-in-out;
        }

        .test-card:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 20px rgba(52, 152, 219, 0.2);
        }

        .test-card .card-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.75rem;
        }

        .test-card .card-text {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .test-card .meta {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .btn-action {
            border-radius: 6px;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        .btn-edit {
            background-color: #3498db;
            border-color: #3498db;
        }

        .btn-view {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-delete {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }

        .btn-manage {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
            color: white;
        }

        .btn-rename {
            background-color: #9b59b6;
            border-color: #9b59b6;
            color: white;
        }

        .badge-count {
            background-color: var(--secondary-color);
            font-size: 0.9rem;
            font-weight: 500;
            padding: 0.35em 0.65em;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state-icon {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }

        .stats-card {
            border-left: 4px solid var(--secondary-color);
            margin-bottom: 1.5rem;
        }

        .stats-card .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stats-card .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <img src="../assets/logo.jpg" alt="Brilliant IAS Academy Logo">
                Brilliant IAS Academy
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">Welcome, <?php echo $_SESSION['name']; ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2 class="mb-0"><i class="bi bi-journal-text me-2"></i>Test Management</h2>
                    <p class="text-muted mb-0">Create and manage your tests</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTestModal">
                        <i class="bi bi-plus-circle me-1"></i> Create New Test
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="stat-value"><?php echo count($tests); ?></div>
                        <div class="stat-label">Total Tests</div>
                        <div class="text-muted mt-2"><small><i class="bi bi-arrow-up text-success"></i> 5 from last month</small></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="stat-value">42</div>
                        <div class="stat-label">Active Students</div>
                        <div class="text-muted mt-2"><small><i class="bi bi-arrow-up text-success"></i> 3 new this week</small></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="stat-value">87%</div>
                        <div class="stat-label">Avg. Completion</div>
                        <div class="text-muted mt-2"><small><i class="bi bi-arrow-down text-danger"></i> 2% from last month</small></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Your Tests</h5>
                        <span class="badge badge-count"><?php echo count($tests); ?> Tests</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tests)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="bi bi-journal-x"></i>
                                </div>
                                <h5>No Tests Found</h5>
                                <p class="text-muted">Get started by creating your first test</p>
                                <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#createTestModal">
                                    <i class="bi bi-plus-circle me-1"></i> Create Test
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($tests as $test): ?>
                                    <div class="col-md-6 col-lg-6">
                                        <div class="card test-card h-100 shadow-sm border-0" style="background: linear-gradient(135deg, #f5f7fa, #dbe9f4);">
                                            <div class="card-body">
                                                <h5 class="card-title text-primary"><?php echo htmlspecialchars($test['test_name']); ?></h5>
                                                <div class="meta mb-3">
                                                    <span class="me-3"><i class="bi bi-calendar me-1"></i> <?php echo date("M d, Y", strtotime($test['created_at'])); ?></span>
                                                    <span><i class="bi bi-arrow-repeat me-1"></i> <?php echo date("M d, Y", strtotime($test['updated_at'])); ?></span>
                                                </div>
                                                <div class="d-flex flex-wrap">
                                                    <a href="manage_test.php?test_id=<?php echo $test['test_id']; ?>" class="btn btn-sm btn-action btn-manage">
                                                        <i class="bi bi-pencil-square"></i> Manage Questions
                                                    </a>
                                                    <a href="view_test.php?test_id=<?php echo $test['test_id']; ?>" class="btn btn-sm btn-action btn-view">
                                                        <i class="bi bi-eye"></i> Attend Test
                                                    </a>
                                                    <button class="btn btn-sm btn-action btn-rename" data-bs-toggle="modal" data-bs-target="#renameTestModal" 
                                                        data-test-id="<?php echo $test['test_id']; ?>" data-test-name="<?php echo htmlspecialchars($test['test_name']); ?>">
                                                        <i class="bi bi-pencil"></i> Rename
                                                    </button>
                                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this test?');" style="display:inline;">
                                                        <input type="hidden" name="delete_test_id" value="<?php echo $test['test_id']; ?>">
                                                        <button type="submit" name="delete_test" class="btn btn-sm btn-action btn-delete">
                                                            <i class="bi bi-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Test Modal -->
    <div class="modal fade" id="createTestModal" tabindex="-1" aria-labelledby="createTestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="createTestModalLabel"><i class="bi bi-plus-circle me-2"></i>Create New Test</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="test_name" class="form-label">Test Name</label>
                            <input type="text" class="form-control" id="test_name" name="test_name" placeholder="Enter test name" required>
                            <div class="form-text">Make it descriptive for easy identification.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_test" class="btn btn-primary">Create Test</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Rename Test Modal -->
    <div class="modal fade" id="renameTestModal" tabindex="-1" aria-labelledby="renameTestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="renameTestModalLabel"><i class="bi bi-pencil me-2"></i>Rename Test</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="rename_test_id" id="rename_test_id" value="">
                        <div class="mb-3">
                            <label for="new_test_name" class="form-label">New Test Name</label>
                            <input type="text" class="form-control" id="new_test_name" name="new_test_name" placeholder="Enter new test name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="rename_test" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Handle the rename modal opening
        $(document).ready(function() {
            $('#renameTestModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget); // Button that triggered the modal
                var testId = button.data('test-id'); // Extract info from data-* attributes
                var testName = button.data('test-name');
                
                var modal = $(this);
                modal.find('#rename_test_id').val(testId);
                modal.find('#new_test_name').val(testName);
            });
        });
    </script>
</body>
</html>
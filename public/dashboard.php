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

// Calculate statistics
$total_tests = count($tests);
$recent_tests = array_filter($tests, function($test) {
    return strtotime($test['created_at']) > strtotime('-30 days');
});
$recent_count = count($recent_tests);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brilliant IAS Academy - Staff Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="../assets/logo.jpg" type="image/x-icon">
    <link rel="stylesheet" href="../assets/dashboard.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <img src="../assets/logo.jpg" alt="Brilliant IAS Academy Logo">
                Brilliant IAS Academy
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3 d-none d-md-inline">Welcome back!</span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <!-- Profile Card Row -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card profile-card h-100">
                    <div class="card-body p-4 text-center position-relative">
                        <div class="profile-avatar mx-auto">
                            <?php echo strtoupper(substr($_SESSION['name'], 0, 2)); ?>
                        </div>
                        <div class="profile-name"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                        <div class="profile-role">Test Administrator</div>
                       
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="col-md-4">
                
                        <div class="card stats-card h-100">
                            <div class="stats-icon primary">
                                <i class="bi bi-journal-text"></i>
                            </div>
                            <div class="stats-value"><?php echo $total_tests; ?></div>
                            <div class="stats-label">Total Tests Created</div>
                            <div class="stats-change">
                                <span class="text-success"><i class="bi bi-arrow-up"></i> +<?php echo $recent_count; ?></span>
                                <small class="text-muted ms-1">this month</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card secondary h-100">
                            <div class="stats-icon secondary">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div class="stats-value"><?php echo $recent_count; ?></div>
                            <div class="stats-label">Recent Activity</div>
                            <div class="stats-change">
                                <span class="text-info"><i class="bi bi-clock"></i></span>
                                <small class="text-muted ms-1">last 30 days</small>
                            </div>
                        </div>
                    </div>
                </div>


        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="page-title">
                        <i class="bi bi-speedometer2 me-2"></i>Dashboard
                    </h1>
                    <p class="page-subtitle mb-0">Manage your tests and track performance</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#createTestModal">
                        <i class="bi bi-plus-circle"></i> Create New Test
                    </button>
                </div>
            </div>
        </div>

        <!-- Tests Section -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-semibold">
                                <i class="bi bi-list-check me-2 text-primary"></i>Your Tests
                            </h5>
                            <span class="badge bg-primary fs-6 px-3 py-2"><?php echo $total_tests; ?> Tests</span>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <?php if (empty($tests)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="bi bi-journal-plus"></i>
                                </div>
                                <h5>No Tests Found</h5>
                                <p class="text-muted mb-4">Get started by creating your first test to begin managing questions and assessments</p>
                                <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#createTestModal">
                                    <i class="bi bi-plus-circle me-2"></i> Create Your First Test
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="row g-4">
                                <?php foreach ($tests as $test): ?>
                                    <div class="col-lg-6">
                                        <div class="card test-card h-100">
                                            <div class="card-body p-4">
                                                <h5 class="card-title"><?php echo htmlspecialchars($test['test_name']); ?></h5>
                                                <div class="test-meta">
                                                    <span>
                                                        <i class="bi bi-calendar-plus"></i>
                                                        Created: <?php echo date("M j, Y", strtotime($test['created_at'])); ?>
                                                    </span>
                                                    <span>
                                                        <i class="bi bi-arrow-clockwise"></i>
                                                        Updated: <?php echo date("M j, Y", strtotime($test['updated_at'])); ?>
                                                    </span>
                                                </div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <a href="manage_test.php?test_id=<?php echo $test['test_id']; ?>" class="btn btn-warning btn-sm">
                                                        <i class="bi bi-gear"></i> Manage
                                                    </a>
                                                    <a href="view_test.php?test_id=<?php echo $test['test_id']; ?>" class="btn btn-success btn-sm">
                                                        <i class="bi bi-play-circle"></i> Take Test
                                                    </a>
                                                    <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#renameTestModal" 
                                                        data-test-id="<?php echo $test['test_id']; ?>" data-test-name="<?php echo htmlspecialchars($test['test_name']); ?>">
                                                        <i class="bi bi-pencil"></i> Rename
                                                    </button>
                                                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this test? This action cannot be undone.');">
                                                        <input type="hidden" name="delete_test_id" value="<?php echo $test['test_id']; ?>">
                                                        <button type="submit" name="delete_test" class="btn btn-danger btn-sm">
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
                <div class="modal-header">
                    <h5 class="modal-title" id="createTestModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>Create New Test
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="test_name" class="form-label fw-semibold">Test Name</label>
                            <input type="text" class="form-control form-control-lg" id="test_name" name="test_name" 
                                placeholder="e.g., General Knowledge Quiz 2025" required>
                            <div class="form-text">Choose a descriptive name that clearly identifies the test content and purpose.</div>
                        </div>
                    </div>
                    <div class="modal-footer p-4 pt-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_test" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Create Test
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Rename Test Modal -->
    <div class="modal fade" id="renameTestModal" tabindex="-1" aria-labelledby="renameTestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="renameTestModalLabel">
                        <i class="bi bi-pencil me-2"></i>Rename Test
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="modal-body p-4">
                        <input type="hidden" name="rename_test_id" id="rename_test_id" value="">
                        <div class="mb-3">
                            <label for="new_test_name" class="form-label fw-semibold">New Test Name</label>
                            <input type="text" class="form-control form-control-lg" id="new_test_name" name="new_test_name" 
                                placeholder="Enter new test name" required>
                            <div class="form-text">Update the test name to better reflect its content or purpose.</div>
                        </div>
                    </div>
                    <div class="modal-footer p-4 pt-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="rename_test" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i> Save Changes
                        </button>
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
                var button = $(event.relatedTarget);
                var testId = button.data('test-id');
                var testName = button.data('test-name');
                
                var modal = $(this);
                modal.find('#rename_test_id').val(testId);
                modal.find('#new_test_name').val(testName);
            });

            // Add smooth scrolling for better UX
            $('a[href^="#"]').on('click', function(event) {
                var target = $(this.getAttribute('href'));
                if( target.length ) {
                    event.preventDefault();
                    $('html, body').stop().animate({
                        scrollTop: target.offset().top - 100
                    }, 1000);
                }
            });

            // Add loading state to forms (but allow form submission)
            $('form').on('submit', function(e) {
                // Don't prevent default - let form submit normally
                var submitBtn = $(this).find('button[type="submit"]');
                setTimeout(function() {
                    submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i> Processing...');
                }, 100);
            });

            // Add animation to cards on load
            $('.card').each(function(index) {
                $(this).css('opacity', '0').delay(index * 100).animate({
                    opacity: 1
                }, 500);
            });

            // Enhanced tooltip initialization
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Real-time search functionality (if needed in future)
        function filterTests(searchTerm) {
            const testCards = document.querySelectorAll('.test-card');
            testCards.forEach(card => {
                const testName = card.querySelector('.card-title').textContent.toLowerCase();
                if (testName.includes(searchTerm.toLowerCase())) {
                    card.parentElement.style.display = 'block';
                } else {
                    card.parentElement.style.display = 'none';
                }
            });
        }

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + N to create new test
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                document.querySelector('[data-bs-target="#createTestModal"]').click();
            }
        });
    </script>
</body>
</html>
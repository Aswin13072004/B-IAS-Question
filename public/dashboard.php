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
    <style>
        :root {
            --primary-color: #1e293b;
            --secondary-color: #3b82f6;
            --accent-color: #ef4444;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --info-color: #06b6d4;
            --light-color: #f8fafc;
            --dark-color: #0f172a;
            --border-color: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-success: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Enhanced Navbar */
        .navbar {
            background: var(--gradient-primary) !important;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: var(--shadow-lg);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: -0.025em;
            color: white !important;
        }

        .navbar-brand img {
            height: 45px;
            margin-right: 12px;
            border-radius: 8px;
        }

        /* Profile Card */
        .profile-card {
            background: var(--gradient-primary) !important ;
            border-radius: 20px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }

        .profile-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            transform: translate(-20px, 20px);
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }

        .profile-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: -0.025em;
        }

        .profile-role {
            opacity: 0.9;
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .profile-stats {
            display: flex;
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .profile-stat {
            text-align: center;
        }

        .profile-stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            display: block;
        }

        .profile-stat-label {
            font-size: 0.875rem;
            opacity: 0.8;
        }

        /* Enhanced Cards */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: 1.5rem;
            background: white;
            border: 1px solid var(--border-color);
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }

        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-success);
        }

        .stats-card.secondary::before {
            background: var(--gradient-secondary);
        }

        .stats-card.warning::before {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: white;
        }

        .stats-icon.primary {
            background: var(--gradient-success);
        }

        .stats-icon.secondary {
            background: var(--gradient-secondary);
        }

        .stats-icon.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .stats-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stats-change {
            margin-top: 0.75rem;
            font-size: 0.875rem;
        }

        /* Test Cards */
        .test-card {
            background: white;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .test-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--secondary-color);
        }

        .test-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--secondary-color);
        }

        .test-card .card-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
            font-size: 1.125rem;
        }

        .test-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .test-meta span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Enhanced Buttons */
        .btn {
            font-weight: 500;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--secondary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: var(--accent-color);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .btn-info {
            background: var(--info-color);
            color: white;
        }

        .btn-info:hover {
            background: #0891b2;
            transform: translateY(-1px);
        }

        .btn-outline-light {
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
        }

        .btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: white;
            color: white;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            letter-spacing: -0.025em;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1.125rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-secondary);
        }

        .empty-state-icon {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 1.5rem;
        }

        .empty-state h5 {
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        /* Modal Enhancements */
        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: var(--shadow-xl);
        }

        .modal-header {
            background: var(--gradient-primary);
            color: white;
            border-radius: 16px 16px 0 0;
            border-bottom: none;
        }

        .form-control {
            border-radius: 8px;
            border: 2px solid var(--border-color);
            padding: 0.75rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .profile-stats {
                flex-direction: column;
                gap: 1rem;
            }
            
            .test-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
                margin-bottom: 0.5rem;
            }
        }
    </style>
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
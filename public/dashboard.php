<?php
include '../config/db_config.php';

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Handle test creation
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["create_test"])){
    $test_name = trim($_POST["test_name"]);
    
    if(!empty($test_name)){
        $sql = "INSERT INTO tests (test_name, created_by) VALUES (?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "si", $test_name, $_SESSION["id"]);
            
            if(mysqli_stmt_execute($stmt)){
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

// Fetch all tests created by this staff member
$tests = [];
$sql = "SELECT * FROM tests WHERE created_by = ? ORDER BY created_at DESC";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($result)){
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
            border-bottom: 1px solid rgba(0,0,0,0.1);
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
            border-left: 4px solid var(--secondary-color);
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
                        <?php if(empty($tests)): ?>
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
                                <?php foreach($tests as $test): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card test-card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($test['test_name']); ?></h5>
                                            <div class="meta mb-3">
                                                <span class="me-3"><i class="bi bi-calendar me-1"></i> <?php echo date("M d, Y", strtotime($test['created_at'])); ?></span>
                                                <span><i class="bi bi-arrow-repeat me-1"></i> <?php echo date("M d, Y", strtotime($test['updated_at'])); ?></span>
                                            </div>
                                            <div class="d-flex flex-wrap">
                                                <a href="manage_test.php?test_id=<?php echo $test['test_id']; ?>" class="btn btn-sm btn-action btn-manage">
                                                    <i class="bi bi-pencil-square"></i> Manage
                                                </a>
                                                <a href="view_test.php?test_id=<?php echo $test['test_id']; ?>" class="btn btn-sm btn-action btn-view">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                                <button class="btn btn-sm btn-action btn-edit" data-bs-toggle="modal" data-bs-target="#editTestModal" data-id="<?php echo $test['test_id']; ?>" data-name="<?php echo htmlspecialchars($test['test_name']); ?>">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-action btn-delete delete-test" data-id="<?php echo $test['test_id']; ?>">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
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

    <!-- Edit Test Modal -->
    <div class="modal fade" id="editTestModal" tabindex="-1" aria-labelledby="editTestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editTestModalLabel"><i class="bi bi-pencil-square me-2"></i>Edit Test</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editTestForm" method="POST" action="update_test.php">
                    <input type="hidden" id="edit_test_id" name="test_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_test_name" class="form-label">Test Name</label>
                            <input type="text" class="form-control" id="edit_test_name" name="test_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function(){
        // Handle edit button click
        $('.btn-edit').click(function(){
            const testId = $(this).data('id');
            const testName = $(this).data('name');
            
            $('#edit_test_id').val(testId);
            $('#edit_test_name').val(testName);
        });
        
        // Handle delete button click
        $('.delete-test').click(function(){
            const testId = $(this).data('id');
            if(confirm('Are you sure you want to delete this test? All associated questions and results will be permanently deleted.')){
                // AJAX call to delete test
                $.post('delete_test.php', {test_id: testId}, function(response){
                    if(response.success){
                        location.reload();
                    } else {
                        alert('Error: ' + response.error);
                    }
                }).fail(function(){
                    alert('An error occurred while deleting the test.');
                });
            }
        });
    });
    </script>
</body>
</html>
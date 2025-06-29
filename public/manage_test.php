<?php
include '../config/db_config.php';

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Check if test_id is provided
if(!isset($_GET['test_id'])){
    header("location: dashboard.php");
    exit;
}

$test_id = intval($_GET['test_id']);

// Verify the test belongs to the current user
$sql = "SELECT * FROM tests WHERE test_id = ? AND created_by = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "ii", $test_id, $_SESSION["id"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $test = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if(!$test){
        header("location: dashboard.php");
        exit;
    }
}

// Fetch questions for this test
$questions = [];
$sql = "SELECT * FROM questions WHERE test_id = ? ORDER BY question_id";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $test_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($result)){
        $questions[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Handle AJAX requests
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'){
    header('Content-Type: application/json');
    
    // Add new question
    if(isset($_POST['action']) && $_POST['action'] === 'add_question'){
        $question_text = trim($_POST['question_text']);
        $option1 = trim($_POST['option1']);
        $option2 = trim($_POST['option2']);
        $option3 = trim($_POST['option3']);
        $option4 = trim($_POST['option4']);
        $correct_option = intval($_POST['correct_option']);
        
        if(!empty($question_text) && !empty($option1) && !empty($option2) && $correct_option >= 1 && $correct_option <= 4){
            $sql = "INSERT INTO questions (test_id, question_text, option1, option2, option3, option4, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "isssssi", $test_id, $question_text, $option1, $option2, $option3, $option4, $correct_option);
                if(mysqli_stmt_execute($stmt)){
                    $question_id = mysqli_insert_id($conn);
                    echo json_encode([
                        'success' => true,
                        'question' => [
                            'question_id' => $question_id,
                            'question_text' => htmlspecialchars($question_text),
                            'option1' => htmlspecialchars($option1),
                            'option2' => htmlspecialchars($option2),
                            'option3' => htmlspecialchars($option3),
                            'option4' => htmlspecialchars($option4),
                            'correct_option' => $correct_option
                        ]
                    ]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Database error']);
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid data']);
        }
        exit;
    }
    
    // Update question
    if(isset($_POST['action']) && $_POST['action'] === 'update_question'){
        $question_id = intval($_POST['question_id']);
        $question_text = trim($_POST['question_text']);
        $option1 = trim($_POST['option1']);
        $option2 = trim($_POST['option2']);
        $option3 = trim($_POST['option3']);
        $option4 = trim($_POST['option4']);
        $correct_option = intval($_POST['correct_option']);
        
        if(!empty($question_text) && !empty($option1) && !empty($option2) && $correct_option >= 1 && $correct_option <= 4){
            $sql = "UPDATE questions SET question_text = ?, option1 = ?, option2 = ?, option3 = ?, option4 = ?, correct_option = ? WHERE question_id = ? AND test_id = ?";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "sssssiii", $question_text, $option1, $option2, $option3, $option4, $correct_option, $question_id, $test_id);
                if(mysqli_stmt_execute($stmt)){
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Database error']);
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid data']);
        }
        exit;
    }
    
    // Delete question
    if(isset($_POST['action']) && $_POST['action'] === 'delete_question'){
        $question_id = intval($_POST['question_id']);
        
        $sql = "DELETE FROM questions WHERE question_id = ? AND test_id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "ii", $question_id, $test_id);
            if(mysqli_stmt_execute($stmt)){
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error']);
            }
            mysqli_stmt_close($stmt);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Test - Brilliant IAS Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- <link rel="stylesheet" href="../assets/style.css"> -->
    <link rel="stylesheet" href="../assets/dashboard.css">
        <link rel="icon" href="../assets/logo.jpg" type="image/x-icon">


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

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><?php echo htmlspecialchars($test['test_name']); ?></h2>
                <p class="text-muted mb-0">
                    Created: <?php echo date("M d, Y h:i A", strtotime($test['created_at'])); ?> | 
                    Last Updated: <?php echo date("M d, Y h:i A", strtotime($test['updated_at'])); ?>
                </p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left me-1"></i> Back to Tests
            </a>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Add New Question</h5>
            </div>
            <div class="card-body">
                <form id="addQuestionForm">
                    <div class="mb-3">
                        <label for="question_text" class="form-label">Question Text</label>
                        <textarea class="form-control" id="question_text" name="question_text" rows="3" required></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="option1" class="form-label">Option 1</label>
                            <div class="input-group">
                                <div class="input-group-text">
                                    <input class="form-check-input mt-0" type="radio" name="correct_option" value="1" required>
                                </div>
                                <input type="text" class="form-control" id="option1" name="option1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="option2" class="form-label">Option 2</label>
                            <div class="input-group">
                                <div class="input-group-text">
                                    <input class="form-check-input mt-0" type="radio" name="correct_option" value="2">
                                </div>
                                <input type="text" class="form-control" id="option2" name="option2" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="option3" class="form-label">Option 3</label>
                            <div class="input-group">
                                <div class="input-group-text">
                                    <input class="form-check-input mt-0" type="radio" name="correct_option" value="3">
                                </div>
                                <input type="text" class="form-control" id="option3" name="option3" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="option4" class="form-label">Option 4</label>
                            <div class="input-group">
                                <div class="input-group-text">
                                    <input class="form-check-input mt-0" type="radio" name="correct_option" value="4">
                                </div>
                                <input type="text" class="form-control" id="option4" name="option4" required>
                            </div>
                        </div>
                    </div>
                    <div class="d-grid mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Add Question
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Test Questions</h5>
                <span class="badge bg-light text-primary"><?php echo count($questions); ?> Questions</span>
            </div>
            <div class="card-body">
                <?php if(empty($questions)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-question-circle display-4 text-muted"></i>
                        <h5 class="mt-3">No Questions Added Yet</h5>
                        <p class="text-muted">Add your first question using the form above</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="45%">Question</th>
                                    <th width="40%">Options</th>
                                    <th width="10%">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="questionsTableBody">
                                <?php foreach($questions as $index => $question): ?>
                                <tr id="questionRow_<?php echo $question['question_id']; ?>">
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <div class="question-text" data-id="<?php echo $question['question_id']; ?>">
                                            <?php echo htmlspecialchars($question['question_text']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="options-container" data-id="<?php echo $question['question_id']; ?>">
                                            <ol class="list-unstyled">
                                                <li class="<?php echo $question['correct_option'] == 1 ? 'text-success fw-bold' : ''; ?>">
                                                    <?php echo htmlspecialchars($question['option1']); ?>
                                                </li>
                                                <li class="<?php echo $question['correct_option'] == 2 ? 'text-success fw-bold' : ''; ?>">
                                                    <?php echo htmlspecialchars($question['option2']); ?>
                                                </li>
                                                <li class="<?php echo $question['correct_option'] == 3 ? 'text-success fw-bold' : ''; ?>">
                                                    <?php echo htmlspecialchars($question['option3']); ?>
                                                </li>
                                                <li class="<?php echo $question['correct_option'] == 4 ? 'text-success fw-bold' : ''; ?>">
                                                    <?php echo htmlspecialchars($question['option4']); ?>
                                                </li>
                                            </ol>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-outline-primary edit-question" data-id="<?php echo $question['question_id']; ?>">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger delete-question" data-id="<?php echo $question['question_id']; ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Question Modal -->
    <div class="modal fade" id="editQuestionModal" tabindex="-1" aria-labelledby="editQuestionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editQuestionModalLabel">Edit Question</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editQuestionForm">
                    <input type="hidden" id="edit_question_id" name="question_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_question_text" class="form-label">Question Text</label>
                            <textarea class="form-control" id="edit_question_text" name="question_text" rows="3" required></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="edit_option1" class="form-label">Option 1</label>
                                <div class="input-group">
                                    <div class="input-group-text">
                                        <input class="form-check-input mt-0" type="radio" name="correct_option" value="1" required>
                                    </div>
                                    <input type="text" class="form-control" id="edit_option1" name="option1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_option2" class="form-label">Option 2</label>
                                <div class="input-group">
                                    <div class="input-group-text">
                                        <input class="form-check-input mt-0" type="radio" name="correct_option" value="2">
                                    </div>
                                    <input type="text" class="form-control" id="edit_option2" name="option2" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_option3" class="form-label">Option 3</label>
                                <div class="input-group">
                                    <div class="input-group-text">
                                        <input class="form-check-input mt-0" type="radio" name="correct_option" value="3">
                                    </div>
                                    <input type="text" class="form-control" id="edit_option3" name="option3" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_option4" class="form-label">Option 4</label>
                                <div class="input-group">
                                    <div class="input-group-text">
                                        <input class="form-check-input mt-0" type="radio" name="correct_option" value="4">
                                    </div>
                                    <input type="text" class="form-control" id="edit_option4" name="option4" required>
                                </div>
                            </div>
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
        // Add new question
        $('#addQuestionForm').on('submit', function(e){
            e.preventDefault();
            
            $.ajax({
                url: 'manage_test.php?test_id=<?php echo $test_id; ?>',
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'add_question',
                    question_text: $('#question_text').val(),
                    option1: $('#option1').val(),
                    option2: $('#option2').val(),
                    option3: $('#option3').val(),
                    option4: $('#option4').val(),
                    correct_option: $('input[name="correct_option"]:checked').val()
                },
                success: function(response){
                    if(response.success){
                        // Add new row to table
                        const question = response.question;
                        const rowCount = $('#questionsTableBody tr').length + 1;
                        
                        let optionsHtml = '';
                        for(let i = 1; i <= 4; i++){
                            const isCorrect = question.correct_option == i;
                            optionsHtml += `<li class="${isCorrect ? 'text-success fw-bold' : ''}">${question['option' + i]}</li>`;
                        }
                        
                        const newRow = `
                            <tr id="questionRow_${question.question_id}">
                                <td>${rowCount}</td>
                                <td>
                                    <div class="question-text" data-id="${question.question_id}">
                                        ${question.question_text}
                                    </div>
                                </td>
                                <td>
                                    <div class="options-container" data-id="${question.question_id}">
                                        <ol class="list-unstyled">
                                            ${optionsHtml}
                                        </ol>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary edit-question" data-id="${question.question_id}">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-question" data-id="${question.question_id}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                        
                        if(rowCount === 1){
                            $('#questionsTableBody').html(newRow);
                        } else {
                            $('#questionsTableBody').append(newRow);
                        }
                        
                        // Reset form
                        $('#addQuestionForm')[0].reset();
                        
                        // Update question count
                        $('.badge').text(rowCount + ' Questions');
                    } else {
                        alert('Error: ' + response.error);
                    }
                },
                error: function(){
                    alert('An error occurred while adding the question.');
                }
            });
        });
        
        // Edit question - show modal
        $(document).on('click', '.edit-question', function(){
            const questionId = $(this).data('id');
            const row = $('#questionRow_' + questionId);
            
            // Get current values
            const questionText = row.find('.question-text').text().trim();
            const options = [];
            row.find('.options-container li').each(function(){
                options.push($(this).text().trim());
            });
            
            // Find correct option
            let correctOption = 1;
            row.find('.options-container li').each(function(index){
                if($(this).hasClass('text-success')){
                    correctOption = index + 1;
                    return false;
                }
            });
            
            // Set values in modal
            $('#edit_question_id').val(questionId);
            $('#edit_question_text').val(questionText);
            $('#edit_option1').val(options[0]);
            $('#edit_option2').val(options[1]);
            $('#edit_option3').val(options[2]);
            $('#edit_option4').val(options[3]);
            $(`#editQuestionForm input[name="correct_option"][value="${correctOption}"]`).prop('checked', true);
            
            // Show modal
            $('#editQuestionModal').modal('show');
        });
        
        // Edit question - submit
        $('#editQuestionForm').on('submit', function(e){
            e.preventDefault();
            
            const questionId = $('#edit_question_id').val();
            
            $.ajax({
                url: 'manage_test.php?test_id=<?php echo $test_id; ?>',
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'update_question',
                    question_id: questionId,
                    question_text: $('#edit_question_text').val(),
                    option1: $('#edit_option1').val(),
                    option2: $('#edit_option2').val(),
                    option3: $('#edit_option3').val(),
                    option4: $('#edit_option4').val(),
                    correct_option: $('#editQuestionForm input[name="correct_option"]:checked').val()
                },
                success: function(response){
                    if(response.success){
                        // Update row in table
                        const row = $('#questionRow_' + questionId);
                        
                        // Update question text
                        row.find('.question-text').text($('#edit_question_text').val());
                        
                        // Update options
                        const options = [
                            $('#edit_option1').val(),
                            $('#edit_option2').val(),
                            $('#edit_option3').val(),
                            $('#edit_option4').val()
                        ];
                        const correctOption = parseInt($('#editQuestionForm input[name="correct_option"]:checked').val());
                        
                        let optionsHtml = '';
                        for(let i = 0; i < 4; i++){
                            const isCorrect = correctOption === (i + 1);
                            optionsHtml += `<li class="${isCorrect ? 'text-success fw-bold' : ''}">${options[i]}</li>`;
                        }
                        
                        row.find('.options-container ol').html(optionsHtml);
                        
                        // Close modal
                        $('#editQuestionModal').modal('hide');
                    } else {
                        alert('Error: ' + response.error);
                    }
                },
                error: function(){
                    alert('An error occurred while updating the question.');
                }
            });
        });
        
        // Delete question
        $(document).on('click', '.delete-question', function(){
            if(confirm('Are you sure you want to delete this question?')){
                const questionId = $(this).data('id');
                
                $.ajax({
                    url: 'manage_test.php?test_id=<?php echo $test_id; ?>',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'delete_question',
                        question_id: questionId
                    },
                    success: function(response){
                        if(response.success){
                            // Remove row from table
                            $('#questionRow_' + questionId).remove();
                            
                            // Update question numbers
                            $('#questionsTableBody tr').each(function(index){
                                $(this).find('td:first').text(index + 1);
                            });
                            
                            // Update question count
                            const newCount = $('#questionsTableBody tr').length;
                            $('.badge').text(newCount + ' Questions');
                            
                            if(newCount === 0){
                                $('#questionsTableBody').html(`
                                    <div class="text-center py-4">
                                        <i class="bi bi-question-circle display-4 text-muted"></i>
                                        <h5 class="mt-3">No Questions Added Yet</h5>
                                        <p class="text-muted">Add your first question using the form above</p>
                                    </div>
                                `);
                            }
                        } else {
                            alert('Error: ' + response.error);
                        }
                    },
                    error: function(){
                        alert('An error occurred while deleting the question.');
                    }
                });
            }
        });
    });
    </script>
</body>
</html>
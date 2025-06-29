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

// Get test details
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

// Get questions for this test
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Test - Brilliant IAS Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .navbar{
            background-color: #0A3161;
        }
        .navbar-brand {
            font-weight: 600;
        }
        .navbar-brand img {
            height: 40px;
            margin-right: 10px;
        }
        .test-container {
            max-width: 100%;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
        }
        .question-card {
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        .question-text {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .option-btn {
            text-align: left;
            padding: 12px 15px;
            margin-bottom: 10px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            background-color: white;
            transition: all 0.2s;
        }
        .option-btn:hover {
            background-color: #f8f9fa;
        }
        .option-btn.selected {
            border-color: #0d6efd;
            background-color: #e7f1ff;
        }
        .option-btn.correct {
            border-color: #198754;
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .option-btn.incorrect {
            border-color: #dc3545;
            background-color: #f8d7da;
            color: #842029;
        }
        .option-label {
            font-weight: 600;
            margin-right: 8px;
        }
        .progress-container {
            margin-bottom: 20px;
        }
        .progress-text {
            font-weight: 500;
            margin-bottom: 5px;
        }
        .answer-status {
            margin-top: 10px;
            font-weight: 500;
        }
        .correct-status {
            color: #198754;
        }
        .incorrect-status {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark  mb-4 shadow-sm">
    <div class="container d-flex justify-content-center">
        <a class="navbar-brand d-flex align-items-center mx-auto" href="dashboard.php" style="gap: 0.5rem;">
            <img src="../assets/logo.jpg" alt="Brilliant IAS Academy Logo" style="height: 40px;">
            <span class="text-white">Brilliant IAS Academy</span>
        </a>
    </div>
</nav>

    
    <div class="container py-4">
        <div class="test-container">
            <h2 class="text-center mb-4"><?php echo htmlspecialchars($test['test_name']); ?></h2>
            
            <div class="progress-container">
                <div class="progress-text">Question 1 of <?php echo count($questions); ?></div>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                </div>
            </div>
            
            <?php foreach($questions as $index => $question): ?>
            <div class="question-card" data-question-id="<?php echo $question['question_id']; ?>" data-correct-option="<?php echo $question['correct_option']; ?>" <?php echo $index > 0 ? 'style="display:none;"' : ''; ?>>
                <div class="question-text">
                    <strong>Q<?php echo $index + 1; ?>.</strong> <?php echo htmlspecialchars($question['question_text']); ?>
                </div>
                
                <div class="row options">
                    <div class="col-md-6">
                        <button class="option-btn w-100" data-option="1">
                            <span class="option-label">A</span><?php echo htmlspecialchars($question['option1']); ?>
                        </button>
                    </div>
                    
                    <div class="col-md-6">
                        <button class="option-btn w-100" data-option="2">
                            <span class="option-label">B</span><?php echo htmlspecialchars($question['option2']); ?>
                        </button>
                    </div>
                    
                    <div class="col-md-6">
                        <button class="option-btn w-100" data-option="3">
                            <span class="option-label">C</span><?php echo htmlspecialchars($question['option3']); ?>
                        </button>
                    </div>
                    
                    <div class="col-md-6">
                        <button class="option-btn w-100" data-option="4">
                            <span class="option-label">D</span><?php echo htmlspecialchars($question['option4']); ?>
                        </button>
                    </div>
                </div>
                
                <div class="answer-status" style="display: none;"></div>
            </div>
            <?php endforeach; ?>
            
            <div class="d-flex justify-content-between mt-4">
                <button class="btn btn-outline-primary prev-btn" >
                    <i class="bi bi-chevron-left"></i> Previous
                </button>
                <button class="btn btn-primary next-btn" >
                    Next <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
  $(document).ready(function() {
    const totalQuestions = <?php echo count($questions); ?>;
    let currentQuestion = 1;

    function updateProgress() {
        const progress = (currentQuestion / totalQuestions) * 100;
        $('.progress-bar').css('width', progress + '%');
        $('.progress-text').text(`Question ${currentQuestion} of ${totalQuestions}`);
    }

    $('.option-btn').click(function() {
        const questionCard = $(this).closest('.question-card');
        const selectedOption = $(this).data('option');
        const correctOption = questionCard.data('correct-option');
        const isCorrect = (selectedOption == correctOption);

        questionCard.find('.option-btn').removeClass('selected correct incorrect');
        $(this).addClass('selected');
        questionCard.find(`.option-btn[data-option="${correctOption}"]`).addClass('correct');

        if(!isCorrect) {
            $(this).addClass('incorrect');
        }

        const statusDiv = questionCard.find('.answer-status');
        statusDiv.show();
        if(isCorrect) {
            statusDiv.html('<span class="correct-status"><i class="bi bi-check-circle-fill"></i> Correct Answer</span>');
        } else {
            statusDiv.html('<span class="incorrect-status"><i class="bi bi-x-circle-fill"></i> Incorrect</span>');
        }
    });

    $('.next-btn').click(function() {
        if(currentQuestion < totalQuestions) {
            $(`.question-card:nth-child(${currentQuestion + 2})`).hide();
            currentQuestion++;
            $(`.question-card:nth-child(${currentQuestion + 2})`).show();

            $('.prev-btn').prop('disabled', false);
            if(currentQuestion === totalQuestions) {
                $(this).text('Finish');
            }

            updateProgress();
        } else {
            alert('Test completed!');
            window.location.href = 'dashboard.php';
        }
    });

    $('.prev-btn').click(function() {
        if(currentQuestion > 1) {
            $(`.question-card:nth-child(${currentQuestion + 2})`).hide();
            currentQuestion--;
            $(`.question-card:nth-child(${currentQuestion + 2})`).show();

            $('.next-btn').text('Next');
            if(currentQuestion === 1) {
                $(this).prop('disabled', true);
            }

            updateProgress();
        }
    });

    updateProgress();
});

    </script>
</body>
</html>
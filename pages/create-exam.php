<?php
session_start();
require_once '../config/db_connect.php';

// Check if admin is logged in
// if (!isset($_SESSION['admin_id'])) {
//     header("Location: ../admin-login.php");
//     exit;
// }

$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];

$success_message = "";
$error_message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if exam data is submitted
    if (isset($_POST['exam_title'])) {
        $exam_title = trim($_POST['exam_title']);
        $exam_description = trim($_POST['exam_description']);
        $duration = intval($_POST['duration']);
        $passing_score = intval($_POST['passing_score']);
        
        // Validate input
        if (empty($exam_title) || empty($duration) || empty($passing_score)) {
            $error_message = "Please fill in all required fields.";
        } else {
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Insert exam

                // Modify the exam insertion query
                //$stmt = $conn->prepare("INSERT INTO exams (title, description, duration_minutes, passing_score, created_by, exam_date, exam_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
                //$stmt->bind_param("ssiisss", $exam_title, $exam_description, $duration, $passing_score, $admin_id, $exam_date, $exam_time);

                $stmt = $conn->prepare("INSERT INTO exams (title, description, duration_minutes, passing_score, created_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiii", $exam_title, $exam_description, $duration, $passing_score, $admin_id);
                $stmt->execute();
                $exam_id = $conn->insert_id;
                $stmt->close();
                
                // Process questions
                $question_count = count($_POST['question_text']);
                
                for ($i = 0; $i < $question_count; $i++) {
                    $question_text = trim($_POST['question_text'][$i]);
                    $question_type = $_POST['question_type'][$i];
                    $points = intval($_POST['points'][$i]);
                    
                    if (!empty($question_text)) {
                        // Insert question
                        $stmt = $conn->prepare("INSERT INTO questions (exam_id, question_text, question_type, points, order_num) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("issii", $exam_id, $question_text, $question_type, $points, $i);
                        $stmt->execute();
                        $question_id = $conn->insert_id;
                        $stmt->close();
                        
                        // Process options for multiple choice questions
                        if ($question_type === 'multiple_choice' && isset($_POST['option_text'][$i])) {
                            $options = $_POST['option_text'][$i];
                            $correct_option = isset($_POST['correct_option'][$i]) ? $_POST['correct_option'][$i] : -1;
                            
                            for ($j = 0; $j < count($options); $j++) {
                                $option_text = trim($options[$j]);
                                $is_correct = ($j == $correct_option) ? 1 : 0;
                                
                                if (!empty($option_text)) {
                                    $stmt = $conn->prepare("INSERT INTO options (question_id, option_text, is_correct, order_num) VALUES (?, ?, ?, ?)");
                                    $stmt->bind_param("isii", $question_id, $option_text, $is_correct, $j);
                                    $stmt->execute();
                                    $stmt->close();
                                }
                            }
                        }
                        
                        // Process true/false options
                        if ($question_type === 'true_false') {
                            $correct_answer = isset($_POST['true_false'][$i]) ? $_POST['true_false'][$i] : 'true';
                            
                            // Add "True" option
                            $stmt = $conn->prepare("INSERT INTO options (question_id, option_text, is_correct, order_num) VALUES (?, ?, ?, ?)");
                            $is_correct_true = ($correct_answer === 'true') ? 1 : 0;
                            $option_text = "True";
                            $order_num = 0;
                            $stmt->bind_param("isii", $question_id, $option_text, $is_correct_true, $order_num);
                            $stmt->execute();
                            $stmt->close();
                            
                            // Add "False" option
                            $stmt = $conn->prepare("INSERT INTO options (question_id, option_text, is_correct, order_num) VALUES (?, ?, ?, ?)");
                            $is_correct_false = ($correct_answer === 'false') ? 1 : 0;
                            $option_text = "False";
                            $order_num = 1;
                            $stmt->bind_param("isii", $question_id, $option_text, $is_correct_false, $order_num);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
                
                // Commit transaction
                $conn->commit();
                $success_message = "Exam created successfully!";
                
                // Redirect to view the created exam
                header("Location: view-exam.php?id=" . $exam_id . "&created=1");
                exit;
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $error_message = "Error creating exam: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Exam - RUPP Examination System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            transition: all 0.3s ease;
        }
        
        .sidebar-link {
            transition: all 0.2s ease;
        }
        
        .sidebar-link:hover {
            background-color: rgba(139, 92, 246, 0.1);
        }
        
        .glass {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <div class="sidebar w-64 bg-white shadow-md h-screen fixed">
            <div class="flex items-center justify-center h-24 border-b">
                <div class="text-center">
                    <h1 class="text-2xl font-bold text-purple-900">Admin Panel</h1>
                    <p class="text-sm text-purple-600">Exam Management System</p>
                </div>
            </div>
            <nav class="py-4">
                <ul>
                    <li>
                        <a href="dashboard.php" class="sidebar-link flex items-center px-6 py-3 text-gray-700 hover:text-purple-900">
                            <i class="fas fa-tachometer-alt mr-3"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="exams.php" class="sidebar-link flex items-center px-6 py-3 text-gray-700 hover:text-purple-900">
                            <i class="fas fa-file-alt mr-3"></i>
                            <span>Exams</span>
                        </a>
                    </li>
                    <li>
                        <a href="create-exam.php" class="sidebar-link flex items-center px-6 py-3 text-purple-900 bg-purple-100 border-r-4 border-purple-600">
                            <i class="fas fa-plus-circle mr-3"></i>
                            <span>Create Exam</span>
                        </a>
                    </li>
                    <li>
                        <a href="students.php" class="sidebar-link flex items-center px-6 py-3 text-gray-700 hover:text-purple-900">
                            <i class="fas fa-user-graduate mr-3"></i>
                            <span>Students</span>
                        </a>
                    </li>
                    <li>
                        <a href="results.php" class="sidebar-link flex items-center px-6 py-3 text-gray-700 hover:text-purple-900">
                            <i class="fas fa-chart-bar mr-3"></i>
                            <span>Results</span>
                        </a>
                    </li>
                    <li>
                        <a href="schedule.php" class="sidebar-link flex items-center px-6 py-3 text-gray-700 hover:text-purple-900">
                            <i class="fas fa-calendar-alt mr-3"></i>
                            <span>Schedule</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings.php" class="sidebar-link flex items-center px-6 py-3 text-gray-700 hover:text-purple-900">
                            <i class="fas fa-cog mr-3"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="absolute bottom-0 w-full border-t p-4">
                <a href="../logout.php" class="flex items-center text-red-500 hover:text-red-700">
                    <i class="fas fa-sign-out-alt mr-3"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="ml-64 flex-grow p-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Create New Exam</h1>
                    <p class="text-gray-600">Design your exam with questions and options</p>
                </div>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?php echo $error_message; ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                    <p><?php echo $success_message; ?></p>
                </div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="exam-form">
                <!-- Exam Details Card -->
                <div class="glass rounded-xl shadow-md p-6 mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">Exam Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="exam_title" class="block text-gray-700 font-medium mb-2">Exam Title *</label>
                            <input 
                                type="text" 
                                id="exam_title" 
                                name="exam_title" 
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                placeholder="Enter exam title"
                                required
                            >
                        </div>
                        
                        <div>
                            <label for="duration" class="block text-gray-700 font-medium mb-2">Duration (minutes) *</label>
                            <input 
                                type="number" 
                                id="duration" 
                                name="duration" 
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                placeholder="e.g. 60"
                                min="1"
                                required
                            >
                        </div>
                    </div>
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <div>
                            <label for="exam_date" class="block text-gray-700 font-medium mb-2">Exam Date *</label>
                            <input 
                                type="date" 
                                id="exam_date" 
                                name="exam_date" 
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                required
                            >
                        </div>
                        
                        <div>
                            <label for="exam_time" class="block text-gray-700 font-medium mb-2">Exam Time *</label>
                            <input 
                                type="time" 
                                id="exam_time" 
                                name="exam_time" 
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                required
                            >
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <label for="exam_description" class="block text-gray-700 font-medium mb-2">Exam Description</label>
                        <textarea 
                            id="exam_description" 
                            name="exam_description" 
                            rows="4" 
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            placeholder="Enter exam description (optional)"
                        ></textarea>
                    </div>
                    
                    <div class="mt-6">
                        <label for="passing_score" class="block text-gray-700 font-medium mb-2">Passing Score (%) *</label>
                        <input 
                            type="number" 
                            id="passing_score" 
                            name="passing_score" 
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            placeholder="e.g. 60"
                            min="1"
                            max="100"
                            required
                        >
                    </div>
                </div>
                
                <!-- Questions Section -->
                <div class="glass rounded-xl shadow-md p-6 mb-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Questions</h2>
                        <button 
                            type="button" 
                            id="add-question-btn"
                            class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
                        >
                            <i class="fas fa-plus mr-2"></i> Add Question
                        </button>
                    </div>
                    
                    <div id="questions-container">
                        <!-- Question template will be added here via JavaScript -->
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="flex justify-end mb-8">
                    <button 
                        type="submit" 
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition-colors"
                    >
                        <i class="fas fa-save mr-2"></i> Create Exam
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Question Template (hidden) -->
    <template id="question-template">
        <div class="question-item bg-white p-6 rounded-lg shadow mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">Question <span class="question-number">1</span></h3>
                <button type="button" class="remove-question text-red-500 hover:text-red-700" title="Remove Question">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">Question Text *</label>
                <textarea 
                    name="question_text[]" 
                    rows="3" 
                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                    placeholder="Enter your question"
                    required
                ></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Question Type *</label>
                    <select 
                        name="question_type[]" 
                        class="question-type w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                        required
                    >
                        <option value="multiple_choice">Multiple Choice</option>
                        <option value="true_false">True/False</option>
                        <option value="short_answer">Short Answer</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Points *</label>
                    <input 
                        type="number" 
                        name="points[]" 
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                        placeholder="e.g. 5"
                        value="1"
                        min="1"
                        required
                    >
                </div>
            </div>
            
            <!-- Multiple Choice Options -->
            <div class="options-container multiple-choice-options">
                <div class="flex justify-between items-center mb-3">
                    <label class="block text-gray-700 font-medium">Answer Options *</label>
                    <button 
                        type="button" 
                        class="add-option text-purple-600 hover:text-purple-800 text-sm font-medium"
                    >
                        <i class="fas fa-plus mr-1"></i> Add Option
                    </button>
                </div>
                
                <div class="options-list">
                    <!-- Option 1 -->
                    <div class="option-item flex items-center mb-3">
                        <input 
                            type="radio" 
                            name="correct_option[0]" 
                            value="0" 
                            class="correct-option mr-3"
                            checked
                        >
                        <input 
                            type="text" 
                            name="option_text[0][]" 
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            placeholder="Option 1"
                            required
                        >
                        <button type="button" class="remove-option ml-3 text-red-500 hover:text-red-700" title="Remove Option">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <!-- Option 2 -->
                    <div class="option-item flex items-center mb-3">
                        <input 
                            type="radio" 
                            name="correct_option[0]" 
                            value="1" 
                            class="correct-option mr-3"
                        >
                        <input 
                            type="text" 
                            name="option_text[0][]" 
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            placeholder="Option 2"
                            required
                        >
                        <button type="button" class="remove-option ml-3 text-red-500 hover:text-red-700" title="Remove Option">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- True/False Options -->
            <div class="options-container true-false-options hidden">
                <label class="block text-gray-700 font-medium mb-3">Correct Answer *</label>
                <div class="flex space-x-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="true_false[0]" value="true" class="mr-2" checked>
                        <span>True</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="true_false[0]" value="false" class="mr-2">
                        <span>False</span>
                    </label>
                </div>
            </div>
            
            <!-- Short Answer doesn't need options -->
            <div class="options-container short-answer-options hidden">
                <p class="text-gray-600 italic">Student will provide a text answer that will be manually graded.</p>
            </div>
        </div>
    </template>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize with one question
            addQuestion();
            
            // Add question button click event
            document.getElementById('add-question-btn').addEventListener('click', function() {
                addQuestion();
            });
            
            // Event delegation for dynamically added elements
            document.getElementById('questions-container').addEventListener('click', function(e) {
                // Remove question button
                if (e.target.closest('.remove-question')) {
                    const questionItem = e.target.closest('.question-item');
                    if (document.querySelectorAll('.question-item').length > 1) {
                        questionItem.remove();
                        updateQuestionNumbers();
                    } else {
                        alert('You must have at least one question.');
                    }
                }
                
                // Add option button
                if (e.target.closest('.add-option')) {
                    const questionItem = e.target.closest('.question-item');
                    const optionsList = questionItem.querySelector('.options-list');
                    const questionIndex = Array.from(document.querySelectorAll('.question-item')).indexOf(questionItem);
                    const optionsCount = optionsList.querySelectorAll('.option-item').length;
                    
                    const optionItem = document.createElement('div');
                    optionItem.className = 'option-item flex items-center mb-3';
                    optionItem.innerHTML = `
                        <input 
                            type="radio" 
                            name="correct_option[${questionIndex}]" 
                            value="${optionsCount}" 
                            class="correct-option mr-3"
                        >
                        <input 
                            type="text" 
                            name="option_text[${questionIndex}][]" 
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            placeholder="Option ${optionsCount + 1}"
                            required
                        >
                        <button type="button" class="remove-option ml-3 text-red-500 hover:text-red-700" title="Remove Option">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    
                    optionsList.appendChild(optionItem);
                }
                
                // Remove option button
                if (e.target.closest('.remove-option')) {
                    const optionItem = e.target.closest('.option-item');
                    const optionsList = optionItem.closest('.options-list');
                    
                    if (optionsList.querySelectorAll('.option-item').length > 2) {
                        optionItem.remove();
                        
                        // Update radio button values
                        const questionItem = optionItem.closest('.question-item');
                        const questionIndex = Array.from(document.querySelectorAll('.question-item')).indexOf(questionItem);
                        const options = optionsList.querySelectorAll('.option-item');
                        
                        options.forEach((option, index) => {
                            option.querySelector('.correct-option').value = index;
                        });
                    } else {
                        alert('You must have at least two options.');
                    }
                }
            });
            
            // Event delegation for question type change
            document.getElementById('questions-container').addEventListener('change', function(e) {
                if (e.target.classList.contains('question-type')) {
                    const questionItem = e.target.closest('.question-item');
                    const questionType = e.target.value;
                    const questionIndex = Array.from(document.querySelectorAll('.question-item')).indexOf(questionItem);
                    
                    // Hide all option containers
                    questionItem.querySelectorAll('.options-container').forEach(container => {
                        container.classList.add('hidden');
                    });
                    
                    // Show the relevant option container
                    questionItem.querySelector(`.${questionType}-options`).classList.remove('hidden');
                    
                    // Update true/false radio buttons name
                    if (questionType === 'true_false') {
                        const radioButtons = questionItem.querySelectorAll('.true-false-options input[type="radio"]');
                        radioButtons.forEach(radio => {
                            radio.name = `true_false[${questionIndex}]`;
                        });
                    }
                    
                    // Update multiple choice radio buttons name
                    if (questionType === 'multiple_choice') {
                        const radioButtons = questionItem.querySelectorAll('.multiple-choice-options .correct-option');
                        radioButtons.forEach((radio, index) => {
                            radio.name = `correct_option[${questionIndex}]`;
                            radio.value = index;
                        });
                        
                        // Update option text input names
                        const optionInputs = questionItem.querySelectorAll('.multiple-choice-options input[type="text"]');
                        optionInputs.forEach(input => {
                            input.name = `option_text[${questionIndex}][]`;
                        });
                    }
                }
            });
            
            // Function to add a new question
            function addQuestion() {
                const questionsContainer = document.getElementById('questions-container');
                const template = document.getElementById('question-template');
                const questionCount = questionsContainer.querySelectorAll('.question-item').length;
                
                // Clone the template
                const newQuestion = template.content.cloneNode(true);
                
                // Update question number
                newQuestion.querySelector('.question-number').textContent = questionCount + 1;
                
                // Update form element names with the correct index
                const radioButtons = newQuestion.querySelectorAll('.correct-option');
                radioButtons.forEach((radio, index) => {
                    radio.name = `correct_option[${questionCount}]`;
                    radio.value = index;
                });
                
                const truefalseRadios = newQuestion.querySelectorAll('.true-false-options input[type="radio"]');
                truefalseRadios.forEach(radio => {
                    radio.name = `true_false[${questionCount}]`;
                });
                
                const optionInputs = newQuestion.querySelectorAll('.multiple-choice-options input[type="text"]');
                optionInputs.forEach(input => {
                    input.name = `option_text[${questionCount}][]`;
                });
                
                // Add the new question to the container
                questionsContainer.appendChild(newQuestion);
                
                // Show first question type options by default
                const lastQuestion = questionsContainer.querySelector('.question-item:last-child');
                lastQuestion.querySelector('.multiple-choice-options').classList.remove('hidden');
            }
            
            // Function to update question numbers
            function updateQuestionNumbers() {
                const questions = document.querySelectorAll('.question-item');
                questions.forEach((question, index) => {
                    question.querySelector('.question-number').textContent = index + 1;
                    
                    // Update radio button names
                    const mcRadios = question.querySelectorAll('.multiple-choice-options .correct-option');
                    mcRadios.forEach((radio, radioIndex) => {
                        radio.name = `correct_option[${index}]`;
                        radio.value = radioIndex;
                    });
                    
                    // Update true/false radio names
                    const tfRadios = question.querySelectorAll('.true-false-options input[type="radio"]');
                    tfRadios.forEach(radio => {
                        radio.name = `true_false[${index}]`;
                    });
                    
                    // Update option text input names
                    const optionInputs = question.querySelectorAll('.multiple-choice-options input[type="text"]');
                    optionInputs.forEach(input => {
                        input.name = `option_text[${index}][]`;
                    });
                });
            }
            
            // Form submission
            document.getElementById('exam-form').addEventListener('submit', function(e) {
                const questions = document.querySelectorAll('.question-item');
                let valid = true;
                
                questions.forEach((question, index) => {
                    const questionType = question.querySelector('.question-type').value;
                    
                    if (questionType === 'multiple_choice') {
                        const options = question.querySelectorAll('.option-item input[type="text"]');
                        const filledOptions = Array.from(options).filter(option => option.value.trim() !== '');
                        
                        if (filledOptions.length < 2) {
                            alert(`Question ${index + 1} must have at least 2 options.`);
                            valid = false;
                        }
                        
                        // Check if a correct option is selected
                        const hasCorrectOption = Array.from(question.querySelectorAll('.correct-option')).some(radio => radio.checked);
                        if (!hasCorrectOption) {
                            alert(`Please select a correct option for Question ${index + 1}.`);
                            valid = false;
                        }
                    }
                });
                
                if (!valid) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
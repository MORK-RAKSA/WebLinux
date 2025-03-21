<?php
// Start session to maintain state
session_start();

// Check if admin is logged in
// if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_name'])) {
//     // Redirect to admin login page
//     header("Location: admin_login.php");
//     exit();
// }

// Database Connection
$servername = "localhost";
$username = "dbuser";
$password = "1234";
$dbname = "test";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$successMessage = '';
$errorMessage = '';

// Get all students for the dropdown
$students = [];
$studentQuery = "SELECT student_id, first_name, last_name FROM students ORDER BY last_name, first_name";
$studentResult = $conn->query($studentQuery);
if ($studentResult && $studentResult->num_rows > 0) {
    while ($row = $studentResult->fetch_assoc()) {
        $students[] = $row;
    }
}

// Process exam creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_exam'])) {
    try {
        // Generate unique schedule ID
        $scheduleId = 'SCH_' . date('Ymd') . '_' . uniqid();
        
        // Get form data
        $studentId = $_POST['student_id'];
        $examDate = $_POST['exam_date'];
        $examTime = $_POST['exam_time'];
        
        // Validate student exists
        $checkStudent = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
        $checkStudent->bind_param("s", $studentId);
        $checkStudent->execute();
        $result = $checkStudent->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Student ID does not exist!");
        }
        
        // Insert into exam_schedules table
        $stmt = $conn->prepare("INSERT INTO exam_schedules (schedule_id, student_id, exam_date, exam_time, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $scheduleId, $studentId, $examDate, $examTime);
        
        if ($stmt->execute()) {
            // Now handle the questions
            $subjects = ['khmer', 'math', 'english', 'science'];
            
            // Create questions table if it doesn't exist
            $createQuestionsTable = "CREATE TABLE IF NOT EXISTS custom_questions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                schedule_id VARCHAR(20) NOT NULL,
                subject VARCHAR(50) NOT NULL,
                question_text TEXT NOT NULL,
                option1 TEXT NOT NULL,
                option2 TEXT NOT NULL,
                option3 TEXT NOT NULL,
                option4 TEXT NOT NULL,
                correct_option INT NOT NULL,
                FOREIGN KEY (schedule_id) REFERENCES exam_schedules(schedule_id)
            )";
            
            $conn->query($createQuestionsTable);
            
            // For each subject, get questions and save them
            foreach ($subjects as $subject) {
                // Process each question for this subject
                for ($i = 1; $i <= 5; $i++) {
                    $questionKey = $subject . '_question_' . $i;
                    $option1Key = $subject . '_option1_' . $i;
                    $option2Key = $subject . '_option2_' . $i;
                    $option3Key = $subject . '_option3_' . $i;
                    $option4Key = $subject . '_option4_' . $i;
                    $correctKey = $subject . '_correct_' . $i;
                    
                    // Check if question is provided
                    if (!empty($_POST[$questionKey])) {
                        $questionText = $_POST[$questionKey];
                        $option1 = $_POST[$option1Key];
                        $option2 = $_POST[$option2Key];
                        $option3 = $_POST[$option3Key];
                        $option4 = $_POST[$option4Key];
                        $correctOption = $_POST[$correctKey];
                        
                        // Insert question
                        $stmt = $conn->prepare("INSERT INTO custom_questions (schedule_id, subject, question_text, option1, option2, option3, option4, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("sssssssi", $scheduleId, $subject, $questionText, $option1, $option2, $option3, $option4, $correctOption);
                        $stmt->execute();
                    }
                }
            }
            
            $successMessage = "Exam created successfully with Schedule ID: $scheduleId";
        } else {
            throw new Exception("Error creating exam: " . $stmt->error);
        }
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Create Exam</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .dot-pattern {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, rgba(79, 70, 229, 0.4) 1px, transparent 1px);
            background-size: 20px 20px;
            pointer-events: none;
            z-index: 0;
        }
        
        .glass {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Background gradient -->
    <div class="fixed inset-0 bg-gradient-to-br from-purple-100 to-sky-100 z-0"></div>
    
    <!-- Dot pattern -->
    <div class="dot-pattern"></div>
    
    <div class="relative z-10 container mx-auto py-8 px-4">
        <!-- Header -->
        <div class="glass rounded-t-xl p-6 shadow-lg mb-6">
            <h1 class="text-3xl font-bold text-indigo-900 text-center">Admin Dashboard</h1>
            <p class="text-indigo-800 text-center mt-2">Welcome, <?php echo $_SESSION['admin_name']; ?></p>
            
            <div class="flex justify-center mt-4 space-x-4">
                <a href="admin_dashboard.php" class="text-indigo-700 hover:text-indigo-900 font-medium">Dashboard</a>
                <a href="admin_students.php" class="text-indigo-700 hover:text-indigo-900 font-medium">Manage Students</a>
                <a href="admin_exams.php" class="text-indigo-700 hover:text-indigo-900 font-medium">View Exams</a>
                <a href="admin_create_exam.php" class="text-indigo-700 hover:text-indigo-900 font-medium border-b-2 border-indigo-700">Create Exam</a>
                <a href="./login-page.php" class="text-red-600 hover:text-red-800 font-medium">Logout</a>
            </div>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (!empty($successMessage)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-green-700"><?php echo $successMessage; ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-red-700"><?php echo $errorMessage; ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="glass rounded-xl p-6 shadow-lg mb-6">
    <h2 class="text-2xl font-semibold text-indigo-800 mb-6">Create New Exam</h2>
    
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="createExamForm">
        <!-- Basic Exam Details -->
        <div class="bg-white bg-opacity-70 rounded-xl p-5 mb-6 shadow-md">
            <h3 class="text-xl font-semibold text-indigo-800 mb-4">Exam Details</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="student_id" class="block text-indigo-700 font-medium mb-2">Select Student</label>
                    <select 
                        id="student_id" 
                        name="student_id" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        required
                    >
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $student): ?>
                        <option value="<?php echo $student['student_id']; ?>">
                            <?php echo $student['last_name'] . ', ' . $student['first_name'] . ' (' . $student['student_id'] . ')'; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="exam_date" class="block text-indigo-700 font-medium mb-2">Exam Date</label>
                    <input 
                        type="date" 
                        id="exam_date" 
                        name="exam_date" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        required
                    >
                </div>
                
                <div>
                    <label for="exam_time" class="block text-indigo-700 font-medium mb-2">Exam Time</label>
                    <input 
                        type="time" 
                        id="exam_time" 
                        name="exam_time" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        required
                    >
                </div>
            </div>
        </div>
        
        <!-- Subject Management -->
        <div class="bg-white bg-opacity-70 rounded-xl p-5 mb-6 shadow-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-indigo-800">Subjects</h3>
                <button type="button" id="addSubjectBtn" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    Add Subject
                </button>
            </div>
            
            <!-- Subject List -->
            <div id="subjectList" class="mb-4">
                <div class="flex items-center gap-4 mb-2">
                    <input 
                        type="text" 
                        name="subjects[]" 
                        value="Khmer"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="Subject Name"
                        required
                    >
                    <button type="button" class="remove-subject px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500" disabled>
                        Remove
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Questions Tab Navigation -->
        <div class="mb-6 border-b border-gray-200">
            <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="questionTabs">
                <li class="mr-2">
                    <a href="#khmer" 
                       class="inline-block p-4 border-b-2 rounded-t-lg text-indigo-600 border-indigo-600 question-tab-link active"
                       data-target="khmer"
                    >
                        Khmer
                    </a>
                </li>
                <!-- Additional tabs will be added dynamically -->
            </ul>
        </div>
        
        <!-- Question Sections -->
        <div id="questionSections">
            <!-- Khmer Questions -->
            <div id="khmer-section" class="question-section">
                <div class="bg-white bg-opacity-70 rounded-xl p-5 mb-6 shadow-md">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-indigo-800">Khmer Questions</h3>
                        <button type="button" class="add-question px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500" data-subject="khmer">
                            Add Question
                        </button>
                    </div>
                    
                    <div class="questions-container" id="khmer-questions">
                        <!-- Question template will be cloned here -->
                        <?php for ($i = 1; $i <= 1; $i++): ?>
                        <div class="question-item mb-8 pb-6 border-b border-gray-200">
                            <div class="flex justify-between items-center mb-4">
                                <label class="block text-indigo-700 font-medium">
                                    Question <?php echo $i; ?>
                                </label>
                                <button type="button" class="remove-question px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500" disabled>
                                    Remove
                                </button>
                            </div>
                            
                            <div class="mb-4">
                                <input 
                                    type="text" 
                                    name="khmer_question[]" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Enter question"
                                    required
                                >
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-indigo-700 font-medium mb-2">
                                        Option 1
                                    </label>
                                    <input 
                                        type="text" 
                                        name="khmer_option1[]" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        placeholder="Enter option 1"
                                        required
                                    >
                                </div>
                                
                                <div>
                                    <label class="block text-indigo-700 font-medium mb-2">
                                        Option 2
                                    </label>
                                    <input 
                                        type="text" 
                                        name="khmer_option2[]" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        placeholder="Enter option 2"
                                        required
                                    >
                                </div>
                                
                                <div>
                                    <label class="block text-indigo-700 font-medium mb-2">
                                        Option 3
                                    </label>
                                    <input 
                                        type="text" 
                                        name="khmer_option3[]" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        placeholder="Enter option 3"
                                        required
                                    >
                                </div>
                                
                                <div>
                                    <label class="block text-indigo-700 font-medium mb-2">
                                        Option 4
                                    </label>
                                    <input 
                                        type="text" 
                                        name="khmer_option4[]" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        placeholder="Enter option 4"
                                        required
                                    >
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-indigo-700 font-medium mb-2">
                                    Correct Answer
                                </label>
                                <select 
                                    name="khmer_correct[]" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">-- Select Correct Answer --</option>
                                    <option value="0">Option 1</option>
                                    <option value="1">Option 2</option>
                                    <option value="2">Option 3</option>
                                    <option value="3">Option 4</option>
                                </select>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            
            <!-- Template for new subject section (hidden) -->
            <div id="subject-template" class="question-section hidden">
                <div class="bg-white bg-opacity-70 rounded-xl p-5 mb-6 shadow-md">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-indigo-800">Subject Questions</h3>
                        <button type="button" class="add-question px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            Add Question
                        </button>
                    </div>
                    
                    <div class="questions-container">
                        <!-- Questions will be added here -->
                    </div>
                </div>
            </div>
            
            <!-- Template for new question (hidden) -->
            <div id="question-template" class="hidden">
                <div class="question-item mb-8 pb-6 border-b border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <label class="block text-indigo-700 font-medium question-number">
                            Question
                        </label>
                        <button type="button" class="remove-question px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500">
                            Remove
                        </button>
                    </div>
                    
                    <div class="mb-4">
                        <input 
                            type="text" 
                            name="subject_question[]" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            placeholder="Enter question"
                            required
                        >
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-indigo-700 font-medium mb-2">
                                Option 1
                            </label>
                            <input 
                                type="text" 
                                name="subject_option1[]" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Enter option 1"
                                required
                            >
                        </div>
                        
                        <div>
                            <label class="block text-indigo-700 font-medium mb-2">
                                Option 2
                            </label>
                            <input 
                                type="text" 
                                name="subject_option2[]" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Enter option 2"
                                required
                            >
                        </div>
                        
                        <div>
                            <label class="block text-indigo-700 font-medium mb-2">
                                Option 3
                            </label>
                            <input 
                                type="text" 
                                name="subject_option3[]" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Enter option 3"
                                required
                            >
                        </div>
                        
                        <div>
                            <label class="block text-indigo-700 font-medium mb-2">
                                Option 4
                            </label>
                            <input 
                                type="text" 
                                name="subject_option4[]" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Enter option 4"
                                required
                            >
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-indigo-700 font-medium mb-2">
                            Correct Answer
                        </label>
                        <select 
                            name="subject_correct[]" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            required
                        >
                            <option value="">-- Select Correct Answer --</option>
                            <option value="0">Option 1</option>
                            <option value="1">Option 2</option>
                            <option value="2">Option 3</option>
                            <option value="3">Option 4</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Submit Button -->
        <div class="flex justify-end mt-6">
            <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                Create Exam
            </button>
        </div>
    </form>
</div>

<!-- Submit Button
<div class="flex justify-end">
    <button 
        type="submit" 
        name="create_exam" 
        class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
    >
        Create Exam
    </button>
</div> -->
</form>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Subject management
    const addSubjectBtn = document.getElementById('addSubjectBtn');
    const subjectList = document.getElementById('subjectList');
    const questionTabs = document.getElementById('questionTabs');
    const questionSections = document.getElementById('questionSections');
    const subjectTemplate = document.getElementById('subject-template');
    const questionTemplate = document.getElementById('question-template');
    
    // Add new subject
    addSubjectBtn.addEventListener('click', function() {
        // Create subject input
        const subjectItem = document.createElement('div');
        subjectItem.className = 'flex items-center gap-4 mb-2';
        
        const subjectName = "subject_" + Date.now();
        const displayName = "New Subject";
        
        subjectItem.innerHTML = `
            <input 
                type="text" 
                name="subjects[]" 
                value="${displayName}"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="Subject Name"
                required
            >
            <button type="button" class="remove-subject px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500">
                Remove
            </button>
        `;
        subjectList.appendChild(subjectItem);
        
        // Create tab for the subject
        const tabItem = document.createElement('li');
        tabItem.className = 'mr-2';
        tabItem.innerHTML = `
            <a href="#${subjectName}" 
               class="inline-block p-4 border-b-2 rounded-t-lg text-gray-500 hover:text-gray-600 border-transparent hover:border-gray-300 question-tab-link"
               data-target="${subjectName}"
            >
                ${displayName}
            </a>
        `;
        questionTabs.appendChild(tabItem);
        
        // Create question section for the subject
        const newSection = subjectTemplate.cloneNode(true);
        newSection.id = `${subjectName}-section`;
        newSection.classList.remove('hidden');
        
        // Update section title
        newSection.querySelector('h3').textContent = `${displayName} Questions`;
        
        // Set add question button data attribute
        newSection.querySelector('.add-question').setAttribute('data-subject', subjectName);
        
        // Set questions container ID
        newSection.querySelector('.questions-container').id = `${subjectName}-questions`;
        
        // Add a first question to the new section
        const firstQuestion = createNewQuestion(subjectName, 1);
        newSection.querySelector('.questions-container').appendChild(firstQuestion);
        
        questionSections.appendChild(newSection);
        
        // Hide all sections first
        document.querySelectorAll('.question-section').forEach(section => {
            section.classList.add('hidden');
        });
        
        // Show the new section
        newSection.classList.remove('hidden');
        
        // Update active tab
        document.querySelectorAll('.question-tab-link').forEach(tab => {
            tab.classList.remove('active', 'text-indigo-600', 'border-indigo-600');
            tab.classList.add('text-gray-500', 'border-transparent');
        });
        
        tabItem.querySelector('.question-tab-link').classList.add('active', 'text-indigo-600', 'border-indigo-600');
        tabItem.querySelector('.question-tab-link').classList.remove('text-gray-500', 'border-transparent');
        
        // Listen for input changes to update tab name
        const subjectInput = subjectItem.querySelector('input');
        subjectInput.addEventListener('input', function() {
            tabItem.querySelector('.question-tab-link').textContent = this.value;
            newSection.querySelector('h3').textContent = `${this.value} Questions`;
        });
        
        // Listen for remove subject button click
        const removeBtn = subjectItem.querySelector('.remove-subject');
        removeBtn.addEventListener('click', function() {
            // Remove tab
            questionTabs.removeChild(tabItem);
            
            // Remove section
            questionSections.removeChild(newSection);
            
            // Remove subject input
            subjectList.removeChild(subjectItem);
            
            // Activate first tab if there's no active tab
            if (!document.querySelector('.question-tab-link.active')) {
                const firstTab = document.querySelector('.question-tab-link');
                if (firstTab) {
                    firstTab.classList.add('active', 'text-indigo-600', 'border-indigo-600');
                    firstTab.classList.remove('text-gray-500', 'border-transparent');
                    
                    const targetId = firstTab.getAttribute('data-target');
                    document.querySelectorAll('.question-section').forEach(section => {
                        section.classList.add('hidden');
                    });
                    document.getElementById(`${targetId}-section`).classList.remove('hidden');
                }
            }
        });
    });
    
    // Tab navigation
    questionTabs.addEventListener('click', function(e) {
        if (e.target.classList.contains('question-tab-link')) {
            e.preventDefault();
            
            // Remove active class from all tabs
            document.querySelectorAll('.question-tab-link').forEach(tab => {
                tab.classList.remove('active', 'text-indigo-600', 'border-indigo-600');
                tab.classList.add('text-gray-500', 'border-transparent');
            });
            
            // Add active class to clicked tab
            e.target.classList.add('active', 'text-indigo-600', 'border-indigo-600');
            e.target.classList.remove('text-gray-500', 'border-transparent');
            
            // Hide all sections
            document.querySelectorAll('.question-section').forEach(section => {
                section.classList.add('hidden');
            });
            
            // Show the selected section
            const targetId = e.target.getAttribute('data-target');
            document.getElementById(`${targetId}-section`).classList.remove('hidden');
        }
    });
    
    // Add Question button click event delegation
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-question')) {
            const subject = e.target.getAttribute('data-subject');
            const questionsContainer = document.getElementById(`${subject}-questions`);
            const questionCount = questionsContainer.querySelectorAll('.question-item').length + 1;
            
            const newQuestion = createNewQuestion(subject, questionCount);
            questionsContainer.appendChild(newQuestion);
            
            // Enable remove buttons if there's more than one question
            if (questionCount > 1) {
                questionsContainer.querySelectorAll('.remove-question').forEach(btn => {
                    btn.disabled = false;
                });
            }
        }
        
        if (e.target.classList.contains('remove-question')) {
            const questionItem = e.target.closest('.question-item');
            const questionsContainer = questionItem.closest('.questions-container');
            questionsContainer.removeChild(questionItem);
            
            // Update question numbers
            updateQuestionNumbers(questionsContainer);
            
            // Disable remove buttons if only one question remains
            if (questionsContainer.querySelectorAll('.question-item').length <= 1) {
                questionsContainer.querySelectorAll('.remove-question').forEach(btn => {
                    btn.disabled = true;
                });
            }
        }
    });
    
    // Functions to create new question element
    function createNewQuestion(subject, questionNumber) {
        const newQuestion = questionTemplate.querySelector('.question-item').cloneNode(true);
        
        // Update question number
        newQuestion.querySelector('.question-number').textContent = `Question ${questionNumber}`;
        
        // Update input names
        const inputs = newQuestion.querySelectorAll('input, select');
        inputs.forEach(input => {
            const name = input.getAttribute('name');
            input.setAttribute('name', name.replace('subject', subject));
        });
        
        // If it's the first question, disable the remove button
        if (questionNumber === 1) {
            newQuestion.querySelector('.remove-question').disabled = true;
        }
        
        return newQuestion;
    }
    
    // Update question numbers after removal
    function updateQuestionNumbers(container) {
        const questions = container.querySelectorAll('.question-item');
        questions.forEach((question, index) => {
            question.querySelector('.question-number').textContent = `Question ${index + 1}`;
        });
    }
    
    // Initialize the first tab as active
    const firstTab = document.querySelector('.question-tab-link');
    if (firstTab) {
        firstTab.classList.add('active');
    }
});
</script>
</body>
</html>

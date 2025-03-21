<?php
// Start session to maintain state
session_start();

// Check if student is logged in
if (!isset($_SESSION['student_id']) || !isset($_SESSION['student_name'])) {
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// Database Connection
$servername = "localhost";
$username = "dbuser";  // Updated username
$password = "1234";    // Updated password
$dbname = "test";      // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the student has a scheduled exam
$studentId = $_SESSION['student_id'];
$examIsScheduled = false;
$examDateInfo = null;

$checkSchedule = $conn->prepare("SELECT * FROM exam_schedules WHERE student_id = ? ORDER BY exam_date DESC, exam_time DESC LIMIT 1");
$checkSchedule->bind_param("s", $studentId);
$checkSchedule->execute();
$scheduleResult = $checkSchedule->get_result();

if ($scheduleResult->num_rows > 0) {
    $examSchedule = $scheduleResult->fetch_assoc();
    $examIsScheduled = true;
    
    // Format exam date and time for display
    $examDate = new DateTime($examSchedule['exam_date'] . ' ' . $examSchedule['exam_time']);
    $currentDate = new DateTime();
    
    // Check if it's time for the exam
    if ($currentDate < $examDate) {
        $interval = $currentDate->diff($examDate);
        $examDateInfo = "Your exam is scheduled for " . $examDate->format('l, F j, Y') . " at " . $examDate->format('g:i A') . 
                        ". Please come back at that time.";
    }
}

// Define questions for each subject
$questions = [
    'khmer' => [
        ['question' => 'អក្សរក្រមខ្មែរមានចំនួនប៉ុន្មាន?', 'options' => ['33', '35', '74', '66'], 'correct' => 1],
        ['question' => 'ភាសាខ្មែរស្ថិតក្នុងគ្រួសារភាសាមួយណា?', 'options' => ['ចិន-តីបេ', 'ឥណ្ឌូ-អឺរ៉ុប', 'មន-ខ្មែរ', 'អូស្រ្តូនេស៊ី'], 'correct' => 2],
        ['question' => 'តើអក្សរមួយណាដែលជាស្រៈនៅក្នុងភាសាខ្មែរ?', 'options' => ['ក', 'ញ', 'អ', 'ឆ'], 'correct' => 2],
        ['question' => 'តើមួយណាជាពាក្យខ្មែរសុទ្ធ?', 'options' => ['ទូរស័ព្ទ', 'ផ្កាឈូក', 'វិទ្យាល័យ', 'អវកាស'], 'correct' => 1],
        ['question' => 'តើរូបភាពអក្សរខ្មែរបុរាណត្រូវបានរកឃើញនៅសតវត្សទីប៉ុន្មាន?', 'options' => ['ទី១', 'ទី៥', 'ទី៧', 'ទី៩'], 'correct' => 1]
    ],
    'math' => [
        ['question' => 'Solve for x: 3x + 7 = 22', 'options' => ['4', '5', '7', '15'], 'correct' => 1],
        ['question' => 'What is the area of a circle with radius 5 cm?', 'options' => ['25π cm²', '10π cm²', '5π cm²', '15π cm²'], 'correct' => 0],
        ['question' => 'If x = 3 and y = 4, what is the value of x² + y²?', 'options' => ['7', '12', '25', '49'], 'correct' => 2],
        ['question' => 'Simplify: (2³)²', 'options' => ['12', '16', '32', '64'], 'correct' => 3],
        ['question' => 'What is the solution to the equation 2x² - 8 = 0?', 'options' => ['x = ±2', 'x = ±4', 'x = ±√2', 'x = ±2√2'], 'correct' => 0]
    ],
    'english' => [
        ['question' => 'Choose the correct sentence:', 'options' => ['She don\'t like apples.', 'She doesn\'t likes apples.', 'She doesn\'t like apples.', 'She not like apples.'], 'correct' => 2],
        ['question' => 'What is the past tense of "go"?', 'options' => ['goed', 'went', 'gone', 'going'], 'correct' => 1],
        ['question' => 'Which word is an adjective?', 'options' => ['quickly', 'beautiful', 'jump', 'organization'], 'correct' => 1],
        ['question' => 'Complete the sentence: "If it rains tomorrow, I ____ stay home."', 'options' => ['will', 'would', 'am', 'be'], 'correct' => 0],
        ['question' => 'Choose the correct spelling:', 'options' => ['accomodate', 'accommodate', 'acommodate', 'accomadate'], 'correct' => 1]
    ],
    'science' => [
        ['question' => 'What is the chemical symbol for water?', 'options' => ['H2O', 'CO2', 'O2', 'N2'], 'correct' => 0],
        ['question' => 'Which planet is known as the Red Planet?', 'options' => ['Venus', 'Jupiter', 'Mars', 'Saturn'], 'correct' => 2],
        ['question' => 'What is the basic unit of life?', 'options' => ['Atom', 'Cell', 'Molecule', 'Tissue'], 'correct' => 1],
        ['question' => 'What type of energy is stored in a battery?', 'options' => ['Kinetic Energy', 'Thermal Energy', 'Chemical Energy', 'Nuclear Energy'], 'correct' => 2],
        ['question' => 'Which of these is NOT a state of matter?', 'options' => ['Solid', 'Liquid', 'Gas', 'Energy'], 'correct' => 3]
    ]
];

// Process form submission
$examCompleted = false;
$examResults = [];
$totalScore = 0;
$totalQuestions = 0;
$examId = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_exam'])) {
    // Generate unique exam ID
    $examId = 'EX_' . date('Ymd') . '_' . uniqid();
    
    // Process answers
    $subjectScores = [];
    $totalCorrect = 0;
    
    // Loop through each subject
    foreach ($questions as $subject => $subjectQuestions) {
        $subjectTotal = count($subjectQuestions);
        $subjectCorrect = 0;
        
        // Check answers for this subject
        for ($i = 0; $i < $subjectTotal; $i++) {
            $questionKey = $subject . '_' . $i;
            $userAnswer = isset($_POST[$questionKey]) ? intval($_POST[$questionKey]) : -1;
            $correctAnswer = $subjectQuestions[$i]['correct'];
            
            if ($userAnswer === $correctAnswer) {
                $subjectCorrect++;
                $totalCorrect++;
            }
        }
        
        // Calculate percentage
        $subjectPercentage = ($subjectCorrect / $subjectTotal) * 100;
        
        // Store subject score
        $subjectScores[$subject] = [
            'score' => $subjectCorrect,
            'total' => $subjectTotal,
            'percentage' => $subjectPercentage
        ];
        
        // Insert into subject_scores table
        $stmt = $conn->prepare("INSERT INTO subject_scores (exam_id, subject, score, total, percentage, created) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiii", 
            $examId, 
            $subject, 
            $subjectCorrect, 
            $subjectTotal, 
            $subjectPercentage
        );
        $stmt->execute();
    }
    
    // Calculate overall score
    $totalQuestions = 0;
    foreach ($questions as $subject => $subjectQuestions) {
        $totalQuestions += count($subjectQuestions);
    }
    
    $totalPercentage = ($totalCorrect / $totalQuestions) * 100;
    
    // Insert into exams table
    $studentName = $_SESSION['student_name'];
    $stmt = $conn->prepare("INSERT INTO exams (exam_id, student_name, student_id, total_score, total_questions, percentage, exam_date) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssiid", 
        $examId, 
        $studentName, 
        $studentId, 
        $totalCorrect, 
        $totalQuestions, 
        $totalPercentage
    );
    
    if ($stmt->execute()) {
        $examCompleted = true;
        $examResults = [
            'examId' => $examId,
            'totalScore' => $totalCorrect,
            'totalQuestions' => $totalQuestions,
            'percentage' => $totalPercentage,
            'subjectScores' => $subjectScores
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Examination</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .dot-pattern {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, rgba(139, 92, 246, 0.4) 1px, transparent 1px);
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
        
        .question-card {
            transition: all 0.3s ease;
        }
        
        .question-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
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
            <h1 class="text-3xl font-bold text-purple-900 text-center">Online Examination</h1>
            <p class="text-purple-800 text-center mt-2">Welcome, <?php echo $_SESSION['student_name']; ?> (ID: <?php echo $_SESSION['student_id']; ?>)</p>
        </div>
        
        <?php if ($examCompleted): ?>
        <!-- Exam Results -->
        <div class="glass rounded-xl p-6 shadow-lg mb-6">
            <div class="bg-green-100 border-l-4 border-green-500 p-4 mb-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-green-700 font-medium">Exam completed successfully! Your score has been recorded.</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white bg-opacity-50 rounded-xl p-6 mb-6">
                <h2 class="text-2xl font-semibold text-purple-800 mb-4">Exam Results</h2>
                <div class="mb-6">
                    <p class="text-purple-900 font-medium">Exam ID: <span class="font-bold"><?php echo $examResults['examId']; ?></span></p>
                    <p class="text-purple-900 font-medium mt-2">Overall Score: <span class="font-bold"><?php echo $examResults['totalScore']; ?> / <?php echo $examResults['totalQuestions']; ?></span></p>
                    <p class="text-purple-900 font-medium mt-2">Percentage: <span class="font-bold"><?php echo number_format($examResults['percentage'], 2); ?>%</span></p>
                    
                    <div class="w-full bg-gray-200 rounded-full h-4 mt-4">
                        <div class="bg-purple-600 h-4 rounded-full" style="width: <?php echo min(100, $examResults['percentage']); ?>%"></div>
                    </div>
                </div>
                
                <h3 class="text-xl font-semibold text-purple-800 mb-3">Subject Breakdown</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <?php foreach ($examResults['subjectScores'] as $subject => $score): ?>
                    <div class="bg-white rounded-lg p-4 shadow">
                        <h4 class="text-lg font-semibold text-purple-900 capitalize"><?php echo $subject; ?></h4>
                        <p class="text-purple-800 mt-1">Score: <?php echo $score['score']; ?> / <?php echo $score['total']; ?></p>
                        <p class="text-purple-800">Percentage: <?php echo number_format($score['percentage'], 2); ?>%</p>
                        
                        <div class="w-full bg-gray-200 rounded-full h-3 mt-3">
                            <div class="bg-purple-600 h-3 rounded-full" style="width: <?php echo min(100, $score['percentage']); ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="flex justify-center mt-8">
                <a 
                    href="./login-page.php" 
                    class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-8 rounded-lg transition-colors"
                >
                    Logout
                </a>
            </div>
        </div>
        
        <?php elseif (!empty($examDateInfo)): ?>
        <!-- Exam Not Yet Available -->
        <div class="glass rounded-xl p-6 shadow-lg mb-6">
            <div class="bg-yellow-100 border-l-4 border-yellow-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-yellow-700 font-medium"><?php echo $examDateInfo; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-center mt-8">
                <a 
                    href="./login-page.php" 
                    class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-8 rounded-lg transition-colors"
                >
                    Logout
                </a>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Exam Form -->
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="examForm">
            <div class="mb-6">
                <div class="glass rounded-xl p-6 shadow-lg">
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-yellow-700 font-medium">
                                    Important Instructions:
                                </p>
                                <ul class="list-disc list-inside text-yellow-700 mt-2">
                                    <li>Read each question carefully before answering.</li>
                                    <li>You must complete the exam in one session.</li>
                                    <li>Once submitted, you cannot retake the exam.</li>
                                    <li>The exam consists of 20 questions across 4 subjects.</li>
                                    <li>Each question has only one correct answer.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-semibold text-purple-800">Exam Questions</h2>
                        <div id="timer" class="text-xl font-bold text-purple-900">30:00</div>
                    </div>
                    
                    <!-- Exam navigation tabs -->
                    <div class="mb-6 border-b border-gray-200">
                        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
                            <?php 
                            $activeTab = "text-purple-600 border-purple-600";
                            $inactiveTab = "text-gray-500 hover:text-gray-600 border-transparent hover:border-gray-300";
                            $subjects = array_keys($questions);
                            foreach ($subjects as $index => $subject): 
                            ?>
                            <li class="mr-2">
                                <a href="#<?php echo $subject; ?>" 
                                   class="inline-block p-4 border-b-2 rounded-t-lg <?php echo $index === 0 ? $activeTab : $inactiveTab; ?> tab-link"
                                   data-target="<?php echo $subject; ?>"
                                >
                                    <?php echo ucfirst($subject); ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <!-- Subject question sections -->
                    <?php foreach ($questions as $subject => $subjectQuestions): ?>
                    <div id="<?php echo $subject; ?>-section" class="question-section <?php echo $subject !== 'khmer' ? 'hidden' : ''; ?>">
                        <h3 class="text-xl font-semibold text-purple-800 mb-4 capitalize"><?php echo $subject; ?> (<?php echo count($subjectQuestions); ?> questions)</h3>
                        
                        <?php foreach ($subjectQuestions as $index => $question): ?>
                        <div class="bg-white bg-opacity-70 rounded-xl p-5 mb-6 shadow-md question-card">
                            <p class="text-lg font-medium text-purple-900 mb-3">
                                <span class="font-bold"><?php echo ($index + 1); ?>.</span> 
                                <?php echo $question['question']; ?>
                            </p>
                            
                            <div class="space-y-2 ml-6">
                                <?php foreach ($question['options'] as $optionIndex => $option): ?>
                                <div class="flex items-center">
                                    <input 
                                        type="radio" 
                                        id="<?php echo $subject . '_' . $index . '_' . $optionIndex; ?>" 
                                        name="<?php echo $subject . '_' . $index; ?>" 
                                        value="<?php echo $optionIndex; ?>" 
                                        class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300"
                                        required
                                    >
                                    <label for="<?php echo $subject . '_' . $index . '_' . $optionIndex; ?>" class="ml-2 block text-gray-700">
                                        <?php echo $option; ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Navigation buttons -->
                    <div class="flex justify-between mt-8">
                        <button 
                            type="button" 
                            id="prevBtn" 
                            class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded-lg transition-colors disabled:opacity-50"
                            disabled
                        >
                            Previous
                        </button>
                        
                        <span id="pageIndicator" class="text-purple-900 font-medium self-center">
                            Page 1 of 4
                        </span>
                        
                        <button 
                            type="button" 
                            id="nextBtn" 
                            class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded-lg transition-colors"
                        >
                            Next
                        </button>
                    </div>
                    
                    <!-- Submit button (initially hidden) -->
                    <div id="submitBtnContainer" class="hidden mt-8 text-center">
                        <p class="text-purple-800 mb-4">
                            Ready to submit? Make sure you've answered all questions before submitting.
                        </p>
                        <button 
                            type="submit" 
                            name="submit_exam" 
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg transition-colors"
                        >
                            Submit Exam
                        </button>
                    </div>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab navigation
            const tabLinks = document.querySelectorAll('.tab-link');
            const questionSections = document.querySelectorAll('.question-section');
            const subjects = ['khmer', 'math', 'english', 'science'];
            let currentTabIndex = 0;
            
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const pageIndicator = document.getElementById('pageIndicator');
            const submitBtnContainer = document.getElementById('submitBtnContainer');
            
            // Timer functionality
            const timerDisplay = document.getElementById('timer');
            let timeLeft = 30 * 60; // 30 minutes in seconds
            
            const examTimer = setInterval(function() {
                timeLeft--;
                
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                
                timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                if (timeLeft <= 0) {
                    clearInterval(examTimer);
                    alert('Time is up! Your exam will be submitted automatically.');
                    document.getElementById('examForm').submit();
                }
                
                // Warning when 5 minutes left
                if (timeLeft === 300) {
                    alert('5 minutes remaining!');
                }
            }, 1000);
            
            // Function to show current tab
            function showTab(index) {
                // Hide all sections
                questionSections.forEach(section => {
                    section.classList.add('hidden');
                });
                
                // Deactivate all tabs
                tabLinks.forEach(tab => {
                    tab.classList.remove('text-purple-600', 'border-purple-600');
                    tab.classList.add('text-gray-500', 'hover:text-gray-600', 'border-transparent', 'hover:border-gray-300');
                });
                
                // Show current section and activate tab
                document.getElementById(`${subjects[index]}-section`).classList.remove('hidden');
                tabLinks[index].classList.remove('text-gray-500', 'hover:text-gray-600', 'border-transparent', 'hover:border-gray-300');
                tabLinks[index].classList.add('text-purple-600', 'border-purple-600');
                
                // Update navigation buttons
                prevBtn.disabled = index === 0;
                if (index === subjects.length - 1) {
                    nextBtn.classList.add('hidden');
                    submitBtnContainer.classList.remove('hidden');
                } else {
                    nextBtn.classList.remove('hidden');
                    submitBtnContainer.classList.add('hidden');
                }
                
                // Update page indicator
                pageIndicator.textContent = `Page ${index + 1} of ${subjects.length}`;
                
                currentTabIndex = index;
            }
            
            // Initialize tab clicks
            tabLinks.forEach((tab, index) => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    showTab(index);
                });
            });
            
            // Navigation button handlers
            prevBtn.addEventListener('click', function() {
                if (currentTabIndex > 0) {
                    showTab(currentTabIndex - 1);
                }
            });
            
            nextBtn.addEventListener('click', function() {
                if (currentTabIndex < subjects.length - 1) {
                    showTab(currentTabIndex + 1);
                }
            });
            
            // Form validation
            document.getElementById('examForm').addEventListener('submit', function(e) {
                let allQuestionsAnswered = true;
                
                // Check each subject
                subjects.forEach(subject => {
                    const questions = document.querySelectorAll(`[name^="${subject}_"]`);
                    const uniqueQuestionNames = new Set();
                    
                    questions.forEach(question => {
                        const name = question.getAttribute('name');
                        uniqueQuestionNames.add(name);
                    });
                    
                    // Check if all questions in this subject have an answer
                    uniqueQuestionNames.forEach(name => {
                        const answered = document.querySelector(`input[name="${name}"]:checked`);
                        if (!answered) {
                            allQuestionsAnswered = false;
                        }
                    });
                });
                
                if (!allQuestionsAnswered) {
                    e.preventDefault();
                    alert('Please answer all questions before submitting.');
                    return false;
                }
                
                // Confirm submission
                if (!confirm('Are you sure you want to submit your exam? You cannot make changes afterwards.')) {
                    e.preventDefault();
                    return false;
                }
                
                // Stop the timer
                clearInterval(examTimer);
                return true;
            });
            
            // Initialize first tab
            showTab(0);
        });
    </script>
</body>
</html>

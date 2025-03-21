<?php
// Start session to maintain state
session_start();

// Database Connection
$servername = "localhost";
$username = "dbuser";  // Default XAMPP username
$password = "1234";      // Default XAMPP password
$dbname = "test"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Define questions for each subject
$questions = [
    'khmer' => [
        ['question' => 'អក្សរក្រមខ្មែរមានចំនួនប៉ុន្មាន?', 'options' => ['៣៣', '៤០', '៧៤', '២៦'], 'correct' => 0],
        ['question' => 'តើភាសាខ្មែរចាត់ចូលក្នុងគ្រួសារភាសាមួយណា?', 'options' => ['ឥណ្ឌូ-អឺរ៉ុប', 'ម៉ុងហ្គោល', 'អូស្ត្រូអាស៊ី', 'ម៉ុន-ខ្មែរ'], 'correct' => 3],
        ['question' => 'ក្នុងចំណោមអ្នកនិពន្ធខាងក្រោម តើអ្នកណាជាអ្នកនិពន្ធសម័យអង្គរ?', 'options' => ['គង់ ប៊ុនសូរ', 'ពុទ្ធសាស្ត្រាចារ្យ ជា សុខុម', 'កវី ស៊ូត', 'គង់ ណាស'], 'correct' => 2],
    ],
    'math' => [
        ['question' => 'Solve for x: 2x + 5 = 15', 'options' => ['x = 5', 'x = 10', 'x = 7.5', 'x = 5.5'], 'correct' => 0],
        ['question' => 'What is the value of π (pi) to two decimal places?', 'options' => ['3.14', '3.41', '3.12', '3.16'], 'correct' => 0],
        ['question' => 'If f(x) = x² + 3x + 2, what is f(2)?', 'options' => ['12', '10', '8', '14'], 'correct' => 0],
    ],
    'physics' => [
        ['question' => 'What is Newton\'s First Law of Motion?', 'options' => ['F = ma', 'For every action, there is an equal and opposite reaction', 'An object in motion stays in motion, an object at rest stays at rest unless acted upon by an external force', 'Energy cannot be created or destroyed'], 'correct' => 2],
        ['question' => 'What is the unit of electric current?', 'options' => ['Volt', 'Watt', 'Ampere', 'Ohm'], 'correct' => 2],
        ['question' => 'Which of these is NOT a fundamental force in nature?', 'options' => ['Gravitational force', 'Electromagnetic force', 'Strong nuclear force', 'Centrifugal force'], 'correct' => 3],
    ],
    'english' => [
        ['question' => 'Which of the following is a correct sentence?', 'options' => ['She don\'t like apples', 'They was running', 'He doesn\'t know the answer', 'We is going home'], 'correct' => 2],
        ['question' => 'What is the past tense of "go"?', 'options' => ['Gone', 'Went', 'Goed', 'Going'], 'correct' => 1],
        ['question' => 'Choose the correct word: "I need to _____ this document before sending it."', 'options' => ['review', 'revue', 'reveiw', 'reveaw'], 'correct' => 0],
    ],
    'science' => [
        ['question' => 'Which of the following is NOT a state of matter?', 'options' => ['Solid', 'Liquid', 'Gas', 'Energy'], 'correct' => 3],
        ['question' => 'What is the chemical symbol for gold?', 'options' => ['Go', 'Gl', 'Au', 'Ag'], 'correct' => 2],
        ['question' => 'Which planet is known as the Red Planet?', 'options' => ['Venus', 'Jupiter', 'Mars', 'Saturn'], 'correct' => 2],
    ],
    'programming' => [
        ['question' => 'Which of the following is a valid variable name in PHP?', 'options' => ['$123variable', '$_variable', '123$variable', '$variable@123'], 'correct' => 1],
        ['question' => 'What does HTML stand for?', 'options' => ['Hyper Text Markup Language', 'Home Tool Markup Language', 'Hyperlinks and Text Markup Language', 'Home Text Making Language'], 'correct' => 0],
        ['question' => 'Which of these is NOT a programming paradigm?', 'options' => ['Procedural', 'Object-Oriented', 'Functional', 'Sequential'], 'correct' => 3],
    ],
];

$results = [];
$showResults = false;
$studentName = "";
$studentId = "";
$examId = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $showResults = true;
    
    // Get student information
    $studentName = isset($_POST['student_name']) ? $_POST['student_name'] : "Anonymous";
    $studentId = isset($_POST['student_id']) ? $_POST['student_id'] : "000";
    
    // Generate unique exam ID
    $examId = uniqid("EXAM_");
    
    // Calculate scores for each subject
    foreach ($questions as $subject => $subjectQuestions) {
        $score = 0;
        $total = count($subjectQuestions);
        
        for ($i = 0; $i < $total; $i++) {
            $questionKey = $subject . '_' . $i;
            if (isset($_POST[$questionKey]) && $_POST[$questionKey] == $subjectQuestions[$i]['correct']) {
                $score++;
            }
        }
        
        $results[$subject] = [
            'score' => $score,
            'total' => $total,
            'percentage' => ($score / $total) * 100
        ];
    }
    
    // Calculate overall score
    $totalScore = 0;
    $totalQuestions = 0;
    
    foreach ($results as $subject => $result) {
        $totalScore += $result['score'];
        $totalQuestions += $result['total'];
    }
    
    $results['overall'] = [
        'score' => $totalScore,
        'total' => $totalQuestions,
        'percentage' => ($totalScore / $totalQuestions) * 100
    ];
    
    // Store results in database
    // 1. Insert into exams table
    $stmt = $conn->prepare("INSERT INTO exams (exam_id, student_name, student_id, total_score, total_questions, percentage, exam_date) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssidi", 
        $examId, 
        $studentName, 
        $studentId, 
        $totalScore, 
        $totalQuestions, 
        $results['overall']['percentage']
    );
    $stmt->execute();
    
    // 2. Insert subject scores
    foreach ($results as $subject => $result) {
        if ($subject != 'overall') {
            $stmt = $conn->prepare("INSERT INTO subject_scores (exam_id, subject, score, total, percentage) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssidi", 
                $examId, 
                $subject, 
                $result['score'], 
                $result['total'], 
                $result['percentage']
            );
            $stmt->execute();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examination Form</title>
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
    </style>
</head>
<body class="min-h-screen ">
    <!-- Background gradient -->
    <div class="fixed inset-0 bg-gradient-to-br from-purple-100 to-sky-100 z-0"></div>
    
    <!-- Dot pattern -->
    <div class=""></div>
    
    <div class="relative z-10 container mx-auto py-8 px-4">
        <!-- Header -->
        <div class="glass rounded-t-xl p-6 shadow-lg">
            <h1 class="text-3xl font-bold text-purple-900 text-center">Subject Examination</h1>
            <p class="text-purple-800 text-center mt-2">Complete all sections to receive your results</p>
        </div>
        
        <?php if (!$showResults): ?>
        <!-- Examination Form -->
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="glass rounded-b-xl p-6 shadow-lg">
            <!-- Student Information -->
            <div class="bg-white bg-opacity-50 rounded-xl p-6 mb-6">
                <h2 class="text-2xl font-semibold text-purple-800 mb-4">Student Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="student_name" class="block text-purple-900 font-medium mb-2">Full Name</label>
                        <input 
                            type="text" 
                            id="student_name" 
                            name="student_name" 
                            class="w-full p-2 border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            required
                        >
                    </div>
                    <div>
                        <label for="student_id" class="block text-purple-900 font-medium mb-2">Student ID</label>
                        <input 
                            type="text" 
                            id="student_id" 
                            name="student_id" 
                            class="w-full p-2 border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            required
                        >
                    </div>
                </div>
            </div>
        
            <div class="space-y-8">
                <?php foreach ($questions as $subject => $subjectQuestions): ?>
                    <div class="bg-white bg-opacity-50 rounded-xl p-6">
                        <h2 class="text-2xl font-semibold text-purple-800 mb-4 capitalize"><?php echo $subject; ?></h2>
                        
                        <?php foreach ($subjectQuestions as $index => $q): ?>
                            <div class="mb-6">
                                <p class="text-purple-900 font-medium mb-2"><?php echo ($index + 1) . '. ' . $q['question']; ?></p>
                                <div class="space-y-2 ml-4">
                                    <?php foreach ($q['options'] as $optionIndex => $option): ?>
                                        <div class="flex items-center">
                                            <input 
                                                type="radio" 
                                                id="<?php echo $subject . '_' . $index . '_' . $optionIndex; ?>" 
                                                name="<?php echo $subject . '_' . $index; ?>" 
                                                value="<?php echo $optionIndex; ?>" 
                                                class="mr-2 h-4 w-4 text-purple-600"
                                                required
                                            >
                                            <label 
                                                for="<?php echo $subject . '_' . $index . '_' . $optionIndex; ?>" 
                                                class="text-purple-800"
                                            >
                                                <?php echo $option; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                
                <div class="flex justify-center mt-6">
                    <button 
                        type="submit" 
                        name="submit" 
                        class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-8 rounded-lg transition-colors"
                    >
                        Submit Exam
                    </button>
                </div>
            </div>
        </form>
        
        <?php else: ?>
        <!-- Results Display -->
        <div class="glass rounded-b-xl p-6 shadow-lg">
            <h2 class="text-2xl font-semibold text-purple-800 mb-6 text-center">Your Examination Results</h2>
            
            <!-- Student Information -->
            <div class="bg-white bg-opacity-50 rounded-xl p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-purple-900 font-medium">Name: <span class="font-bold"><?php echo htmlspecialchars($studentName); ?></span></p>
                    </div>
                    <div>
                        <p class="text-purple-900 font-medium">Student ID: <span class="font-bold"><?php echo htmlspecialchars($studentId); ?></span></p>
                    </div>
                    <div>
                        <p class="text-purple-900 font-medium">Exam ID: <span class="font-bold"><?php echo $examId; ?></span></p>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                <?php foreach ($results as $subject => $result): ?>
                    <?php if ($subject !== 'overall'): ?>
                        <div class="bg-white bg-opacity-50 rounded-xl p-4 shadow">
                            <h3 class="text-xl font-medium text-purple-900 capitalize mb-2"><?php echo $subject; ?></h3>
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-purple-800">Score: <?php echo $result['score']; ?>/<?php echo $result['total']; ?></p>
                                    <p class="text-purple-800">Percentage: <?php echo number_format($result['percentage'], 1); ?>%</p>
                                </div>
                                <div class="relative h-16 w-16">
                                    <svg viewBox="0 0 36 36" class="h-16 w-16">
                                        <path
                                            d="M18 2.0845
                                            a 15.9155 15.9155 0 0 1 0 31.831
                                            a 15.9155 15.9155 0 0 1 0 -31.831"
                                            stroke="rgba(139, 92, 246, 0.2)"
                                            stroke-width="3"
                                            fill="none"
                                        />
                                        <path
                                            d="M18 2.0845
                                            a 15.9155 15.9155 0 0 1 0 31.831
                                            a 15.9155 15.9155 0 0 1 0 -31.831"
                                            stroke="#8B5CF6"
                                            stroke-width="3"
                                            fill="none"
                                            stroke-dasharray="<?php echo $result['percentage']; ?>, 100"
                                            stroke-linecap="round"
                                        />
                                    </svg>
                                    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-purple-800 font-bold text-sm">
                                        <?php echo round($result['percentage']); ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <!-- Overall Result -->
            <div class="bg-purple-100 rounded-xl p-6 shadow">
                <h3 class="text-2xl font-semibold text-purple-900 mb-4 text-center">Overall Performance</h3>
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-center md:text-left mb-4 md:mb-0">
                        <p class="text-purple-800 text-lg">Total Score: <?php echo $results['overall']['score']; ?>/<?php echo $results['overall']['total']; ?></p>
                        <p class="text-purple-800 text-lg">Overall Percentage: <?php echo number_format($results['overall']['percentage'], 1); ?>%</p>
                        <p class="text-purple-900 font-bold mt-2">
                            <?php 
                            $overallPercentage = $results['overall']['percentage'];
                            if ($overallPercentage >= 90) {
                                echo 'Excellent!';
                            } elseif ($overallPercentage >= 75) {
                                echo 'Very Good!';
                            } elseif ($overallPercentage >= 60) {
                                echo 'Good!';
                            } elseif ($overallPercentage >= 50) {
                                echo 'Fair!';
                            } else {
                                echo 'Needs Improvement';
                            }
                            ?>
                        </p>
                    </div>
                    <div class="relative h-32 w-32">
                        <svg viewBox="0 0 36 36" class="h-32 w-32">
                            <path
                                d="M18 2.0845
                                a 15.9155 15.9155 0 0 1 0 31.831
                                a 15.9155 15.9155 0 0 1 0 -31.831"
                                stroke="rgba(139, 92, 246, 0.2)"
                                stroke-width="4"
                                fill="none"
                            />
                            <path
                                d="M18 2.0845
                                a 15.9155 15.9155 0 0 1 0 31.831
                                a 15.9155 15.9155 0 0 1 0 -31.831"
                                stroke="#8B5CF6"
                                stroke-width="4"
                                fill="none"
                                stroke-dasharray="<?php echo $results['overall']['percentage']; ?>, 100"
                                stroke-linecap="round"
                            />
                        </svg>
                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-purple-800 font-bold text-xl">
                            <?php echo round($results['overall']['percentage']); ?>%
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-center mt-8">
                <a 
                    href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" 
                    class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-8 rounded-lg transition-colors"
                >
                    Take Exam Again
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
<!-- Code injected by live-server -->
<script>
	// <![CDATA[  <-- For SVG support
	if ('WebSocket' in window) {
		(function () {
			function refreshCSS() {
				var sheets = [].slice.call(document.getElementsByTagName("link"));
				var head = document.getElementsByTagName("head")[0];
				for (var i = 0; i < sheets.length; ++i) {
					var elem = sheets[i];
					var parent = elem.parentElement || head;
					parent.removeChild(elem);
					var rel = elem.rel;
					if (elem.href && typeof rel != "string" || rel.length == 0 || rel.toLowerCase() == "stylesheet") {
						var url = elem.href.replace(/(&|\?)_cacheOverride=\d+/, '');
						elem.href = url + (url.indexOf('?') >= 0 ? '&' : '?') + '_cacheOverride=' + (new Date().valueOf());
					}
					parent.appendChild(elem);
				}
			}
			var protocol = window.location.protocol === 'http:' ? 'ws://' : 'wss://';
			var address = protocol + window.location.host + window.location.pathname + '/ws';
			var socket = new WebSocket(address);
			socket.onmessage = function (msg) {
				if (msg.data == 'reload') window.location.reload();
				else if (msg.data == 'refreshcss') refreshCSS();
			};
			if (sessionStorage && !sessionStorage.getItem('IsThisFirstTime_Log_From_LiveServer')) {
				console.log('Live reload enabled.');
				sessionStorage.setItem('IsThisFirstTime_Log_From_LiveServer', true);
			}
		})();
	}
	else {
		console.error('Upgrade your browser. This Browser is NOT supported WebSocket for Live-Reloading.');
	}
	// ]]>
</script>
</body>
</html>
<?php
// Start session to maintain state
session_start();

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

$errorMessage = "";
$loginSuccess = false;

// Process login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $studentId = $conn->real_escape_string($_POST['student_id']);
    $email = $conn->real_escape_string($_POST['email']);
    
    // Check if student exists with given ID and email
    $stmt = $conn->prepare("SELECT student_id, first_name, last_name FROM students WHERE student_id = ? AND email = ?");
    $stmt->bind_param("ss", $studentId, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        
        // Store student info in session
        $_SESSION['student_id'] = $student['student_id'];
        $_SESSION['student_name'] = $student['first_name'] . " " . $student['last_name'];
        
        $loginSuccess = true;
    } else {
        $errorMessage = "Invalid student ID or email. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login</title>
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
<body class="min-h-screen flex items-center justify-center">
    <!-- Background gradient -->
    <div class="fixed inset-0 bg-gradient-to-br from-purple-100 to-sky-100 z-0"></div>
    
    <!-- Dot pattern -->
    <div class="dot-pattern"></div>
    
    <div class="relative z-10 w-full max-w-md px-4">
        <!-- Header -->
        <div class="glass rounded-t-xl p-6 shadow-lg">
            <h1 class="text-3xl font-bold text-purple-900 text-center">Student Login</h1>
            <p class="text-purple-800 text-center mt-2">Enter your credentials to access your exam</p>
        </div>
        
        <?php if ($loginSuccess): ?>
        <!-- Login Success -->
        <div class="glass rounded-b-xl p-8 shadow-lg">
            <div class="bg-green-100 border-l-4 border-green-500 p-4 mb-6 rounded-lg">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm leading-5 font-medium text-green-800">
                            Login successful! Welcome back, <?php echo $_SESSION['student_name']; ?>!
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-center mt-8">
                <a 
                    href="exam.php" 
                    class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-8 rounded-lg transition-colors"
                >
                    Go to Exam
                </a>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Login Form -->
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="glass rounded-b-xl p-6 shadow-lg">
            
            <?php if (!empty($errorMessage)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 p-4 mb-6 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">
                            <?php echo $errorMessage; ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="space-y-4">
                <div>
                    <label for="student_id" class="block text-purple-900 font-medium mb-2">Student ID</label>
                    <input 
                        type="text" 
                        id="student_id" 
                        name="student_id" 
                        class="w-full p-3 border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                        placeholder="Enter your student ID"
                        required
                    >
                </div>
                
                <div>
                    <label for="email" class="block text-purple-900 font-medium mb-2">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="w-full p-3 border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                        placeholder="Enter your registered email"
                        required
                    >
                </div>
            </div>
            
            <div class="mt-6">
                <button 
                    type="submit" 
                    name="login" 
                    class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-4 rounded-lg transition-colors"
                >
                    Login
                </button>
            </div>
            
            <div class="mt-4 text-center">
                <p class="text-purple-800">
                    Don't have an account? 
                    <a href="student-registration.php" class="text-purple-600 hover:text-purple-800 font-semibold">Register here</a>
                </p>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>

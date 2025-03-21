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

// Set default timezone
// date_default_timezone_set('Asia/Phnom_Penh'); // Change this to your timezone

// Define available exam slots (you can modify these as needed)
$examSlots = [
    ['date' => date('Y-m-d', strtotime('+0 day')), 'time' => '09:00 AM'],
    ['date' => date('Y-m-d', strtotime('+0 day')), 'time' => '02:00 PM'],
    ['date' => date('Y-m-d', strtotime('+1 day')), 'time' => '09:00 AM'],
    ['date' => date('Y-m-d', strtotime('+1 day')), 'time' => '02:00 PM'],
    ['date' => date('Y-m-d', strtotime('+2 days')), 'time' => '09:00 AM'],
    ['date' => date('Y-m-d', strtotime('+2 days')), 'time' => '02:00 PM'],
    ['date' => date('Y-m-d', strtotime('+3 days')), 'time' => '09:00 AM'],
    ['date' => date('Y-m-d', strtotime('+3 days')), 'time' => '02:00 PM'],
];

$registrationSuccess = false;
$registrationMessage = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    // Get student information
    $firstName = isset($_POST['first_name']) ? $conn->real_escape_string($_POST['first_name']) : "";
    $lastName = isset($_POST['last_name']) ? $conn->real_escape_string($_POST['last_name']) : "";
    $email = isset($_POST['email']) ? $conn->real_escape_string($_POST['email']) : "";
    $address = isset($_POST['address']) ? $conn->real_escape_string($_POST['address']) : "";
    $phone = isset($_POST['phone']) ? $conn->real_escape_string($_POST['phone']) : "";
    $dateOfBirth = isset($_POST['date_of_birth']) ? $conn->real_escape_string($_POST['date_of_birth']) : "";
    $gender = isset($_POST['gender']) ? $conn->real_escape_string($_POST['gender']) : "";
    $examDate = isset($_POST['exam_date']) ? $conn->real_escape_string($_POST['exam_date']) : "";
    $examTime = isset($_POST['exam_time']) ? $conn->real_escape_string($_POST['exam_time']) : "";
    
    // Generate unique student ID (you can customize this format)
    $studentId = 'STU_' . date('Ym') . '_' . rand(1000, 9999);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registrationMessage = "Invalid email format. Please enter a valid email address.";
    } 
    // Validate phone number (basic validation)
    elseif (!preg_match('/^[0-9]{9,15}$/', $phone)) {
        $registrationMessage = "Invalid phone number. Please enter a valid phone number.";
    } 
    else {
        // Check if email already exists
        $checkEmail = $conn->prepare("SELECT email FROM students WHERE email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $result = $checkEmail->get_result();
        
        if ($result->num_rows > 0) {
            $registrationMessage = "This email address is already registered. Please use a different email.";
        } else {
            // Insert into students table
            $stmt = $conn->prepare("INSERT INTO students (student_id, first_name, last_name, email, address, phone, date_of_birth, gender, registration_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssssss", 
                $studentId, 
                $firstName, 
                $lastName, 
                $email, 
                $address, 
                $phone,
                $dateOfBirth,
                $gender
            );
            
            if ($stmt->execute()) {
                // Schedule exam
                $stmt = $conn->prepare("INSERT INTO exam_schedules (student_id, exam_date, exam_time, schedule_created) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("sss", 
                    $studentId, 
                    $examDate, 
                    $examTime
                );
                
                if ($stmt->execute()) {
                    $registrationSuccess = true;
                    $registrationMessage = "Registration successful! Your Student ID is: " . $studentId;
                    
                    // Store in session for the success page
                    $_SESSION['registration_success'] = true;
                    $_SESSION['student_id'] = $studentId;
                    $_SESSION['student_name'] = $firstName . " " . $lastName;
                    $_SESSION['exam_date'] = $examDate;
                    $_SESSION['exam_time'] = $examTime;
                    
                    // Optional: redirect to exam page
                    // header("Location: exam2.php");
                    // exit();
                } else {
                    $registrationMessage = "Error scheduling exam: " . $stmt->error;
                }
            } else {
                $registrationMessage = "Error registering student: " . $stmt->error;
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
    <title>Student Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .dot-pattern {
            position: fixed;
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
<body class="min-h-screen bg-gradient-to-br from-purple-100 to-sky-100">
    <!-- Dot pattern -->
    <div class="dot-pattern"></div>
    
    <div class="relative z-10 container mx-auto py-8 px-4">
        <!-- Header -->
        <!-- <div class="glass rounded-t-xl p-6 shadow-lg">
            <h1 class="text-3xl font-bold text-purple-900 text-center">Student Registration</h1>
            <p class="text-purple-800 text-center mt-2">Register for your examination</p>
        </div> -->
        <div class="glass rounded-t-xl p-6 flex flex-col items-center text-center">
            <img src="https://www.rupp.edu.kh/logo/rupp_logo.png" alt="RUPP" width="150px" class="mb-4">
            <h1 class="text-3xl font-bold text-purple-900">Student Registration</h1>
            <p class="text-purple-800 mt-2">Register for your examination</p>
        </div>
        
        <?php if ($registrationSuccess): ?>
        <!-- Success Message -->
        <div class="glass rounded-b-xl p-6 shadow-lg">
            <div class="bg-green-100 border-l-4 border-green-500 p-4 mb-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-green-700 font-medium"><?php echo $registrationMessage; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white bg-opacity-50 rounded-xl p-6 mb-6">
                <h2 class="text-2xl font-semibold text-purple-800 mb-4">Registration Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-purple-900 font-medium">Name: <span class="font-bold"><?php echo $_SESSION['student_name']; ?></span></p>
                        <p class="text-purple-900 font-medium mt-2">Student ID: <span class="font-bold"><?php echo $_SESSION['student_id']; ?></span></p>
                    </div>
                    <div>
                        <p class="text-purple-900 font-medium">Exam Date: <span class="font-bold"><?php echo date('F j, Y', strtotime($_SESSION['exam_date'])); ?></span></p>
                        <p class="text-purple-900 font-medium mt-2">Exam Time: <span class="font-bold"><?php echo $_SESSION['exam_time']; ?></span></p>
                    </div>
                </div>
                
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mt-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-yellow-700">
                                Important: Please save your Student ID. You'll need it to take the exam.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-center mt-8">
                <a 
                    href="exam.php" 
                    class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-8 rounded-lg transition-colors mr-4"
                >
                    Go to Exam
                </a>
                <a 
                    href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" 
                    class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-8 rounded-lg transition-colors"
                >
                    Register Another Student
                </a>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Registration Form -->
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="glass rounded-b-xl p-6 shadow-lg">
            
            <?php if (!empty($registrationMessage)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-red-700"><?php echo $registrationMessage; ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Personal Information -->
            <div class="bg-white bg-opacity-50 rounded-xl p-6 mb-6">
                <h2 class="text-2xl font-semibold text-purple-800 mb-4">Personal Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="block text-purple-900 font-medium mb-2">First Name</label>
                        <input 
                            type="text" 
                            id="first_name" 
                            name="first_name" 
                            class="w-full p-2 border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            required
                        >
                    </div>
                    <div>
                        <label for="last_name" class="block text-purple-900 font-medium mb-2">Last Name</label>
                        <input 
                            type="text" 
                            id="last_name" 
                            name="last_name" 
                            class="w-full p-2 border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            required
                        >
                    </div>
                    <div>
                        <label for="date_of_birth" class="block text-purple-900 font-medium mb-2">Date of Birth</label>
                        <input 
                            type="date" 
                            id="date_of_birth" 
                            name="date_of_birth" 
                            class="w-full p-2 border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            required
                        >
                    </div>
                    <div>
                        <label for="gender" class="block text-purple-900 font-medium mb-2">Gender</label>
                        <select 
                            id="gender" 
                            name="gender" 
                            class="w-full p-2 border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            required
                        >
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Contact Information -->
            <div class="bg-white bg-opacity-50 rounded-xl p-6 mb-6">
                <h2 class="text-2xl font-semibold text-purple-800 mb-4">Contact Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="email" class="block text-purple-900 font-medium mb-2">Email Address</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="w-full p-2 border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            required
                        >
                    </div>
                    <div class="md:col-span-2">
                        <label for="address" class="block text-purple-900 font-medium mb-2">Address</label>
                        <textarea 
                            id="address" 
                            name="address" 
                            rows="3" 
                            class="w-full p-2 border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            required
                        ></textarea>
                    </div>
                    <div>
                        <label for="phone" class="block text-purple-900 font-medium mb-2">Phone Number</label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            class="w-full p-2 border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            required
                        >
                    </div>
                </div>
            </div>
            
            <!-- Exam Scheduling -->
            <div class="bg-white bg-opacity-50 rounded-xl p-6 mb-6">
                <h2 class="text-2xl font-semibold text-purple-800 mb-4">Exam Schedule</h2>
                <p class="text-purple-800 mb-4">Please select your preferred date and time for the examination:</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="exam_date" class="block text-purple-900 font-medium mb-2">Exam Date</label>
                        <select 
                            id="exam_date" 
                            name="exam_date" 
                            class="w-full p-2 border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            required
                        >
                            <option value="">Select Date</option>
                            <?php 
                            $uniqueDates = [];
                            foreach ($examSlots as $slot) {
                                if (!in_array($slot['date'], $uniqueDates)) {
                                    $uniqueDates[] = $slot['date'];
                                    echo '<option value="' . $slot['date'] . '">' . date('l, F j, Y', strtotime($slot['date'])) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label for="exam_time" class="block text-purple-900 font-medium mb-2">Exam Time</label>
                        <select 
                            id="exam_time" 
                            name="exam_time" 
                            class="w-full p-2 border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            required
                        >
                            <option value="">Select Time</option>
                            <?php 
                            $uniqueTimes = [];
                            foreach ($examSlots as $slot) {
                                if (!in_array($slot['time'], $uniqueTimes)) {
                                    $uniqueTimes[] = $slot['time'];
                                    echo '<option value="' . $slot['time'] . '">' . $slot['time'] . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Terms & Conditions -->
            <div class="bg-white bg-opacity-50 rounded-xl p-6 mb-6">
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input 
                            id="terms" 
                            name="terms" 
                            type="checkbox" 
                            class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded"
                            required
                        >
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="terms" class="font-medium text-purple-800">I agree to the terms and conditions</label>
                        <p class="text-purple-700">By registering, you confirm that all information provided is accurate and you agree to abide by the examination rules.</p>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-center mt-6">
                <button 
                    type="submit" 
                    name="register" 
                    class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-8 rounded-lg transition-colors"
                >
                    Register for Exam
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <script>
        // Simple validation script
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            
            if (form) {
                form.addEventListener('submit', function(e) {
                    const email = document.getElementById('email');
                    const phone = document.getElementById('phone');
                    
                    // Basic email validation
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (email && !emailPattern.test(email.value)) {
                        alert('Please enter a valid email address');
                        e.preventDefault();
                        return;
                    }
                    
                    // Basic phone validation
                    const phonePattern = /^[0-9]{9,15}$/;
                    if (phone && !phonePattern.test(phone.value)) {
                        alert('Please enter a valid phone number (9-15 digits)');
                        e.preventDefault();
                        return;
                    }
                });
            }
        });
    </script>
</body>
</html>

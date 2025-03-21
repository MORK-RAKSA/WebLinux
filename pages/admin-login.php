<?php
session_start();
require_once '../config/db_connect.php';

$error = '';
$debug_mode = true; // Set to false in production

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } else {
        // Check if admin exists - case insensitive username comparison
        $stmt = $conn->prepare("SELECT * FROM admins WHERE LOWER(username) = LOWER(?)");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            
            // Check if password is hashed
            $is_hashed = password_get_info($admin['password'])['algo'] !== 0;
            
            // Verify password (supports both hashed and plain text passwords)
            if (($is_hashed && password_verify($password, $admin['password'])) || 
                (!$is_hashed && $password === $admin['password'])) {
                
                // Set session variables
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['full_name'];
                
                // If password wasn't hashed, update it now
                if (!$is_hashed) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $updatePwdStmt = $conn->prepare("UPDATE admins SET password = ? WHERE admin_id = ?");
                    $updatePwdStmt->bind_param("si", $hashed_password, $admin['admin_id']);
                    $updatePwdStmt->execute();
                    $updatePwdStmt->close();
                }
                
                // Update last login time
                $updateStmt = $conn->prepare("UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE admin_id = ?");
                $updateStmt->bind_param("i", $admin['admin_id']);
                $updateStmt->execute();
                $updateStmt->close();
                
                // Redirect to admin dashboard
                header("Location: admin/dashboard.php");
                exit;
            } else {
                $error = "Invalid username or password";
                if ($debug_mode) {
                    error_log("Password verification failed for user: $username");
                }
            }
        } else {
            $error = "Invalid username or password";
            if ($debug_mode) {
                error_log("No admin found with username: $username");
            }
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - RUPP Examination System</title>
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
<body class="min-h-screen bg-gradient-to-br from-purple-100 to-sky-100 flex items-center justify-center">
    <!-- Dot pattern -->
    <div class="dot-pattern"></div>
    
    <div class="glass rounded-xl p-8 shadow-lg w-full max-w-md z-10">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-purple-900">Admin Login</h2>
            <p class="mt-2 text-purple-700">Access the exam management system</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                <p><?php echo $error; ?></p>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-6">
                <label for="username" class="block text-purple-900 font-medium mb-2">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="w-full p-3 border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                    placeholder="Enter your username"
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    required
                >
            </div>
            
            <div class="mb-6">
                <label for="password" class="block text-purple-900 font-medium mb-2">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="w-full p-3 border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                    placeholder="Enter your password"
                    required
                >
            </div>
            
            <div class="mb-6">
                <button 
                    type="submit" 
                    class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-4 rounded-lg transition-colors"
                >
                    Login
                </button>
            </div>
            
            <div class="text-center">
                <a href="../index.html" class="text-purple-600 hover:text-purple-800 font-medium">Back to Homepage</a>
            </div>
        </form>
    </div>
</body>
</html>
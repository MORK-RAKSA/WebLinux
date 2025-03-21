<?php
session_start();
require_once '../config/db_connect.php';

// Check if admin is logged in
// if (!isset($_SESSION['admin_id'])) {
//     header("Location: ../pages/admin-login.php");
//     exit;
// }

// Get admin info
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];

// Get statistics
// Total exams
$stmt = $conn->prepare("SELECT COUNT(*) as total_exams FROM exams WHERE created_by = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$total_exams = $result->fetch_assoc()['total_exams'];
$stmt->close();

// Total students
$stmt = $conn->prepare("SELECT COUNT(*) as total_students FROM students");
$stmt->execute();
$result = $stmt->get_result();
$total_students = $result->fetch_assoc()['total_students'];
$stmt->close();

// Total results
$stmt = $conn->prepare("SELECT COUNT(*) as total_results FROM results");
$stmt->execute();
$result = $stmt->get_result();
$total_results = $result->fetch_assoc()['total_results'];
$stmt->close();

// Recent exams
$stmt = $conn->prepare("
    SELECT e.exam_id, e.title, e.created_at, 
           COUNT(DISTINCT r.student_id) as student_count
    FROM exams e
    LEFT JOIN results r ON e.exam_id = r.exam_id
    WHERE e.created_by = ?
    GROUP BY e.exam_id
    ORDER BY e.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$recent_exams = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - RUPP Examination System</title>
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
        
        .stats-card {
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
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
                        <a href="dashboard.php" class="sidebar-link flex items-center px-6 py-3 text-purple-900 bg-purple-100 border-r-4 border-purple-600">
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
                        <a href="create-exam.php" class="sidebar-link flex items-center px-6 py-3 text-gray-700 hover:text-purple-900">
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
            <div class="flex justify-between items-center mb-10">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
                    <p class="text-gray-600">Welcome back, <?php echo htmlspecialchars($admin_name); ?>!</p>
                </div>
                <div class="flex items-center">
                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full mr-2">
                        Online
                    </span>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin_name); ?>&background=random" alt="Admin" class="w-10 h-10 rounded-full">
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <div class="stats-card glass p-6 rounded-xl shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                            <i class="fas fa-file-alt text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Exams</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_exams; ?></h3>
                        </div>
                    </div>
                </div>
                
                <div class="stats-card glass p-6 rounded-xl shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                            <i class="fas fa-user-graduate text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Students</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_students; ?></h3>
                        </div>
                    </div>
                </div>
                
                <div class="stats-card glass p-6 rounded-xl shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                            <i class="fas fa-chart-bar text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Results</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_results; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Exams -->
            <div class="glass rounded-xl shadow-md p-6 mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Recent Exams</h2>
                    <a href="exams.php" class="text-sm text-purple-600 hover:text-purple-800">View All</a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                            <tr>
                                <th class="px-6 py-3 rounded-tl-lg">Exam Title</th>
                                <th class="px-6 py-3">Created Date</th>
                                <th class="px-6 py-3">Students</th>
                                <th class="px-6 py-3 rounded-tr-lg">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_exams->num_rows > 0): ?>
                                <?php while ($exam = $recent_exams->fetch_assoc()): ?>
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-6 py-4 font-medium text-gray-900">
                                            <?php echo htmlspecialchars($exam['title']); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php echo date('M d, Y', strtotime($exam['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php echo $exam['student_count']; ?> students
                                        </td>
                                        <td class="px-6 py-4">
                                            <a href="view-exam.php?id=<?php echo $exam['exam_id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit-exam.php?id=<?php echo $exam['exam_id']; ?>" class="text-green-600 hover:text-green-900 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete-exam.php?id=<?php echo $exam['exam_id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this exam?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr class="bg-white border-b">
                                    <td colspan="4" class="px-6 py-4 text-center">No exams found. <a href="create-exam.php" class="text-purple-600 hover:text-purple-800">Create one now</a>.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="glass rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Quick Actions</h2>
                    <div class="grid grid-cols-2 gap-3">
                        <a href="create-exam.php" class="bg-purple-600 hover:bg-purple-700 text-white rounded-lg p-4 flex flex-col items-center justify-center text-center transition-colors">
                            <i class="fas fa-plus-circle text-2xl mb-2"></i>
                            <span>Create New Exam</span>
                        </a>
                        <a href="schedule.php" class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg p-4 flex flex-col items-center justify-center text-center transition-colors">
                            <i class="fas fa-calendar-alt text-2xl mb-2"></i>
                            <span>Schedule Exam</span>
                        </a>
                        <a href="students.php" class="bg-green-600 hover:bg-green-700 text-white rounded-lg p-4 flex flex-col items-center justify-center text-center transition-colors">
                            <i class="fas fa-user-graduate text-2xl mb-2"></i>
                            <span>Manage Students</span>
                        </a>
                        <a href="results.php" class="bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg p-4 flex flex-col items-center justify-center text-center transition-colors">
                            <i class="fas fa-chart-bar text-2xl mb-2"></i>
                            <span>View Results</span>
                        </a>
                    </div>
                </div>
                
                <div class="glass rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">System Status</h2>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-700">Database</span>
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Online</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-700">Exam System</span>
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Online</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-700">Student Portal</span>
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Online</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-700">Last System Update</span>
                            <span class="text-gray-600 text-sm">March 19, 2025</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    // Remove active class from all links
                    sidebarLinks.forEach(item => {
                        item.classList.remove('bg-purple-100', 'border-r-4', 'border-purple-600', 'text-purple-900');
                        item.classList.add('text-gray-700');
                    });
                    
                    // Add active class to clicked link
                    this.classList.add('bg-purple-100', 'border-r-4', 'border-purple-600', 'text-purple-900');
                    this.classList.remove('text-gray-700');
                });
            });
        });
    </script>
</body>
</html>

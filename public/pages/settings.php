<?php
// Include Database Configuration
require_once __DIR__ . '/../../config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Update Profile Information
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email format!";
        } else {
            // Check if email already exists for another user
            $check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_email->bind_param("si", $email, $user_id);
            $check_email->execute();
            $email_result = $check_email->get_result();
            
            if ($email_result->num_rows > 0) {
                $error_message = "Email already exists!";
            } else {
                $update_stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
                $update_stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);
                
                if ($update_stmt->execute()) {
                    $success_message = "Profile updated successfully!";
                    // Refresh user data
                    $stmt->execute();
                    $user = $stmt->get_result()->fetch_assoc();
                } else {
                    $error_message = "Failed to update profile!";
                }
            }
        }
    }
    
    // Update Password
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $error_message = "Current password is incorrect!";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match!";
        } elseif (strlen($new_password) < 8) {
            $error_message = "Password must be at least 8 characters long!";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_pass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_pass->bind_param("si", $hashed_password, $user_id);
            
            if ($update_pass->execute()) {
                $success_message = "Password updated successfully!";
            } else {
                $error_message = "Failed to update password!";
            }
        }
    }
}
?>

<div class="px-8 py-2">
    
    <!-- Success/Error Messages -->
    <?php if ($success_message): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 animate-fade-in">
        <div class="flex items-center gap-3">
            <i class='bx bx-check-circle text-2xl'></i>
            <p class="font-semibold"><?= htmlspecialchars($success_message) ?></p>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 animate-fade-in">
        <div class="flex items-center gap-3">
            <i class='bx bx-error-circle text-2xl'></i>
            <p class="font-semibold"><?= htmlspecialchars($error_message) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Profile Section -->
    <form action="" method="POST" id="profileForm">
        <input type="hidden" name="update_profile" value="1">
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-6 transition-all duration-300 hover:shadow-xl">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b-2 border-gray-200">
                <div class="bg-[#092363] p-3 rounded-xl">
                    <i class='bx bxs-user text-2xl text-white'></i>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-800">Profile Settings</h3>
                    <p class="text-sm text-gray-500">Update your personal information</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="relative">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class='bx bx-user mr-1'></i> Username
                    </label>
                    <input type="text" 
                           value="<?= htmlspecialchars($user['username']) ?>"
                           disabled
                           class="w-full bg-gray-100 border-2 border-gray-200 rounded-xl px-4 py-3 text-gray-500 cursor-not-allowed">
                    <p class="text-xs text-gray-500 mt-1">Username cannot be changed</p>
                </div>
                
                <div class="relative">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class='bx bx-user-circle mr-1'></i> Full Name
                    </label>
                    <input type="text" 
                           name="full_name"
                           value="<?= htmlspecialchars($user['full_name'] ?? '') ?>"
                           placeholder="Enter your full name" 
                           required
                           class="w-full bg-gray-50 border-2 border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-[#092363] focus:bg-white transition-all duration-200">
                </div>
                
                <div class="relative">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class='bx bx-envelope mr-1'></i> Email Address
                    </label>
                    <input type="email" 
                           name="email"
                           value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                           placeholder="Enter your email" 
                           required
                           class="w-full bg-gray-50 border-2 border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-[#092363] focus:bg-white transition-all duration-200">
                </div>
                
                <div class="relative">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class='bx bx-phone mr-1'></i> Phone Number
                    </label>
                    <input type="tel" 
                           name="phone"
                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                           placeholder="Enter your phone number" 
                           class="w-full bg-gray-50 border-2 border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-[#092363] focus:bg-white transition-all duration-200">
                </div>
                
                <div class="relative">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class='bx bx-briefcase mr-1'></i> Role
                    </label>
                    <input type="text" 
                           value="<?= ucfirst($user['role']) ?>"
                           disabled
                           class="w-full bg-gray-100 border-2 border-gray-200 rounded-xl px-4 py-3 text-gray-500 cursor-not-allowed">
                </div>
                
                <div class="relative">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class='bx bx-time mr-1'></i> Member Since
                    </label>
                    <input type="text" 
                           value="<?= date('F d, Y', strtotime($user['created_at'])) ?>"
                           disabled
                           class="w-full bg-gray-100 border-2 border-gray-200 rounded-xl px-4 py-3 text-gray-500 cursor-not-allowed">
                </div>
            </div>
            
            <div class="flex justify-end mt-6">
                <button type="submit" 
                        class="px-8 py-3 bg-[#092363] text-white font-semibold rounded-xl hover:bg-[#e6b949] hover:text-[#092363] transition-all duration-300 hover:shadow-lg hover:scale-105">
                    <i class='bx bx-save mr-2'></i>
                    Update Profile
                </button>
            </div>
        </div>
    </form>

    <!-- Security Section -->
    <form action="" method="POST" id="passwordForm">
        <input type="hidden" name="update_password" value="1">
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-6 transition-all duration-300 hover:shadow-xl">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b-2 border-gray-200">
                <div class="bg-red-500 p-3 rounded-xl">
                    <i class='bx bxs-lock-alt text-2xl text-white'></i>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-800">Security Settings</h3>
                    <p class="text-sm text-gray-500">Keep your account secure</p>
                </div>
            </div>
            
            <div class="space-y-4">
                <div class="relative">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class='bx bx-lock mr-1'></i> Current Password
                    </label>
                    <input type="password" 
                           name="current_password"
                           placeholder="Enter current password"
                           required
                           class="w-full bg-gray-50 border-2 border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-red-500 focus:bg-white transition-all duration-200">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="relative">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class='bx bx-key mr-1'></i> New Password
                        </label>
                        <input type="password" 
                               name="new_password"
                               placeholder="Enter new password"
                               required
                               minlength="8"
                               class="w-full bg-gray-50 border-2 border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-red-500 focus:bg-white transition-all duration-200">
                    </div>
                    
                    <div class="relative">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class='bx bx-check-shield mr-1'></i> Confirm Password
                        </label>
                        <input type="password" 
                               name="confirm_password"
                               placeholder="Confirm new password"
                               required
                               minlength="8"
                               class="w-full bg-gray-50 border-2 border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-red-500 focus:bg-white transition-all duration-200">
                    </div>
                </div>
                
                <div class="bg-amber-100 border-l-4 border-yellow-500 p-4 rounded-lg">
                    <div class="flex items-start gap-3">
                        <i class='bx bx-info-circle text-yellow-600 text-xl mt-0.5'></i>
                        <div>
                            <p class="text-sm font-semibold text-yellow-800">Password Requirements:</p>
                            <ul class="text-xs text-yellow-700 mt-1 space-y-1">
                                <li>• At least 8 characters long</li>
                                <li>• Include uppercase and lowercase letters (recommended)</li>
                                <li>• Include at least one number (recommended)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end mt-6">
                <button type="submit" 
                        class="px-8 py-3 bg-red-500 text-white font-semibold rounded-xl hover:bg-red-600 transition-all duration-300 hover:shadow-lg hover:scale-105">
                    <i class='bx bx-key mr-2'></i>
                    Change Password
                </button>
            </div>
        </div>
    </form>

    <!-- Account Information -->
    <div class="bg-white rounded-2xl shadow-lg p-8 mb-6 transition-all duration-300 hover:shadow-xl">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b-2 border-gray-200">
            <div class="bg-blue-500 p-3 rounded-xl">
                <i class='bx bxs-info-circle text-2xl text-white'></i>
            </div>
            <div>
                <h3 class="text-2xl font-bold text-gray-800">Account Information</h3>
                <p class="text-sm text-gray-500">Your account details and status</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-xl border-2 border-blue-200">
                <div class="flex items-center gap-3 mb-2">
                    <i class='bx bx-shield-quarter text-3xl text-blue-600'></i>
                    <h4 class="font-bold text-gray-800">Account Status</h4>
                </div>
                <p class="text-2xl font-bold <?= $user['status'] === 'active' ? 'text-green-600' : 'text-red-600' ?>">
                    <?= ucfirst($user['status']) ?>
                </p>
            </div>
            
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-xl border-2 border-purple-200">
                <div class="flex items-center gap-3 mb-2">
                    <i class='bx bx-id-card text-3xl text-purple-600'></i>
                    <h4 class="font-bold text-gray-800">User ID</h4>
                </div>
                <p class="text-2xl font-bold text-purple-600">#<?= str_pad($user['id'], 5, '0', STR_PAD_LEFT) ?></p>
            </div>
            
            <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-xl border-2 border-green-200">
                <div class="flex items-center gap-3 mb-2">
                    <i class='bx bx-calendar text-3xl text-green-600'></i>
                    <h4 class="font-bold text-gray-800">Last Updated</h4>
                </div>
                <p class="text-sm font-semibold text-green-600">
                    <?= date('M d, Y H:i', strtotime($user['created_at'])) ?>
                </p>
            </div>
        </div>
    </div>

    <!-- System Preferences -->
    <div class="bg-white rounded-2xl shadow-lg p-8 mb-6 transition-all duration-300 hover:shadow-xl">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b-2 border-gray-200">
            <div class="bg-[#e6b949] p-3 rounded-xl">
                <i class='bx bxs-cog text-2xl text-[#092363]'></i>
            </div>
            <div>
                <h3 class="text-2xl font-bold text-gray-800">System Preferences</h3>
                <p class="text-sm text-gray-500">Customize your experience</p>
            </div>
        </div>
        
        <div class="space-y-4">
            <div class="p-4 bg-gray-50 rounded-xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class='bx bx-bell text-2xl text-gray-600'></i>
                        <div>
                            <p class="font-semibold text-gray-800">Email Notifications</p>
                            <p class="text-xs text-gray-500">Receive system updates via email</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#092363]"></div>
                    </label>
                </div>
            </div>
            
            <div class="p-4 bg-gray-50 rounded-xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class='bx bx-package text-2xl text-gray-600'></i>
                        <div>
                            <p class="font-semibold text-gray-800">Low Stock Alerts</p>
                            <p class="text-xs text-gray-500">Get notified when inventory is low</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#092363]"></div>
                    </label>
                </div>
            </div>
            
            <div class="p-4 bg-gray-50 rounded-xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class='bx bx-moon text-2xl text-gray-600'></i>
                        <div>
                            <p class="font-semibold text-gray-800">Dark Mode</p>
                            <p class="text-xs text-gray-500">Switch to dark theme (Coming Soon)</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-not-allowed opacity-50">
                        <input type="checkbox" class="sr-only peer" disabled>
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                    </label>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}
</style>

<script>
// Password match validation
document.getElementById('passwordForm')?.addEventListener('submit', function(e) {
    const newPassword = document.querySelector('input[name="new_password"]').value;
    const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('New passwords do not match!');
    }
});

// Auto-hide messages after 5 seconds
setTimeout(() => {
    const messages = document.querySelectorAll('.animate-fade-in');
    messages.forEach(msg => {
        msg.style.transition = 'opacity 0.5s';
        msg.style.opacity = '0';
        setTimeout(() => msg.remove(), 500);
    });
}, 5000);
</script>
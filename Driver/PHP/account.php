<?php
    session_start();
    if(!isset($_SESSION['userID'])){
        header("Location: ../../login.php");
        exit();
    }
    
    include '../../database.php';
    
    $success = "";
    $error = "";
    $fullName = $email = $phone = $userType = "";
    $userID = $_SESSION['userID'];
    
    $sql = "SELECT * FROM users WHERE user_id = '$userID'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $userData = $result->fetch_assoc();
        $fullName = $userData['full_name'];
        $email = $userData['email'];
        $phone = $userData['phone'];
        $userType = $userData['userType'];
    }
    
    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $newFullName = $_POST["fullName"];
        $newEmail = $_POST["email"];
        $newPhone = $_POST["phone"];
        $currentPassword = $_POST["currentPassword"];
        $newPassword = $_POST["newPassword"];
        
        if(empty($newFullName) || empty($newEmail) || empty($newPhone)) {
            $error = "Please fill all required fields";
        } else {
            // Check if password is being updated
            $passwordUpdate = "";
            if(!empty($currentPassword) && !empty($newPassword)) {
                // Verify current password
                if(password_verify($currentPassword, $userData['password'])) {
                    $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $passwordUpdate = ", password = '$hashedNewPassword'";
                } else {
                    $error = "Current password is incorrect";
                }
            }
            
            if(empty($error)) {
                // Update user data in database
                $updateSql = "UPDATE users SET 
                            full_name = '$newFullName', 
                            email = '$newEmail', 
                            phone = '$newPhone'"
                            . $passwordUpdate
                            . $profilePicUpdate
                            . ", updated_at = NOW()
                            WHERE user_id = '$userID'";

                
                if($conn->query($updateSql) === TRUE) {
                    $success = "Profile updated successfully!";
                    // Update session variables
                    $_SESSION['fullName'] = $newFullName;
                    
                    // Refresh user data
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        $userData = $result->fetch_assoc();
                    }
                } else {
                    $error = "Error updating profile: " . $conn->error;
                }
            }
        }
    }
    //account deletion
if(isset($_POST['deleteAccount'])){
    $deleteSql = "DELETE FROM users WHERE user_id = '$userID'";
    if($conn->query($deleteSql) === TRUE){
        session_destroy();
        header("Location: ../../login.php?deleted=1");
        exit();
    } else {
        $error = "Error deleting account: " . $conn->error;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account</title>
    <link rel="icon" type="image/png" href="../../Asset/icons/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../global.css">
    <link rel="stylesheet" href="../CSS/account.css">
    <link rel="stylesheet" href="../../darkmode.css">
    <script src="../JS/account.js" defer></script>
</head>
<body>
    <div class="home-container">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="user-profile">
                <div class="user-info">
                    <div class="user-image">
                        <img src="../../Asset/icons/person.png" alt="User Image" class="user-image">
                    </div>
                    <div class="user-details">
                        <h2 class="user-name"><?= htmlspecialchars($_SESSION['fullName']); ?></h2>
                        <p class="user-id"><?= htmlspecialchars($_SESSION['userID']); ?></p>
                    </div>
                </div>
            </div>
            <ul>
                <li><a href="postride.php"><i class="fas fa-plus-circle"></i><span>Post Ride</span></a></li>
                <li><a href="myrides.php"><i class="fas fa-car"></i><span>My Rides</span></a></li>
                <li><a href="account.php" class="active"><i class="fas fa-user"></i><span>Account</span></a></li>
                <li><a href="../../logout.php" id="svg-logout"><i class="fas fa-sign-out-alt"></i><span>Log out</span></a></li>
            </ul>
        </nav>  
    </div>
    <main>
        <div class="account-container">
            <div class="account-header">
                <h1>Account Settings</h1>
            </div>
            
            <div class="account-content">
                <?php if(!empty($success)): ?>
                    <div class="success-message <?php echo !empty($success) ? 'show' : ''; ?>">
                         <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if(!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="profile-section">
                    <div class="profile-image-container">  
                        <img src="../../Asset/icons/person.png" alt="Profile Picture" class="profile-image" id="profileImagePreview">
                    </div>
                    <div class="profile-info">
                        <h2 class="profile-name"><?php echo htmlspecialchars($fullName); ?></h2>
                        <p class="profile-role"><?php echo htmlspecialchars($userType); ?></p>
                        <p class="profile-id">ID: <?php echo htmlspecialchars($userID); ?></p>
                    </div>
                </div>
                
                <h2 class="section-title">Account Information</h2>
                <div class="info-grid">
                    <div class="info-group">
                        <label>User ID</label>
                        <div class="info-value"><?php echo htmlspecialchars($userID); ?></div>
                    </div>
                    
                    <div class="info-group">
                        <label>Account Type</label>
                        <div class="info-value"><?php echo htmlspecialchars(ucfirst($userType)); ?></div>
                    </div>
                    
                    <div class="info-group">
                        <label>Member Since</label>
                        <div class="info-value"><?php echo isset($userData['created_at']) ? date('F j, Y', strtotime($userData['created_at'])) : 'N/A'; ?></div>
                    </div>
                    
                    <div class="info-group">
                        <label>Last Updated</label>
                        <div class="info-value"><?php echo isset($userData['updated_at']) ? date('F j, Y', strtotime($userData['updated_at'])) : 'N/A'; ?></div>
                    </div>
                </div>
                
                <h2 class="section-title">Edit Profile</h2>
                <form method="POST" enctype="multipart/form-data" id="accountForm">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="fullName">Full Name</label>
                            <div class="input-with-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" name="fullName" id="fullName" value="<?php echo htmlspecialchars($fullName); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <div class="input-with-icon">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <div class="input-with-icon">
                                <i class="fas fa-phone"></i>
                                <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="currentPassword">Current Password (to change password)</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="currentPassword" id="currentPassword" placeholder="Enter current password">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="newPassword" id="newPassword" placeholder="Enter new password">
                            </div>
                        </div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" name="deleteAccount" class="btn btn-danger" onclick="return confirmDelete()">
                            Delete Account
                        </button>
                        <button type="reset" class="btn btn-outline">Reset Changes</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>

                </form>
            </div>
        </div>
    </main>
</body>
</html>
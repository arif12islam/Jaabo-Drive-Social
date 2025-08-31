<?php
include "database.php";
if($_SERVER["REQUEST_METHOD"]=="POST"){
    $userId = $_POST["userId"];
    $password = $_POST["password"];
    $fullName = $_POST["fullName"];
    $email = $_POST["email"];
    $userType=$_POST["userType"];
    $phone=$_POST["phone"];
    $hash_pass= password_hash($password, PASSWORD_DEFAULT) ;

    if(empty($userId)||empty($password)||empty($fullName)||empty($email)||empty($userType)||empty($phone)){
        $error="fill all the form";
    }
    else
    {
        $sql="INSERT INTO users (user_id,full_name,email,phone,password,userType) 
        VALUES ('$userId', '$fullName', '$email', '$phone', '$hash_pass', '$userType')";

        if($conn ->query($sql)==TRUE){
            header("Location: login.php");
            exit();
        }
        else{
            echo"404 error";
        }
    }

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jaabo – Drive Social</title>
    <link rel="icon" type="image/png" href="./Asset/icons/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="global.css">
    <link rel="stylesheet" href="signup.css">
    <script src="signup.js" defer></script>

</head>
<body>
    <div class="signup-form-container">
        <div class="form-header">
            <h2>Create Your Account</h2>
            <p>Join thousands of users who trust Jaabo</p>
        </div>
        
        <form class="signup-form" id="signupForm" method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label for="fullName">Full Name</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" name="fullName" id="fullName" placeholder="Full name">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="id">User ID</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" name="userId" id="id" placeholder="Enter your ID">
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label for="email">Email Address</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" id="email" placeholder="Enter your email">
                    </div>
                </div>
                
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="Create password">
                    </div>
                    <div class="error-message" id="passwordError">Password must be at least 8 characters</div>
                </div>
                
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirmPassword" id="confirmPassword" placeholder="Confirm password">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="userType">I am a</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user-tag"></i>
                        <select id="userType" name="userType" >
                            <option value="">Select role</option>
                            <option value="rider">Rider</option>
                            <option value="driver">Driver</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <div class="input-with-icon">
                        <i class="fas fa-phone"></i>
                        <input type="tel" name="phone" id="phone" placeholder="Your phone number">
                    </div>
                </div>
                
            </div>
            <div class="input-error" id="loginError"><?php if(isset($error)) echo $error; ?></div>
            <button type="submit" class="signup-btn" id="signup-btn">Create Account</button>
        </form>
        
        
        <div class="login-link">
            Already have an account? <a href="login.php">Log in</a>
        </div>
    </div>
</body>
</html>
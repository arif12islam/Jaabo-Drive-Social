<?php
include "database.php";
session_start();

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $userId = $_POST["userId"];
    $password = $_POST["password"];
    $remember = isset($_POST["remember"]);

    if(empty($userId) || empty($password)){
        $error= "Please fill all fields.";
    } else {
        // Simple query to find the user
        $sql = "SELECT * FROM users WHERE user_id='$userId'";
        $result = $conn->query($sql);

        if($result->num_rows == 1){
            $user = $result->fetch_assoc();

            // Verify password
            if(password_verify($password, $user['password'])){
                $_SESSION["userID"] = $user["user_id"];
                $_SESSION["fullName"] = $user["full_name"];
                $_SESSION["userType"] = $user["userType"];
                
                if($_SESSION["userType"]=="rider"){
                    header("Location: Rider/PHP/home.php");
                }
                else{
                    header("Location: Driver/PHP/postride.php");
                }

                if($remember){
                    setcookie("userId", $userId, time() + (86400*30), "/");
                    setcookie("password", $password, time() + (86400*30), "/");
                }
            }
        }
        $error= "invalid User ID or Password!";
    }
    
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jaabo - Drive Social</title>
    <link rel="icon" type="image/png" href="./Asset/icons/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="global.css">
    <link rel="stylesheet" href="darkmode.css">
    <script src="login.js" defer></script>
</head>
<body>
    <div class="login-form-container">
        <div class="form-header">
            <h2>Login to Your Account</h2>
            <p>Enter your credentials to access your account</p>
        </div>
        
        <form class="login-form" id="loginForm" method="POST">
            <div class="form-group">
                <label for="userId">User ID</label>
                <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="text" name="userId" id="userId" placeholder="Enter your ID" value="<?php echo isset($_COOKIE['userId']) ? $_COOKIE['userId'] : ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" placeholder="Enter your password" value="<?php echo isset($_COOKIE['password']) ? $_COOKIE['password'] : ''; ?>">
                </div>
            </div>
            
            <div class="remember-forgot">
                <div class="remember">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
            </div>
            <div class="input-error" id="loginError"><?php if(isset($error)) echo $error; ?></div>
            <button type="submit" class="login-btn" id="login-btn">Login</button>
        </form>
        
        
        <div class="signup-link">
            Don't have an account? <a href="signup.php">Sign up now</a>
        </div>
    </div>
</body>
</html>
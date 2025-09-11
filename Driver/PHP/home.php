<?php
    // Enable error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    session_start();
    
    // Check if session is working
    if(!isset($_SESSION['userID'])){
        header("Location: ../../login.php");
        exit();
    }
    
    // Database connection with error checking
    if (file_exists('../../database.php')) {
        require_once '../../database.php';
    } else {
        die("Error: database.php file not found at ../../database.php");
    }
    
    // Check database connection
    if (!isset($conn) || !$conn) {
        die("Database connection failed. Check your database.php file.");
    }
    
    // Test database connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_ride'])) {
        $origin = trim($_POST['origin']);
        $destination = trim($_POST['destination']);
        $departure_time = $_POST['departure_time'];
        $seats_available = (int)$_POST['seats_available'];
        $price = (float)$_POST['price'];
        $title = trim($_POST['title']);
        
        // Basic validation
        $errors = [];
        if (empty($origin)) $errors[] = "Origin is required";
        if (empty($destination)) $errors[] = "Destination is required";
        if (empty($departure_time)) $errors[] = "Departure time is required";
        if ($seats_available < 1) $errors[] = "At least one seat must be available";
        if ($price <= 0) $errors[] = "Price must be greater than 0";
        if (empty($title)) $errors[] = "Title is required";
        
        if (empty($errors)) {
            // Insert into database
            $user_id = $_SESSION['userID'];
            
            // Check if rides table exists and show its structure
            $table_check = $conn->query("SHOW TABLES LIKE 'rides'");
            if ($table_check->num_rows == 0) {
                die("Error: 'rides' table does not exist in database");
            }
            
            // Show rides table structure for debugging
            echo "Rides table columns:<br>";
            $columns_check = $conn->query("DESCRIBE rides");
            $available_columns = [];
            while ($column = $columns_check->fetch_assoc()) {
                $available_columns[] = $column['Field'];
                echo "- " . $column['Field'] . "<br>";
            }
            echo "<br>";
            
        
            
            // Prepare the INSERT statement based on available columns
            $stmt = $conn->prepare("INSERT INTO rides 
                    (user_id, origin, destination, departure_time, seats_available, price, title, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");

            $stmt->bind_param(
            "ssssids", 
            $user_id,         // s → string (your user_id is a string)
            $origin,          // s → string
            $destination,     // s → string
            $departure_time,  // s → string (datetime is passed as text)
            $seats_available, // i → integer
            $price,           // d → decimal/float
            $title            // s → string
        );
            
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }
            
            if ($stmt->execute()) {
                $success_message = "Ride posted successfully!";
                // Clear form data after successful submission
                $_POST = array();
            } else {
                $errors[] = "Error posting ride: " . $stmt->error;
            }
        }
    }
    
    // Check for success message from redirect
    if (isset($_GET['success']) && $_GET['success'] == 1) {
        $success_message = "Ride posted successfully!";
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jaabo - Post a Ride</title>
    <link rel="icon" type="image/png" href="../../Asset/icons/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../CSS/home.css">
    <link rel="stylesheet" href="../../global.css">
    <script src="../JS/postride.js" defer></script>
</head>
<body>
    <div class="home-container">
        <nav id="sidebar">
            <div class="user-profile">
                <div class="user-info">
                    <div class="user-image">
                        <img src="../../Asset/icons/person.png" alt="User Image" class="user-image">
                    </div>
                    <div class="user-details">
                        <h2 class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['fullName'] ?? 'User'); ?></h2>
                        <p class="user-id"><?php echo htmlspecialchars($_SESSION['userID'] ?? $_SESSION['user_id'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
            <ul>
                <li>
                    <a href="#" class="active">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/></svg>
                        <span>Home</span>
                    </a>
                </li>
                <li>
                    <a href="myrides.php">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M240-200v40q0 17-11.5 28.5T200-120h-40q-17 0-28.5-11.5T120-160v-320l84-240q6-18 21.5-29t34.5-11h440q19 0 34.5 11t21.5 29l84 240v320q0 17-11.5 28.5T800-120h-40q-17 0-28.5-11.5T720-160v-40H240Zm-8-360h496l-42-120H274l-42 120Zm-32 80v200-200Zm100 160q25 0 42.5-17.5T360-380q0-25-17.5-42.5T300-440q-25 0-42.5 17.5T240-380q0 25 17.5 42.5T300-320Zm360 0q25 0 42.5-17.5T720-380q0-25-17.5-42.5T660-440q-25 0-42.5 17.5T600-380q0 25 17.5 42.5T660-320Zm-460 40h560v-200H200v200Z"/></svg>
                        <span>My Rides</span>
                    </a>
                </li>
                <li>
                    <a href="account.php">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M480-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM160-160v-112q0-34 17.5-62.5T224-378q62-31 126-46.5T480-440q66 0 130 15.5T736-378q29 15 46.5 43.5T800-272v112H160Zm80-80h480v-32q0-11-5.5-20T700-306q-54-27-109-40.5T480-360q-56 0-111 13.5T260-306q-9 5-14.5 14t-5.5 20v32Zm240-320q33 0 56.5-23.5T560-640q0-33-23.5-56.5T480-720q-33 0-56.5 23.5T400-640q0 33 23.5 56.5T480-560Zm0-80Zm0 400Z"/></svg>
                        <span>Account</span>
                    </a>
                </li>
                <li>
                    <a href="../../logout.php" id="svg-logout" onclick="return showLogoutConfirmation()" style="cursor: pointer;">
                        <svg id="svg-logout" transform="scale(-1, 1)" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h280v80H200v560h280v80H200Zm440-160-55-58 102-102H360v-80h327L585-622l55-58 200 200-200 200Z"/></svg>
                        <span>Log out</span>
                    </a>
                </li>
            </ul>
        </nav>  
    </div>
    
    <main>
        <div class="main-content">
            <h1 class="page-title">Post a New Ride</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success_message)): ?>
                <div class="success">
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Ride Title</label>
                    <input type="text" id="title" name="title" placeholder="e.g., Gulshan to Airport" 
                           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="origin">From</label>
                        <input type="text" id="origin" name="origin" placeholder="Starting location" 
                               value="<?= htmlspecialchars($_POST['origin'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="destination">To</label>
                        <input type="text" id="destination" name="destination" placeholder="Destination" 
                               value="<?= htmlspecialchars($_POST['destination'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="departure_time">Departure Time</label>
                    <input type="datetime-local" id="departure_time" name="departure_time" 
                           value="<?= htmlspecialchars($_POST['departure_time'] ?? '') ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="seats_available">Seats Available</label>
                        <input type="number" id="seats_available" name="seats_available" min="1" max="10" 
                               value="<?= htmlspecialchars($_POST['seats_available'] ?? '1') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price (৳)</label>
                        <input type="number" id="price" name="price" min="1" step="0.01" placeholder="0.00" 
                               value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
                    </div>
                </div>
                
                <button type="submit" name="post_ride" class="form-submit">
                    <i class="fas fa-plus"></i> Post Ride
                </button>
            </form>
        </div>
    </main>
</body>
</html>
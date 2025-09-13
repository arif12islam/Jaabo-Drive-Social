<?php
    // Enable error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    session_start();
    
    if(!isset($_SESSION['userID'])){
        header("Location: ../../login.php");
        exit();
    }
    
    require_once '../../database.php';
    
    if (!isset($conn) || !$conn) {
        die("Database connection failed.");
    }
    
    $userID = $_SESSION['userID'];
    $errors = [];
    $success_message = '';

    // ---- NEW: Check if driver already has an active ride (for page load) ----
    $hasActiveRide = false;
    $check_stmt = $conn->prepare("SELECT ride_id FROM rides WHERE user_id = ? AND status IN ('active','booked') LIMIT 1");
    $check_stmt->bind_param("s", $userID);
    if ($check_stmt->execute()) {
        $result = $check_stmt->get_result();
        if ($result->num_rows > 0) {
            $hasActiveRide = true;
        }
    }
    $check_stmt->close();
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_ride'])) {

        // ---- NEW: Add check for active ride on form submission ----
        if ($hasActiveRide) {
            $errors[] = "You already have an active or booked ride posted. Please complete or delete it before posting a new one.";
        } else {
            $origin = trim($_POST['origin']);
            $destination = trim($_POST['destination']);
            $departure_time = $_POST['departure_time'];
            $seats_available = (int)$_POST['seats_available'];
            $price = (float)$_POST['price'];
            $title = trim($_POST['title']);
            
            // Basic validation
            if (empty($origin)) $errors[] = "Origin is required";
            if (empty($destination)) $errors[] = "Destination is required";
            if (empty($departure_time)) $errors[] = "Departure time is required";
            if ($seats_available < 1) $errors[] = "At least one seat must be available";
            if ($price <= 0) $errors[] = "Price must be greater than 0";
            if (empty($title)) $errors[] = "Title is required";
            
            if (empty($errors)) {
                $stmt = $conn->prepare("INSERT INTO rides 
                        (user_id, origin, destination, departure_time, seats_available, price, title, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");

                $stmt->bind_param(
                    "ssssids", 
                    $userID,
                    $origin,
                    $destination,
                    $departure_time,
                    $seats_available,
                    $price,
                    $title
                );
                
                if ($stmt->execute()) {
                    // Redirect to prevent form resubmission and show success
                    header("Location: postride.php?success=1");
                    exit();
                } else {
                    $errors[] = "Error posting ride: " . $stmt->error;
                }
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
    <link rel="stylesheet" href="../CSS/postride.css">
    <link rel="stylesheet" href="../../global.css">
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
                        <h2 class="user-name"><?= htmlspecialchars($_SESSION['fullName']); ?></h2>
                        <p class="user-id"><?= htmlspecialchars($_SESSION['userID']); ?></p>
                    </div>
                </div>
            </div>
            <ul>
                <li><a href="postride.php" class="active"><i class="fas fa-plus-circle"></i><span>Post Ride</span></a></li>
                <li><a href="myrides.php"><i class="fas fa-car"></i><span>My Rides</span></a></li>
                <li><a href="account.php"><i class="fas fa-user"></i><span>Account</span></a></li>
                <li><a href="../../logout.php" id="svg-logout"><i class="fas fa-sign-out-alt"></i><span>Log out</span></a></li>
            </ul>
        </nav>  
    </div>
    
    <main>
        <div class="main-content">
            <h1 class="page-title">Post a New Ride</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($hasActiveRide && empty($success_message)): ?>
                <div class="info-box">
                    <h2>You Already Have an Active/Booked Ride</h2>
                    <p>Please complete or delete your existing ride before posting a new one.</p>
                    <a href="myrides.php" class="form-submit" >
                        <i class="fas fa-car"></i> Manage My Rides
                    </a>
                </div>
            <?php elseif (empty($success_message)): ?>
                <form method="POST" action="postride.php">
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
                            <input type="number" id="price" name="price" min="1" step="1" placeholder="e.g. 150" 
                                   value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="post_ride" class="form-submit">
                        <i class="fas fa-plus"></i> Post Ride
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
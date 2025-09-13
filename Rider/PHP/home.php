<?php
session_start();
if(!isset($_SESSION['userID'])){
    header("Location: ../../login.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../../database.php"; 
$userID = $_SESSION['userID'];

//Handle Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_ride'])) {

    // ---- UPDATED: Check for any active or pending rides ----
    $check_active_ride_stmt = $conn->prepare("SELECT booking_id FROM bookings WHERE user_id = ? AND status IN ('booked', 'completed') LIMIT 1");
    $check_active_ride_stmt->bind_param("i", $userID);
    $check_active_ride_stmt->execute();
    $active_ride_result = $check_active_ride_stmt->get_result();

    if ($active_ride_result->num_rows > 0) {
        // User has an active ride, block the new booking
        $_SESSION['error'] = "You already have an active ride. Please complete your current journey before booking a new one.";
        header("Location: myrides.php"); // Redirect to where they can see their ride
        exit();
    }
    // ---- END UPDATED CHECK ----

    $ride_id = intval($_POST['ride_id']);
    $seats = 1;

    // Check if ride is still available
    $check = $conn->prepare("SELECT status FROM rides WHERE ride_id = ?");
    $check->bind_param("i", $ride_id);
    $check->execute();
    $check_result = $check->get_result();
    
    if ($check_result->num_rows > 0) {
        $ride = $check_result->fetch_assoc();
        
        if ($ride['status'] === 'active') {
            // Insert booking
            $stmt = $conn->prepare("INSERT INTO bookings (ride_id, user_id, seats_booked, status, booked_at) 
                                    VALUES (?, ?, ?, 'booked', NOW())");
            $stmt->bind_param("isi", $ride_id, $userID, $seats);
            
            if ($stmt->execute()) {
                // Update ride status
                $update = $conn->prepare("UPDATE rides SET status = 'booked' WHERE ride_id = ?");
                $update->bind_param("i", $ride_id);
                $update->execute();
                
                $_SESSION['success'] = "Ride booked successfully!";
            } else {
                $_SESSION['error'] = "Failed to book ride. Please try again.";
            }
        } else {
            $_SESSION['error'] = "This ride is no longer available.";
        }
    } else {
        $_SESSION['error'] = "Ride not found.";
    }
    
    // Refresh page
    header("Location: home.php");
    exit();
}

// ---- Fetch Rides ----
$searchTerm = isset($_GET['q']) ? strtolower(trim($_GET['q'])) : "";

$sql = "SELECT r.ride_id, r.title, r.origin, r.destination, r.departure_time, r.price, u.full_name, r.seats_available 
        FROM rides r
        JOIN users u ON r.user_id = u.user_id
        WHERE r.status = 'active'";

if ($searchTerm !== "") {
    $like = "%" . $conn->real_escape_string($searchTerm) . "%";
    $sql .= " AND (LOWER(r.title) LIKE '$like' OR LOWER(r.origin) LIKE '$like' OR LOWER(r.destination) LIKE '$like')";
}

$sql .= " ORDER BY r.departure_time ASC";

$result = $conn->query($sql);

if (!$result) {
    die("SQL Error: " . $conn->error);
}

$rides = [];
while ($row = $result->fetch_assoc()) {
    $rides[] = [
        "id"    => $row['ride_id'],
        "name"  => $row['full_name'],
        "title" => $row['title'],
        "from"  => $row['origin'],
        
        "to"    => $row['destination'],
        "price" => "৳" . number_format($row['price'], 0),
        "time"  => date("g:i A", strtotime($row['departure_time'])),
        "seats" => "Seats: " . number_format($row['seats_available'])
    ];
}
// ---- NEW: Check for pending payment to disable booking buttons ----
$hasActiveRide = false;
$check_active_ride_stmt = $conn->prepare("SELECT booking_id FROM bookings WHERE user_id = ? AND status IN ('booked', 'completed') LIMIT 1");
$check_active_ride_stmt->bind_param("i", $userID);
if ($check_active_ride_stmt->execute()) {
    $active_ride_result = $check_active_ride_stmt->get_result();
    if ($active_ride_result->num_rows > 0) {
        $hasActiveRide = true;
    }
}
$check_active_ride_stmt->close();
?>
?>
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jaabo - Drive Social</title>
    <link rel="icon" type="image/png" href="../../Asset/icons/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../CSS/home.css">
    <link rel="stylesheet" href="../../global.css">
    <script src="../JS/home.js" defer></script>
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
                <li><a href="home.php" class="active"><i class="fas fa-home"></i><span>Home</span></a></li>
                <li><a href="myrides.php"><i class="fas fa-car"></i><span>My Rides</span></a></li>
                <li><a href="account.php"><i class="fas fa-user"></i><span>Account</span></a></li>
                <li><a href="../../logout.php" id="svg-logout"><i class="fas fa-sign-out-alt"></i><span>Log out</span></a></li>
            </ul>
        </nav>  
    </div>
    <main>
        <form method="GET" class="search-bar">
            <div class="search-bg"></div>
            <input type="text" id="search-input" name="q" placeholder="Search for rides..." 
                   value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            <button id="search-btn" type="submit">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3">
                    <path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z"/>
                </svg>
            </button>
        </form>

        <?php if (!$hasActiveRide): ?>                       
            <div class="containers-wrapper" id="ridesContainer">
                <?php if (count($rides) > 0): ?>
                    <?php foreach ($rides as $ride): ?>
                    <div class="ride-card">
                        <div class="ride-image">
                            <div class="card-image">
                                <img src="../../Asset/icons/person.png" alt="User Image">
                            </div>
                            <h3 class="rider-name"><?= htmlspecialchars($ride['name']) ?></h3>
                        </div>
                    
                        <div class="ride-details">
                            <h3 class="ride-title"><?= htmlspecialchars($ride['title']) ?></h3>
                            <div class="ride-info">
                                <span><?= htmlspecialchars($ride['from']) ?> → <?= htmlspecialchars($ride['to']) ?></span>
                                <span><?= htmlspecialchars($ride['time']) ?></span>
                            </div>
                            <div class="ride-price-seats">
                                <span class="ride-price"><?= htmlspecialchars($ride['price']) ?></span>
                                <span class="ride-seats"><?= htmlspecialchars($ride['seats']) ?></span>
                            </div>

                            <div class="ride-btn">
                                <button class="ride-action" onclick="bookRide(<?= $ride['id'] ?>)">Book Now</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No rides found.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="no-rides">
                    <p>You have a pending payment for a completed ride or an active ride. Please complete your current ride or go to 'My Rides' to pay before booking a new one.</p>
                    <a href="myrides.php" class="ride-action" style="display: inline-block; width: auto; padding: 12px 20px; margin-top:15px;">
                        Go to My Rides
                    </a>
                </div>
        <?php endif; ?>

    </main>
</body>
</html>
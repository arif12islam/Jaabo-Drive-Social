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
    $ride_id = intval($_POST['ride_id']);
    $seats = 1; // default: 1 seat booked

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

$sql = "SELECT r.ride_id, r.title, r.origin, r.destination, r.departure_time, r.price, u.full_name 
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
        "time"  => date("g:i A", strtotime($row['departure_time']))
    ];
}
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
        <nav id="sidebar">
            <div class="user-profile">
                <div class="user-info">
                    <div class="user-image">
                        <img src="../../Asset/icons/person.png" alt="User Image" class="user-image">
                    </div>
                    <div class="user-details">
                        <h2 class="user-name"><?php echo htmlspecialchars($_SESSION['fullName']); ?></h2>
                        <p class="user-id"><?php echo htmlspecialchars($_SESSION['userID']); ?></p>
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
                        <div class="ride-price"><?= htmlspecialchars($ride['price']) ?></div>
                        <div class="ride-btn">
                            <button class="ride-action" onclick="bookRide(<?= $ride['id'] ?>)">Book Now</button>
                            <button class="ride-action">Call</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No rides found.</p>
            <?php endif; ?>
        </div>


    </main>
</body>
</html>
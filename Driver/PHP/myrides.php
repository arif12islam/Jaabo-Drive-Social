<?php
    session_start();
    if(!isset($_SESSION['userID'])){
        header("Location: ../../login.php");
        exit();
    }
    
    require_once "../../database.php";
    $userID = $_SESSION['userID'];
    
    // Check if user is a driver
    $isDriver = true; // In a real app, you would check the user's role
    
    // Fetch driver's posted rides from database
    $sql = "SELECT r.ride_id, r.title, r.origin, r.destination, r.departure_time, r.price, r.status,
                   u.full_name, u.phone 
            FROM rides r
            JOIN users u ON r.user_id = u.user_id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $postedRides = [];
    while ($row = $result->fetch_assoc()) {
        $postedRides[] = [
            "id" => $row['ride_id'],
            "name" => $row['full_name'],
            "phone" => $row['phone'],
            "title" => $row['title'],
            "from" => $row['origin'],
            "to" => $row['destination'],
            "price" => "৳" . number_format($row['price'], 0),
            "time" => date("g:i A", strtotime($row['departure_time'])),
            "date" => date("Y-m-d", strtotime($row['departure_time'])),
            "status" => $row['status']
        ];
    }
    
    // Handle ride deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ride'])) {
        $ride_id = intval($_POST['ride_id']);
        
        // Check if the ride belongs to the current user
        $check_stmt = $conn->prepare("SELECT user_id FROM rides WHERE ride_id = ?");
        $check_stmt->bind_param("i", $ride_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $ride = $check_result->fetch_assoc();
            
            if ($ride['user_id'] == $userID) {
                // Delete the ride
                $delete_stmt = $conn->prepare("DELETE FROM rides WHERE ride_id = ?");
                $delete_stmt->bind_param("i", $ride_id);
                
                if ($delete_stmt->execute()) {
                    // Also delete any bookings for this ride
                    $delete_bookings = $conn->prepare("DELETE FROM bookings WHERE ride_id = ?");
                    $delete_bookings->bind_param("i", $ride_id);
                    $delete_bookings->execute();
                    
                    $_SESSION['success'] = "Ride deleted successfully!";
                } else {
                    $_SESSION['error'] = "Failed to delete ride. Please try again.";
                }
            } else {
                $_SESSION['error'] = "You don't have permission to delete this ride.";
            }
        } else {
            $_SESSION['error'] = "Ride not found.";
        }
        
        header("Location: myrides.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../global.css">
    <link rel="stylesheet" href="../CSS/myrides.css">
    <script src="../JS/myrides.js" defer></script>
    <title>My Posted Rides - Driver</title>
    <link rel="icon" type="image/png" href="../../Asset/icons/favicon.png">
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
                    <a href="home.php">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/></svg>
                        <span>Home</span>
                    </a>
                </li>
                <li>
                    <a href="myrides.php" class="active">
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
                    <a href="../../logout.php" id="svg-logout" style="cursor: pointer;">
                        <svg transform="scale(-1, 1)" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h280v80H200v560h280v80H200Zm440-160-55-58 102-102H360v-80h327L585-622l55-58 200 200-200 200Z"/></svg>
                        <span>Log out</span>
                    </a>
                </li>
            </ul>
        </nav>  
    </div>
    
    <main>
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <span><?= $_SESSION['success'] ?></span>
                <button class="close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <span><?= $_SESSION['error'] ?></span>
                <button class="close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="page-header">
            <h1>My Posted Rides <span class="driver-badge">Driver</span></h1>
        </div>
        
        <div class="containers-wrapper">
    <?php if (count($postedRides) > 0): ?>
        <?php foreach ($postedRides as $ride): ?>
            <div class="ride-card">

                <!-- Status badge (new, styled separately) -->
                <span class="ride-status status-<?= $ride['status'] ?>">
                    <?= ucfirst($ride['status']) ?>
                </span>

                <!-- Rider image + name -->
                <div class="ride-image">
                    <div class="card-image">
                        <img src="../../Asset/icons/person.png" alt="<?= htmlspecialchars($ride['name']) ?>'s Image">
                    </div>
                    <h3 class="rider-name"><?= htmlspecialchars($ride['name']) ?></h3>
                </div>

                <!-- Ride details -->
                <div class="ride-details">
                    <h3 class="ride-title"><?= htmlspecialchars($ride['title']) ?></h3>
                    
                    <div class="ride-info">
                        <span><?= htmlspecialchars($ride['from']) ?> → <?= htmlspecialchars($ride['to']) ?></span>
                        <span><?= htmlspecialchars($ride['time']) ?></span>
                    </div>

                    <!-- New date row (keeps same style as ride-info) -->
                    <div class="ride-info">
                        <span><?= htmlspecialchars($ride['date']) ?></span>
                    </div>

                    <div class="ride-price"><?= htmlspecialchars($ride['price']) ?></div>

                    <!-- Buttons -->
                    <div class="ride-btn">
                        <?php if ($ride['status'] === 'booked'): ?>
                            <a href="tel:<?= htmlspecialchars($ride['phone']) ?>" class="ride-action">Call Rider</a>
                            <button class="ride-action" onclick="showDeleteModal(<?= $ride['id'] ?>)">Delete</button>
                        
                        <?php elseif ($ride['status'] === 'completed'): ?>
                            <button class="ride-action" onclick="viewBookings(<?= $ride['id'] ?>)">Bookings</button>
                            <button class="ride-action" onclick="showDeleteModal(<?= $ride['id'] ?>)">Delete</button>
                        
                        <?php else: ?>
                            <button class="ride-action" onclick="viewDetails(<?= $ride['id'] ?>)">Details</button>
                            <button class="ride-action" onclick="showDeleteModal(<?= $ride['id'] ?>)">Delete</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-rides">
            <i class="fas fa-car"></i>
            <p>No rides posted yet</p>
            <p>You haven't posted any rides yet.</p>
            <a href="home.php" class="ride-action" style="display: inline-block; width: auto; padding: 12px 20px;">
                Create a Ride
            </a>
        </div>
    <?php endif; ?>
</div>


        
        <!-- Delete Confirmation Modal -->
        <div class="modal" id="deleteModal">
            <div class="modal-content">
                <h3>Delete Ride</h3>
                <p>Are you sure you want to delete this ride? This action cannot be undone.</p>
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="delete_ride" value="1">
                    <input type="hidden" name="ride_id" id="ride_id">
                    <div class="modal-buttons">
                        <button type="button" class="modal-btn modal-cancel" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="modal-btn modal-confirm">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
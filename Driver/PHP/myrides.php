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
                <li><a href="home.php"><i class="fas fa-home"></i><span>Home</span></a></li>
                <li><a href="myrides.php"  class="active"><i class="fas fa-car"></i><span>My Rides</span></a></li>
                <li><a href="account.php"><i class="fas fa-user"></i><span>Account</span></a></li>
                <li><a href="../../logout.php" id="svg-logout"><i class="fas fa-sign-out-alt"></i><span>Log out</span></a></li>
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
                            <button class="ride-action" onclick="showDeleteModal(<?= $ride['id'] ?>)">End Ride</button>
                        
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

        <div class="modal" id="endRideModal">
            <div class="modal-content">
                <h3>End Ride</h3>
                <p>Are you sure you want to end this ride? This action cannot be undone.</p>
                <form method="POST" id="endRideForm">
                    <input type="hidden" name="end_ride" value="1">
                    <input type="hidden" name="ride_id" id="ride_id">
                    <input type="hidden" name="ride_id" id="ride_id">
                    <div class="modal-buttons">
                        <button type="button" class="modal-btn modal-cancel" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="modal-btn modal-confirm" style="background-color: #3498db;">End Ride</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
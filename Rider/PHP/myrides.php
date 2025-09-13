<?php
    session_start();
    if (!isset($_SESSION['userID'])) {
        header("Location: ../../login.php");
        exit();
    }
    
    require_once "../../database.php";
    $userID = $_SESSION['userID'];
    
    // Fetch booked rides from database
    $sql = "SELECT b.booking_id, b.status as booking_status, b.booked_at, 
                   r.ride_id, r.title, r.origin, r.destination, r.departure_time, r.price,
                   u.user_id as driver_id, u.full_name as driver_name, u.phone as driver_phone
            FROM bookings b
            JOIN rides r ON b.ride_id = r.ride_id
            JOIN users u ON r.user_id = u.user_id
            WHERE b.user_id = ?
            ORDER BY b.booked_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookedRides = [];
    while ($row = $result->fetch_assoc()) {
        $bookedRides[] = [
            "id" => $row['booking_id'],
            "ride_id" => $row['ride_id'],
            "name" => $row['driver_name'],
            "phone" => $row['driver_phone'],
            "title" => $row['title'],
            "from" => $row['origin'],
            "to" => $row['destination'],
            "price" => "৳" . number_format($row['price'], 0),
            "time" => date("g:i A", strtotime($row['departure_time'])),
            "date" => date("Y-m-d", strtotime($row['departure_time'])),
            "status" => $row['booking_status']
        ];
    }
    
    // Handle ride cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id = intval($_POST['booking_id']);
    $ride_id = intval($_POST['ride_id']);
    
    $conn->begin_transaction();
    try {
        $cancel_booking_stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ? AND user_id = ?");
        $cancel_booking_stmt->bind_param("is", $booking_id, $userID);
        if (!$cancel_booking_stmt->execute()) {
            throw new Exception("Failed to cancel booking.");
        }
        $cancel_booking_stmt->close();

        $update_ride_stmt = $conn->prepare("UPDATE rides SET seats_available = seats_available + 1 WHERE ride_id = ?");
        $update_ride_stmt->bind_param("i", $ride_id);
        if (!$update_ride_stmt->execute()) {
            throw new Exception("Failed to update ride availability.");
        }
        $update_ride_stmt->close();
        
        $conn->commit();
        $_SESSION['success'] = "Booking cancelled successfully! The ride is now available for others.";

    } catch (Exception $e) {
        // If anything fails, undo all changes
        $conn->rollback();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: myrides.php");
    exit();
}
// Handle booking deletion from the rider's view
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_booking'])) {
    $booking_id = intval($_POST['booking_id']);
    
    // Prepare a statement to delete the booking, ensuring it belongs to the current user
    // This is a secure way to prevent users from deleting others' bookings
    $stmt = $conn->prepare("DELETE FROM bookings WHERE booking_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $booking_id, $userID);
    
    if ($stmt->execute()) {
        // Check if a row was actually deleted
        if ($stmt->affected_rows > 0) {
            $_SESSION['success'] = "Booking record removed from your list successfully!";
        } else {
            // This case handles if the booking doesn't exist or doesn't belong to the user
            $_SESSION['error'] = "Could not remove booking. It may have already been removed or you do not have permission.";
        }
    } else {
        $_SESSION['error'] = "Failed to remove the booking record. Please try again.";
    }
    
    $stmt->close();
    
    header("Location: myrides.php");
    exit();
}
?>
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../global.css">
    <title>My Rides - Rider</title>
    <link rel="icon" type="image/png" href="../../Asset/icons/favicon.png">
    <link rel="stylesheet" href="../CSS/myrides.css">
    <script src="../JS/myrides.js" defer></script>
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
                <li><a href="myrides.php" class="active"><i class="fas fa-car"></i><span>My Rides</span></a></li>
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
            <h1>My Booked Rides <span class="rider-badge">Rider</span></h1>
        </div>
        
        <!-- Ride Cards -->
        <div class="containers-wrapper">
            <?php if (count($bookedRides) > 0): ?>
                <?php foreach ($bookedRides as $ride): ?>
                    <div class="ride-card">
                        <span class="ride-status status-<?= $ride['status'] ?>">
                            <?= ucfirst($ride['status']) ?>
                        </span>
                        <div class="ride-image">
                            <div class="card-image">
                                <img src="../../Asset/icons/person.png" alt="Driver Image">
                            </div>
                            <h3 class="rider-name"><?= htmlspecialchars($ride['name']) ?></h3>
                        </div>
                        <div class="ride-details">
                            <h3 class="ride-title"><?= htmlspecialchars($ride['title']) ?></h3>
                            <div class="ride-info">
                                <span><?= htmlspecialchars($ride['from']) ?> → <?= htmlspecialchars($ride['to']) ?></span>
                                <span><?= htmlspecialchars($ride['time']) ?></span>
                            </div>
                            <div class="ride-date-time">
                                <span><?= htmlspecialchars($ride['date']) ?></span>
                            </div>
                            <div class="ride-price"><?= htmlspecialchars($ride['price']) ?></div>
                            <!-- Buttons -->
                            <div class="ride-btn">
                                <?php if ($ride['status'] === 'booked'): ?>
                                    <a href="tel:<?= htmlspecialchars($ride['phone']) ?>" class="ride-action">Call Rider</a>
                                    <button class="ride-action" id="red" onclick="showCancelModal(<?= $ride['id'] ?>)">Cancel Ride</button>
                                
                                <?php elseif ($ride['status'] === 'completed'): ?>
                                    <button class="ride-action" onclick="viewBookings(<?= $ride['id'] ?>)">Bookings</button>
                                    <button class="ride-action" id="green" onclick="showPaymentModal(<?= $ride['id'] ?>)">Pay</button>

                                <?php elseif ($ride['status'] === 'cancelled'): ?>
                                    <button class="ride-action" onclick="viewDetails(<?= $ride['id'] ?>)">Details</button>
                                    <button class="ride-action" id="red" onclick="showDeleteModal(<?= $ride['id'] ?>)">Delete</button>
                                <?php else: ?>
                                    <button class="ride-action" onclick="viewDetails(<?= $ride['id'] ?>)">Details</button>
                                    <button class="ride-action" id="red" onclick="showDeleteModal(<?= $ride['id'] ?>)">Delete</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-rides">
                    <i class="fas fa-car"></i>
                    <p>No rides found</p>
                    <p>You haven't booked any rides yet.</p>
                    <a href="home.php" class="ride-action" style="display: inline-block; width: auto; padding: 12px 20px;">
                        Find Rides
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Cancel Modal -->
        <div class="modal" id="cancelModal">
            <div class="modal-content">
                <h3>Cancel Booking</h3>
                <p>Are you sure you want to cancel this booking? The ride will become available for others to book.</p>
                <form method="POST" id="cancelForm">
                    <input type="hidden" name="cancel_booking" value="1">
                    <input type="hidden" name="booking_id" id="booking_id">
                    <input type="hidden" name="ride_id" id="ride_id">
                    <div class="modal-buttons">
                        <button type="button" class="modal-btn modal-cancel" onclick="closeModal()">No, Keep It</button>
                        <button type="submit" class="modal-btn modal-delete">Yes, Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal" id="payModal">
            <div class="modal-content">
                <h3>Payment Confirmation</h3>
                <p>Are you sure you want to proceed with the payment?</p>
                <form method="POST" id="paymentForm">
                    <input type="hidden" name="confirm_payment" value="1">
                    <input type="hidden" name="booking_id" id="booking_id">
                    <input type="hidden" name="ride_id" id="ride_id">
                    <div class="modal-buttons">
                        <button type="button" class="modal-btn modal-cancel" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="modal-btn modal-confirm">Yes, Pay Now</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="modal" id="deleteModal">
            <div class="modal-content">
                <h3>Delete Booking</h3>
                <p>Are you sure you want to delete this booking from your list? This action cannot be undone.</p>
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="delete_booking" value="1">
                    <input type="hidden" name="booking_id" id="delete_booking_id">
                    <div class="modal-buttons">
                        <button type="button" class="modal-btn modal-cancel" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="modal-btn modal-delete">Yes, Delete</button>
                    </div>
                </form>
            </div>
        </div>

    </main> 
</body>
</html>

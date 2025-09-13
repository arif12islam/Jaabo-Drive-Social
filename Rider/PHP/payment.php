<?php
    session_start();
    require_once "../../database.php";

    if (!isset($_SESSION['userID'])) {
        header("Location: ../../login.php");
        exit();
    }
    if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
        $_SESSION['error'] = "Invalid request.";
        header("Location: myrides.php");
        exit();
    }

    $userID = $_SESSION['userID'];
    $booking_id = intval($_GET['booking_id']);

    // 2. Handle the payment form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
        $payment_successful = false;
        $payment_method = $_POST['payment_method'] ?? 'card';

        // Server-side validation based on payment method
        if ($payment_method === 'mfs') {
            if (!empty($_POST['mfs_provider']) && !empty($_POST['mfs_number']) && !empty($_POST['mfs_pin'])) {
                $payment_successful = true;
            } else {
                $_SESSION['error'] = "Please select a provider and enter your mobile number and PIN.";
            }
        } elseif ($payment_method === 'card') {
            if (!empty($_POST['card_number']) && !empty($_POST['card_holder']) && !empty($_POST['expiry_date']) && !empty($_POST['cvc'])) {
                $payment_successful = true;
            } else {
                $_SESSION['error'] = "Please fill in all card details.";
            }
        }

        if ($payment_successful) {
            // Get the ride_id from the booking_id for the transaction
            $ride_id = null;
            $stmt_get_ride = $conn->prepare("SELECT ride_id FROM bookings WHERE booking_id = ?");
            $stmt_get_ride->bind_param("i", $booking_id);
            $stmt_get_ride->execute();
            $result = $stmt_get_ride->get_result();
            if ($row = $result->fetch_assoc()) {
                $ride_id = $row['ride_id'];
            }
            $stmt_get_ride->close();

            if ($ride_id) {
                // Start the transaction
                $conn->begin_transaction();
                
                try {
                    // Update the bookings table
                    $stmt_booking = $conn->prepare("UPDATE bookings SET status = 'paid' WHERE booking_id = ? AND user_id = ? AND status = 'completed'");
                    $stmt_booking->bind_param("is", $booking_id, $userID);
                    $stmt_booking->execute();
                    $booking_updated = $stmt_booking->affected_rows > 0;
                    $stmt_booking->close();

                    // Update the rides table
                    $stmt_ride = $conn->prepare("UPDATE rides SET status = 'paid' WHERE ride_id = ?");
                    $stmt_ride->bind_param("i", $ride_id);
                    $stmt_ride->execute();
                    $ride_updated = $stmt_ride->affected_rows > 0;
                    $stmt_ride->close();

                    if ($booking_updated && $ride_updated) {
                        $conn->commit();
                        $_SESSION['success'] = "Payment successful! Thank you.";
                    } else {
                        $conn->rollback();
                        $_SESSION['error'] = "Payment failed or booking could not be updated. It might have been processed already.";
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['error'] = "A database error occurred. Please try again.";
                }
            } else {
                 $_SESSION['error'] = "Could not find the associated ride for this booking.";
            }
        } else {
            if (!isset($_SESSION['error'])) {
                 $_SESSION['error'] = "Payment processing failed. Please try again.";
            }
        }
        
        if (isset($_SESSION['success'])) {
            header("Location: myrides.php");
        } else {
            header("Location: payment.php?booking_id=" . $booking_id);
        }
        exit();
    }

    // 3. Fetch booking details to display on the page
    $sql = "SELECT r.title, r.price FROM bookings b JOIN rides r ON b.ride_id = r.ride_id WHERE b.booking_id = ? AND b.user_id = ? AND b.status = 'completed'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $booking_id, $userID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        $_SESSION['error'] = "This booking is not available for payment.";
        header("Location: myrides.php");
        exit();
    }
    $ride = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../global.css">
    <link rel="stylesheet" href="../CSS/payment.css">
    <script src="../JS/payment.js" defer></script>
</head>
<body>
    <main>
        <div class="payment-container">
            <h1 class="page-title">Secure Payment</h1>
            <p class="ride-summary">For ride: <strong><?= htmlspecialchars($ride['title']) ?></strong></p>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message"><?= $_SESSION['error']; ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="payment-method-selector">
                <button class="payment-method-btn active" data-target="card-payment"><i class="fas fa-credit-card"></i> Card</button>
                <button class="payment-method-btn" data-target="mfs-payment"><i class="fas fa-mobile-alt"></i> Mobile Banking</button>
            </div>
            
            <form id="paymentForm" method="POST">
                 <input type="hidden" name="confirm_payment" value="1">
                 <input type="hidden" name="payment_method" id="payment_method_input" value="card">
                 
                <div id="card-payment" class="payment-section active">
                     <div class="form-group">
                        <label for="card-number">Card Number</label>
                        <input type="text" id="card-number" name="card_number" placeholder="0000 0000 0000 0000" required>
                    </div>
                     <div class="form-group">
                        <label for="card-holder">Card Holder Name</label>
                        <input type="text" id="card-holder" name="card_holder" placeholder="e.g. John Doe" required>
                    </div>
                     <div class="form-row">
                        <div class="form-group">
                            <label for="expiry-date">Expiry Date</label>
                            <input type="text" id="expiry-date" name="expiry_date" placeholder="MM/YY" required>
                        </div>
                        <div class="form-group">
                            <label for="cvc">CVC</label>
                            <input type="text" id="cvc" name="cvc" placeholder="123" required>
                        </div>
                     </div>
                </div>

                <div id="mfs-payment" class="payment-section">
                    <div class="mfs-options">
                        <input type="radio" id="bkash" name="mfs_provider" value="bkash">
                        <label for="bkash"><img src="https://placehold.co/100x40/E2136E/FFFFFF?text=bKash" alt="bKash"></label>
                        
                        <input type="radio" id="nagad" name="mfs_provider" value="nagad">
                        <label for="nagad"><img src="https://placehold.co/100x40/F16522/FFFFFF?text=Nagad" alt="Nagad"></label>
                        
                        <input type="radio" id="rocket" name="mfs_provider" value="rocket">
                        <label for="rocket"><img src="https://placehold.co/100x40/8E44AD/FFFFFF?text=Rocket" alt="Rocket"></label>
                    </div>
                    <div class="form-group">
                        <label for="mfs-number">Mobile Number</label>
                        <input type="tel" id="mfs-number" name="mfs_number" placeholder="e.g., 01700000000" required>
                    </div>
                    <div class="form-group">
                        <label for="mfs-pin">PIN</label>
                        <input type="password" id="mfs-pin" name="mfs_pin" placeholder="Enter your PIN" required>
                    </div>
                </div>

                <button type="submit" class="form-submit">Pay Now (৳<?= number_format($ride['price'], 2) ?>)</button>
            </form>
        </div>
    </main>
</body>
</html>

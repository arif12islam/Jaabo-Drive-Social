function showLogoutConfirmation() {
    return confirm('Are you sure you want to log out?');
}

// Add event listener to logout link
document.getElementById('svg-logout').addEventListener('click', function(e) {
    if (!showLogoutConfirmation()) {
        e.preventDefault();
    }
});

// Modal functions
function showCancelModal(bookingId, rideId) {
    document.getElementById('booking_id').value = bookingId;
    document.getElementById('ride_id').value = rideId;
    document.getElementById('cancelModal').style.display = 'flex';
}
function showPaymentModal(bookingId, rideId) {
    document.getElementById('booking_id').value = bookingId;
    document.getElementById('ride_id').value = rideId;
    document.getElementById('payModal').style.display = 'flex';
}
function showDeleteModal(bookingId) {
    // Set the value of the hidden input in the corrected modal form
    document.getElementById('delete_booking_id').value = bookingId;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('cancelModal').style.display = 'none';
    document.getElementById('payModal').style.display = 'none';
    document.getElementById('deleteModal').style.display = 'none';
}

// Close modal if clicked outside
window.onclick = function(event) {
    const modal = document.getElementById('cancelModal');
    if (event.target === modal) {
        closeModal();
    }
};

// Action functions
function callDriver(driverName, driverPhone) {
    alert(`Calling ${driverName} at ${driverPhone}...`);
}
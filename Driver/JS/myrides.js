
function showLogoutConfirmation() {
    return confirm('Are you sure you want to log out?');
}

document.getElementById('svg-logout').addEventListener('click', function(e) {
    if (!showLogoutConfirmation()) {
        e.preventDefault();
    }
});

// Modal functions
function showDeleteModal(rideId) {
    document.getElementById('ride_id').value = rideId;
    document.getElementById('deleteModal').style.display = 'flex';
}
function showEndRideModal(rideId) {
    document.getElementById('end_ride_id').value = rideId;
    document.getElementById('endRideModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('deleteModal').style.display = 'none';
    document.getElementById('endRideModal').style.display = 'none';
}

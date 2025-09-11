function bookRide(rideId) {
    if (confirm("Are you sure you want to book this ride?")) {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'home.php';
        
        const rideInput = document.createElement('input');
        rideInput.type = 'hidden';
        rideInput.name = 'ride_id';
        rideInput.value = rideId;
        
        const bookInput = document.createElement('input');
        bookInput.type = 'hidden';
        bookInput.name = 'book_ride';
        bookInput.value = '1';
        
        form.appendChild(rideInput);
        form.appendChild(bookInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function showLogoutConfirmation() {
    return confirm("Are you sure you want to log out?");
}
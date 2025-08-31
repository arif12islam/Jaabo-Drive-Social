function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profileImagePreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function showLogoutConfirmation() {
    return confirm('Are you sure you want to log out?');
}
function confirmDelete() {
    return confirm("Are you sure you want to delete your account? This action cannot be undone.");
}
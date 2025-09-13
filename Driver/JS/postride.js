window.onload = function() {
    const now = new Date();
    const localDatetime = now.toISOString().slice(0, 16);
    document.getElementById('departure_time').min = localDatetime;
};

document.getElementById('price').addEventListener('input', function() {
    if (this.value <= 0) {
        this.setCustomValidity('Price must be greater than 0');
    } else {
        this.setCustomValidity('');
    }
});

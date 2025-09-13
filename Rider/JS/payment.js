document.addEventListener('DOMContentLoaded', () => {
    const paymentMethodButtons = document.querySelectorAll('.payment-method-btn');
    const paymentSections = document.querySelectorAll('.payment-section');
    const hiddenMethodInput = document.getElementById('payment_method_input');

    paymentMethodButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = button.dataset.target;

            paymentMethodButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            paymentSections.forEach(section => {
                section.classList.remove('active');
                if (section.id === targetId) {
                    section.classList.add('active');
                }
            });

            const methodValue = targetId === 'card-payment' ? 'card' : 'mfs';
            hiddenMethodInput.value = methodValue;

        });
    });
});
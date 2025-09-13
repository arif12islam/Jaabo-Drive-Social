document.addEventListener('DOMContentLoaded', () => {
    const paymentMethodButtons = document.querySelectorAll('.payment-method-btn');
    const paymentSections = document.querySelectorAll('.payment-section');
    const hiddenMethodInput = document.getElementById('payment_method_input');

    const cardInputs = document.querySelectorAll('#card-payment input');
    const mfsInputs = document.querySelectorAll('#mfs-payment input');
    const mfsProviderRadios = document.querySelectorAll('#mfs-payment input[type="radio"]');

    function updateRequiredAttributes(activeMethod) {
        if (activeMethod === 'card-payment') {
            cardInputs.forEach(input => input.setAttribute('required', ''));
            mfsInputs.forEach(input => input.removeAttribute('required'));
        } else if (activeMethod === 'mfs-payment') {
            cardInputs.forEach(input => input.removeAttribute('required'));
            
            mfsProviderRadios[0].setAttribute('required', '');
            document.getElementById('mfs-number').setAttribute('required', '');
            document.getElementById('mfs-pin').setAttribute('required', '');
        }
    }

    paymentMethodButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = button.dataset.target;

            // Update button active state
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
            updateRequiredAttributes(targetId);
        });
    });

    updateRequiredAttributes('card-payment');
});
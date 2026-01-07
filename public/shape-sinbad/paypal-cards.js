document.addEventListener('DOMContentLoaded', function () {
    function renderButton(selector, amount) {
        if (typeof paypal === 'undefined') {
            setTimeout(() => renderButton(selector, amount), 200);
            return;
        }

        paypal.Buttons({
            style: {
                layout: 'vertical',
                shape: 'rect',
                label: 'paypal'
            },
            createOrder: (data, actions) => {
                return actions.order.create({
                    purchase_units: [{
                        amount: { value: amount }
                    }]
                });
            }
        }).render(selector);
    }

    renderButton('#paypal-position', '190.00');
    renderButton('#paypal-discount', '390.00');
    renderButton('#paypal-standard', '3850.00');
    renderButton('#paypal-premium', '7850.00');
});





/**
 * Stock adjuster for the product show page.
 *
 * Submits the quantity form with the Fetch API so a delivery or a sale
 * is recorded in the background, then updates the quantity and a status
 * message in place. Without JavaScript the form still posts normally.
 */

const form = document.querySelector('[data-quantity-form]');

if (form) {
    const quantityOutput = document.querySelector('[data-quantity]');
    const feedback = document.querySelector('[data-quantity-feedback]');
    const amountInput = form.querySelector('[name="amount"]');
    const csrfToken = form.querySelector('[name="_token"]');
    const submitButtons = form.querySelectorAll('button[type="submit"]');

    const setBusy = (isBusy) => {
        form.setAttribute('aria-busy', String(isBusy));

        submitButtons.forEach((button) => {
            button.disabled = isBusy;
        });
    };

    const showFeedback = (type, message) => {
        if (!feedback) {
            return;
        }

        feedback.textContent = message;
        feedback.className = `quantity-feedback quantity-feedback--${type}`;
        feedback.hidden = false;
    };

    const refreshQuantity = (quantity) => {
        if (quantityOutput) {
            quantityOutput.textContent = `${quantity} pcs`;
        }
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        // The button that submitted the form carries the direction,
        // so pressing "Remove stock" always reports a sale.
        const direction = event.submitter?.value ?? 'increase';

        setBusy(true);

        try {
            const response = await fetch(form.action, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken?.value ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    direction,
                    amount: Number(amountInput.value),
                }),
            });

            const payload = await response.json();

            if (response.ok) {
                refreshQuantity(payload.quantity);
                showFeedback('success', payload.message);

                return;
            }

            const firstError = Object.values(payload.errors ?? {}).flat()[0];

            showFeedback(
                'error',
                firstError ?? payload.message ?? 'The stock could not be updated.',
            );
        } catch {
            showFeedback('error', 'Could not reach the server, please try again.');
        } finally {
            setBusy(false);
        }
    });
}

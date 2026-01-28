document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('add-booking-session-btn');

    if (!btn || !wp || !wp.data) return;

    let waitingForSave = false;

    btn.addEventListener('click', function () {
        const flag = document.querySelector(
            'input[name="add_booking_session"]'
        );

        if (flag) {
            flag.value = '1';
        }

        waitingForSave = true;
        console.log('saving post');

        wp.data.dispatch('core/editor').savePost();
    });

    wp.data.subscribe(() => {
        if (!waitingForSave) return;

        const editor = wp.data.select('core/editor');
        if (!editor) return;

        waitingForSave = false;
        window.location.reload();
    });
});

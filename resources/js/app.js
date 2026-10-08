const errors = document.getElementById('form-errors');

if (errors instanceof HTMLElement) {
    errors.focus();
}

document.querySelectorAll('form').forEach((form) => {
    const syncSchedule = () => {
        const allDay = form.querySelector('input[name="schedule"]:checked')?.value === 'all_day';
        const block = form.querySelector('[data-timed-fields]');

        if (!(block instanceof HTMLElement)) {
            return;
        }

        block.hidden = allDay;
        block.querySelectorAll('input').forEach((input) => {
            input.disabled = allDay;
        });
    };

    if (form.querySelector('[data-timed-fields]')) {
        form.addEventListener('change', syncSchedule);
        syncSchedule();
    }
});

document.addEventListener('click', (event) => {
    const target = event.target;

    if (!(target instanceof Element)) {
        return;
    }

    const opener = target.closest('[data-flyer-open]');

    if (opener instanceof HTMLAnchorElement) {
        const dialog = document.getElementById(opener.getAttribute('aria-controls') || '');

        if (dialog instanceof HTMLDialogElement && typeof dialog.showModal === 'function') {
            event.preventDefault();
            dialog.showModal();
        }
    }

    if (target instanceof HTMLDialogElement) {
        target.close();
    }
});

const map = document.querySelector('#events-map');

if (map instanceof HTMLElement) {
    const loader = map.dataset.provider === 'google'
        ? import('./maps/google-map.js')
        : import('./maps/open-map.js');

    loader.then(({ mount }) => mount(map)).catch(() => {
        const note = document.createElement('p');
        note.className = 'mt-3';
        note.textContent = 'The map did not load. The event list is still available.';
        map.insertAdjacentElement('afterend', note);
    });
}

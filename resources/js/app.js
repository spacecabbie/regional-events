const errors = document.getElementById('form-errors');

if (errors instanceof HTMLElement) {
    errors.focus();
}

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

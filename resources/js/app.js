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

function openFlyer(opener) {
    const dialog = document.getElementById('flyer-overlay');
    const image = document.getElementById('flyer-overlay-image');
    const title = document.getElementById('flyer-overlay-title');

    if (!(dialog instanceof HTMLDialogElement) || typeof dialog.showModal !== 'function') {
        return;
    }

    if (!(image instanceof HTMLImageElement)) {
        return;
    }

    const src = opener.getAttribute('data-flyer-src');

    if (!src) {
        return;
    }

    const srcset = opener.getAttribute('data-flyer-srcset') || '';
    const sizes = opener.getAttribute('data-flyer-sizes') || '';
    const width = opener.getAttribute('data-flyer-width') || '';
    const height = opener.getAttribute('data-flyer-height') || '';
    const alt = opener.getAttribute('data-flyer-alt') || 'Flyer';

    image.alt = alt;
    image.src = src;

    if (srcset !== '') {
        image.srcset = srcset;
        image.sizes = sizes;
    } else {
        image.removeAttribute('srcset');
        image.removeAttribute('sizes');
    }

    if (width !== '' && height !== '') {
        image.width = Number(width);
        image.height = Number(height);
        image.style.setProperty('--flyer-width', `${width}px`);
        image.style.setProperty('--flyer-height', `${height}px`);
    } else {
        image.removeAttribute('width');
        image.removeAttribute('height');
        image.style.removeProperty('--flyer-width');
        image.style.removeProperty('--flyer-height');
    }

    if (title instanceof HTMLElement) {
        title.textContent = alt;
    }

    if (!dialog.open) {
        dialog.showModal();
    }
}

document.addEventListener('click', (event) => {
    const target = event.target;

    if (!(target instanceof Element)) {
        return;
    }

    const opener = target.closest('[data-flyer-open]');

    if (opener instanceof HTMLButtonElement) {
        event.preventDefault();
        openFlyer(opener);
    }
});

const flyerDialog = document.getElementById('flyer-overlay');

// closedby="any" is not in Safari yet. A backdrop click targets the dialog
// itself and lands outside its border box.
if (flyerDialog instanceof HTMLDialogElement && !('closedBy' in HTMLDialogElement.prototype)) {
    flyerDialog.addEventListener('click', (event) => {
        if (event.target !== flyerDialog) {
            return;
        }

        const rect = flyerDialog.getBoundingClientRect();
        const inside = rect.top <= event.clientY
            && event.clientY <= rect.top + rect.height
            && rect.left <= event.clientX
            && event.clientX <= rect.left + rect.width;

        if (!inside) {
            flyerDialog.close();
        }
    });
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

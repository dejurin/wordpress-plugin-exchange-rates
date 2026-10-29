(() => {
    'use strict';
    const messages = window.exchangeRatesMessages || {};
    const dialog = document.querySelector('#exchange-rates-information');
    if (!dialog) {
        return;
    }
    const hideDialog = () => {
        dialog.hidden = true;
        dialog.style.setProperty('display', 'none', 'important');
    };
    hideDialog();
    // Unsupported browsers keep the information inert and out of document flow.
    if (typeof window.HTMLDialogElement !== 'function' ||
        !(dialog instanceof window.HTMLDialogElement) ||
        typeof dialog.showModal !== 'function' || typeof dialog.close !== 'function') {
        document.querySelectorAll('.er-footer-slot [data-er-info]').forEach(button => {
            button.disabled = true;
            button.removeAttribute('aria-haspopup');
            button.removeAttribute('aria-controls');
        });
        return;
    }
    const closeButton = dialog.querySelector('.er-dialog-close');
    const initialFocus = dialog.querySelector('[data-er-initial-focus]') || closeButton;
    const date = dialog.querySelector('[data-er-modal-date]');
    const recorded = dialog.querySelector('[data-er-modal-recorded]');
    const formatter = new Intl.DateTimeFormat('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit', hourCycle: 'h23', timeZone: 'UTC'
    });
    const setTimestamp = (element, value) => {
        if (!element) { return; }
        if (!value || !Number.isFinite(Date.parse(value))) {
            element.removeAttribute('datetime');
            element.textContent = messages.notAvailable || 'Not available';
            return;
        }
        const parts = {};
        formatter.formatToParts(new Date(value)).forEach(part => { parts[part.type] = part.value; });
        element.dateTime = new Date(value).toISOString();
        element.textContent = `${parts.day} ${parts.month} ${parts.year} ${parts.hour}:${parts.minute} UTC`;
    };
    let previousOverflow = '';
    let scrollLocked = false;
    let opener = null;

    function finishClose() {
        hideDialog();
        if (scrollLocked) {
            document.body.style.overflow = previousOverflow;
            scrollLocked = false;
        }
        if (opener && opener.isConnected) { opener.focus(); }
        opener = null;
    }

    function closeDialog() {
        dialog.close();
        finishClose();
    }

    document.querySelectorAll('[data-er-info]').forEach(button => {
        button.addEventListener('click', () => {
            if (dialog.open) { return; }
            if (scrollLocked) { finishClose(); }
            const timestamp = button.querySelector('time');
            const value = timestamp && timestamp.dateTime;
            const sourceLink = dialog.querySelector('[data-er-modal-source]');
            sourceLink.removeAttribute('href');
            dialog.querySelector('[data-er-modal-source-name]').textContent = button.dataset.sourceName || messages.unknownSource || 'Unknown source';
            if (/^https?:\/\//i.test(button.dataset.sourceUrl || '')) {
                sourceLink.href = button.dataset.sourceUrl;
            }
            setTimestamp(date, value);
            setTimestamp(recorded, button.dataset.recordedAt);
            dialog.querySelectorAll('a').forEach(link => {
                link.rel = 'noreferrer noopener nofollow';
            });
            // Keep the inline hidden guard until a real top-layer modal is confirmed.
            try {
                dialog.showModal();
                if (!dialog.matches(':modal')) {
                    hideDialog();
                    dialog.close();
                    return;
                }
            } catch (error) {
                hideDialog();
                if (dialog.open) { dialog.close(); }
                console.warn('Data details could not be opened as a modal dialog.', error);
                return;
            }
            opener = button;
            dialog.hidden = false;
            dialog.style.removeProperty('display');
            previousOverflow = document.body.style.overflow;
            scrollLocked = true;
            document.body.style.overflow = 'hidden';
            initialFocus.focus();
        });
    });
    dialog.addEventListener('keydown', event => {
        if (event.key !== 'Tab') {
            return;
        }
        const controls = Array.from(dialog.querySelectorAll('button:not([disabled]), a[href]'))
            .filter(element => element.getClientRects().length > 0);
        const first = controls[0];
        const last = controls[controls.length - 1];
        if (event.shiftKey && (document.activeElement === first || document.activeElement === initialFocus)) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });
    closeButton.addEventListener('click', closeDialog);
    dialog.addEventListener('cancel', event => {
        event.preventDefault();
        closeDialog();
    });
    dialog.addEventListener('close', () => {
        // A queued close event must not hide a subsequently reopened modal.
        if (!dialog.open) { finishClose(); }
    });
    dialog.addEventListener('click', event => {
        const box = dialog.getBoundingClientRect();
        if (event.target === dialog && (
            event.clientX < box.left || event.clientX > box.right ||
            event.clientY < box.top || event.clientY > box.bottom
        )) {
            closeDialog();
        }
    });
})();

(() => {
'use strict';
const messages = window.exchangeRatesMessages || {};
// preserve UTC input and format captions in the visitor's time zone.
function formatCaptionTime(value, locale = 'en-GB', timeZone) {
    if (typeof value !== 'string' || !/T.*(?:Z|[+-]\d{2}:\d{2})$/i.test(value) ||
        !Number.isFinite(Date.parse(value))) {
        throw new Error('A caption timestamp must include a valid date and explicit time zone.');
    }
    const formatter = new Intl.DateTimeFormat(locale, {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
        ...(timeZone ? { timeZone } : {})
    });
    const parts = {};
    formatter.formatToParts(new Date(value)).forEach(part => { parts[part.type] = part.value; });
    return {
        compact: `${parts.day} ${parts.month} ${parts.hour}:${parts.minute}`,
        full: `${parts.day} ${parts.month} ${parts.year} ${parts.hour}:${parts.minute}`,
        timeZone: formatter.resolvedOptions().timeZone
    };
}

function updateCaptionTime(button, locale) {
    const time = button.querySelector('time');
    const formatted = formatCaptionTime(time && time.dateTime, locale);
    time.textContent = formatted.compact;
    const description = [
        button.disabled ? (messages.rateDate || 'Rate date') : (messages.details || 'Data details and disclaimer'), button.dataset.sourceName,
        `${formatted.full} (${formatted.timeZone})`
    ].filter(Boolean).join(' — ');
    button.title = description;
    button.setAttribute('aria-label', description);
    // Do not change time.dateTime: the dialog must still receive the UTC instant.
}

document.querySelectorAll('.er-footer-slot [data-er-info]').forEach(button => {
    if (!button.querySelector('time[datetime]')) { return; }
    try { updateCaptionTime(button, document.documentElement.lang || undefined); }
    catch (error) { console.warn('Exchange Rates could not format the local date; UTC remains visible.', error); }
});
})();

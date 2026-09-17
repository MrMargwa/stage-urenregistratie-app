(function () {
    'use strict';

    if (window.__stageurenKeepAliveLoaded) {
        return;
    }
    window.__stageurenKeepAliveLoaded = true;

    var PING_INTERVAL = 3 * 60 * 1000;
    var DRAFT_TTL = 24 * 60 * 60 * 1000;
    var MAX_AUTO_RETRIES = 2;
    var SUBMIT_METHODS = ['create', 'save', 'createAnother'];
    var FIELD_SELECTOR = 'input[wire\\:model], textarea[wire\\:model], select[wire\\:model]';

    try {
        window.sessionStorage.removeItem('stageuren.errorRetries');
    } catch (error) {}

    function pathOf() {
        return window.location.pathname;
    }

    function draftKey(path) {
        return 'stageuren.draft:' + path;
    }

    function retryKey(path) {
        return 'stageuren.retry:' + path;
    }

    function isFormField(el) {
        return !!(el && el.closest && el.closest(FIELD_SELECTOR));
    }

    function collectFormData() {
        var data = {};
        var els = document.querySelectorAll(FIELD_SELECTOR);
        for (var i = 0; i < els.length; i++) {
            var el = els[i];
            if (el.disabled || el.readOnly || el.type === 'password') {
                continue;
            }
            var key = el.getAttribute('wire:model') || el.name;
            if (!key) {
                continue;
            }
            var value;
            if (el.type === 'checkbox' || el.type === 'radio') {
                if (!el.checked) {
                    continue;
                }
                value = el.value;
            } else {
                value = el.value;
            }
            if (value !== null && value !== undefined && value !== '' && data[key] === undefined) {
                data[key] = value;
            }
        }
        return data;
    }

    function saveDraft() {
        var data = collectFormData();
        if (!Object.keys(data).length) {
            return;
        }
        try {
            window.localStorage.setItem(draftKey(pathOf()), JSON.stringify({
                data: data,
                savedAt: Date.now(),
                url: window.location.href,
            }));
        } catch (error) {}
    }

    var draftTimer = null;

    function scheduleDraft() {
        if (draftTimer) {
            window.clearTimeout(draftTimer);
        }
        draftTimer = window.setTimeout(saveDraft, 600);
    }

    function onFormInput(event) {
        if (isFormField(event.target)) {
            scheduleDraft();
        }
    }

    document.addEventListener('input', onFormInput, true);
    document.addEventListener('change', onFormInput, true);

    function loadDraft(path) {
        try {
            var raw = window.localStorage.getItem(draftKey(path));
            return raw ? JSON.parse(raw) : null;
        } catch (error) {
            return null;
        }
    }

    function clearDraft(path) {
        try {
            window.localStorage.removeItem(draftKey(path));
        } catch (error) {}
    }

    function restoreDraft(path) {
        var draft = loadDraft(path);
        if (!draft || !draft.data) {
            clearDraft(path);
            return false;
        }
        if (Date.now() - (draft.savedAt || 0) > DRAFT_TTL) {
            clearDraft(path);
            return false;
        }
        var els = document.querySelectorAll(FIELD_SELECTOR);
        var restored = false;
        for (var i = 0; i < els.length; i++) {
            var el = els[i];
            if (el.disabled || el.readOnly || el.type === 'password') {
                continue;
            }
            var key = el.getAttribute('wire:model') || el.name;
            if (!key || !Object.prototype.hasOwnProperty.call(draft.data, key)) {
                continue;
            }
            var value = draft.data[key];
            if (String(el.value) === String(value)) {
                continue;
            }
            el.value = value;
            try {
                el.dispatchEvent(new Event('input', { bubbles: true }));
            } catch (error) {}
            restored = true;
        }
        return restored;
    }

    var toastEl = null;
    var toastTimer = null;

    function themeColor() {
        try {
            var el = document.querySelector('html');
            var cs = window.getComputedStyle(el);
            var v = cs.getPropertyValue('--primary-500').trim() || cs.getPropertyValue('--primary-400').trim();
            return v || '#6366f1';
        } catch (error) {
            return '#6366f1';
        }
    }

    function showToast(message, type) {
        if (toastEl) {
            toastEl.remove();
        }
        var dark = document.documentElement.classList.contains('dark');
        var bg = themeColor();
        if (type === 'error') {
            bg = dark ? '#7f1d1d' : '#b91c1c';
        }
        if (type === 'success') {
            bg = dark ? '#14532d' : '#166534';
        }
        var el = document.createElement('div');
        el.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:2147483646;background:' + bg + ';color:#fff;padding:12px 18px;border-radius:10px;font:600 14px/1.4 system-ui,sans-serif;box-shadow:0 10px 30px rgba(0,0,0,.35);max-width:90vw;text-align:center;';
        el.textContent = message;
        document.body.appendChild(el);
        toastEl = el;
        if (toastTimer) {
            window.clearTimeout(toastTimer);
        }
        toastTimer = window.setTimeout(function () {
            if (toastEl) {
                toastEl.remove();
                toastEl = null;
            }
        }, 7000);
    }

    var overlayEl = null;
    var countdownTimer = null;

    function hideOverlay() {
        if (overlayEl) {
            overlayEl.remove();
            overlayEl = null;
        }
        if (countdownTimer) {
            window.clearInterval(countdownTimer);
            countdownTimer = null;
        }
    }

    function showOverlay(opts) {
        hideOverlay();
        var dark = document.documentElement.classList.contains('dark');
        var cardBg = dark ? '#1e293b' : '#ffffff';
        var cardFg = dark ? '#f1f5f9' : '#0f172a';
        var muted = dark ? '#94a3b8' : '#475569';
        var accent = themeColor();

        var dim = document.createElement('div');
        dim.style.cssText = 'position:fixed;inset:0;z-index:2147483647;background:rgba(2,6,23,.6);display:flex;align-items:center;justify-content:center;padding:24px;';

        var box = document.createElement('div');
        box.style.cssText = 'background:' + cardBg + ';color:' + cardFg + ';max-width:430px;width:100%;border-radius:14px;padding:26px 28px;box-shadow:0 24px 60px rgba(0,0,0,.45);font:400 15px/1.6 system-ui,sans-serif;';

        var title = document.createElement('div');
        title.style.cssText = 'font:700 18px/1.3 system-ui,sans-serif;margin-bottom:10px;color:' + cardFg + ';';
        title.textContent = opts.title;
        box.appendChild(title);

        var body = document.createElement('div');
        body.style.cssText = 'color:' + muted + ';margin-bottom:22px;';
        body.textContent = opts.body;
        box.appendChild(body);

        var buttonRow = document.createElement('div');
        buttonRow.style.cssText = 'display:flex;gap:10px;justify-content:flex-end;align-items:center;';

        if (opts.countdown > 0) {
            var countdownLabel = document.createElement('span');
            countdownLabel.style.cssText = 'color:' + muted + ';font-size:13px;margin-right:auto;';
            var leftMs = opts.countdown;
            countdownLabel.textContent = 'Automatisch opnieuw proberen over ' + Math.ceil(leftMs / 1000) + 's…';
            countdownTimer = window.setInterval(function () {
                leftMs -= 500;
                if (leftMs <= 0) {
                    window.clearInterval(countdownTimer);
                    countdownTimer = null;
                } else {
                    countdownLabel.textContent = 'Automatisch opnieuw proberen over ' + Math.ceil(leftMs / 1000) + 's…';
                }
            }, 500);
            buttonRow.appendChild(countdownLabel);
        }

        if (opts.buttons) {
            for (var i = 0; i < opts.buttons.length; i++) {
                (function (b) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.textContent = b.label;
                    btn.style.cssText = 'padding:9px 16px;border-radius:9px;border:1px solid ' + (dark ? '#475569' : '#cbd5e1') + ';background:' + (b.primary ? accent : 'transparent') + ';color:' + (b.primary ? '#ffffff' : cardFg) + ';font:600 14px system-ui,sans-serif;cursor:pointer;';
                    btn.addEventListener('click', function () {
                        hideOverlay();
                        if (typeof b.onClick === 'function') {
                            b.onClick();
                        }
                    });
                    buttonRow.appendChild(btn);
                })(opts.buttons[i]);
            }
        }

        box.appendChild(buttonRow);
        dim.appendChild(box);
        document.body.appendChild(dim);
        overlayEl = dim;
    }

    function findSubmitButton() {
        var submit = document.querySelector('button[type="submit"]');
        if (submit) {
            return submit;
        }
        return document.querySelector('[wire\\:click="create"], [wire\\:click="save"], [wire\\:click="createAnother"]');
    }

    function resubmitForm() {
        var btn = findSubmitButton();
        if (!btn) {
            return false;
        }
        btn.click();
        return true;
    }

    function isFormSubmissionRequest(payload) {
        if (!payload) {
            return false;
        }
        try {
            var parsed = typeof payload === 'string' ? JSON.parse(payload) : payload;
            var components = parsed.components || [];
            for (var i = 0; i < components.length; i++) {
                var calls = components[i].calls || [];
                for (var j = 0; j < calls.length; j++) {
                    if (SUBMIT_METHODS.indexOf(calls[j].method) !== -1) {
                        return true;
                    }
                }
            }
        } catch (error) {}
        return false;
    }

    function retryNow() {
        saveDraft();
        if (resubmitForm()) {
            return;
        }
        try {
            window.sessionStorage.setItem(retryKey(pathOf()), '1');
        } catch (error) {}
        window.location.reload();
    }

    function handleSubmitFailure() {
        saveDraft();
        var attempts = parseInt(window.sessionStorage.getItem(retryKey(pathOf())) || '0', 10) + 1;
        try {
            window.sessionStorage.setItem(retryKey(pathOf()), String(attempts));
        } catch (error) {}

        if (attempts <= MAX_AUTO_RETRIES) {
            showOverlay({
                title: 'Opslaan even niet gelukt',
                body: 'De server is bezig met opstarten. Je ingevulde gegevens zijn veilig op dit apparaat bewaard. We proberen het zo automatisch opnieuw.',
                countdown: 6000,
                buttons: [
                    {
                        label: 'Nu opnieuw proberen',
                        primary: true,
                        onClick: function () {
                            hideOverlay();
                            retryNow();
                        },
                    },
                    {
                        label: 'Wachten',
                        onClick: function () {},
                    },
                ],
            });
            window.setTimeout(function () {
                if (overlayEl) {
                    hideOverlay();
                    retryNow();
                }
            }, 7000);
        } else {
            showOverlay({
                title: 'Opslaan niet gelukt',
                body: 'De server was meerdere keren niet bereikbaar. Je gegevens zijn veilig bewaard op dit apparaat — zodra de site weer werkt, worden ze automatisch hersteld en kun je opnieuw opslaan.',
                buttons: [
                    {
                        label: 'Opnieuw proberen',
                        primary: true,
                        onClick: function () {
                            hideOverlay();
                            retryNow();
                        },
                    },
                    {
                        label: 'Sluiten',
                        onClick: function () {},
                    },
                ],
            });
        }
    }

    function initRestore() {
        var path = pathOf();
        var retryNeeded = false;
        try {
            retryNeeded = parseInt(window.sessionStorage.getItem(retryKey(path)) || '0', 10) > 0;
            window.sessionStorage.removeItem(retryKey(path));
        } catch (error) {}

        var restored = restoreDraft(path);
        if (!restored) {
            return;
        }
        if (retryNeeded) {
            showToast('Je concept is hersteld. We slaan het opnieuw op…');
            window.setTimeout(function () {
                var btn = findSubmitButton();
                if (btn) {
                    btn.click();
                }
            }, 1400);
        } else {
            showToast('Je concept is hersteld.');
        }
    }

    function initLivewireHooks() {
        if (!window.Livewire) {
            return;
        }
        try {
            window.Livewire.hook('request', function (request) {
                request.succeed(function () {
                    if (isFormSubmissionRequest(request.payload)) {
                        clearDraft(pathOf());
                        try {
                            window.sessionStorage.removeItem(retryKey(pathOf()));
                        } catch (error) {}
                    }
                });
                request.fail(function (failure) {
                    var status = Number(failure && failure.status) || 0;
                    var isFormSubmit = isFormSubmissionRequest(request.payload);
                    if (status > 0 && status < 500) {
                        return;
                    }
                    if (failure && typeof failure.preventDefault === 'function') {
                        try {
                            failure.preventDefault();
                        } catch (error) {}
                    }
                    if (isFormSubmit) {
                        handleSubmitFailure();
                    } else {
                        saveDraft();
                    }
                });
            });
        } catch (error) {}
    }

    initRestore();

    function startKeepAlive() {
        function ping() {
            fetch('/keepalive?_=' + Date.now(), { cache: 'no-store', referrerPolicy: 'no-referrer' })
                .then(function () {})
                .catch(function () {});
        }
        ping();
        window.setInterval(ping, PING_INTERVAL);
    }
    startKeepAlive();

    if (window.Livewire) {
        initLivewireHooks();
    } else {
        document.addEventListener('livewire:initialized', initLivewireHooks, { once: true });
    }
})();
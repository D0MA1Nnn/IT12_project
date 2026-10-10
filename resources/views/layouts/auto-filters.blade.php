<script data-auto-filter-script>
    document.addEventListener('DOMContentLoaded', () => {
        const forms = Array.from(document.querySelectorAll('form[data-auto-filter]'))
            .filter((form) => form.method.toLowerCase() === 'get');
        const focusKey = `auto-filter-focus:${window.location.pathname}`;
        let savedFocus = null;

        try {
            savedFocus = JSON.parse(sessionStorage.getItem(focusKey));
            sessionStorage.removeItem(focusKey);
        } catch {
            // Filtering still works when browser storage is unavailable.
        }

        forms.forEach((form, formIndex) => {
            let timer;
            let submitting = false;
            let composing = false;
            const controls = Array.from(form.querySelectorAll('input[name], select[name]'));
            const isSearch = (control) => ['text', 'search'].includes(control.type);
            const submitFilters = () => {
                clearTimeout(timer);

                if (!submitting && !composing && form.checkValidity()) {
                    form.requestSubmit();
                }
            };

            form.addEventListener('submit', (event) => {
                clearTimeout(timer);

                if (submitting) {
                    event.preventDefault();
                    return;
                }

                submitting = true;
                controls.filter((control) => control.name === 'page')
                    .forEach((control) => { control.disabled = true; });

                const control = document.activeElement;

                if (controls.includes(control) && isSearch(control)) {
                    try {
                        sessionStorage.setItem(focusKey, JSON.stringify({
                            formIndex,
                            name: control.name,
                            value: control.value,
                            start: control.selectionStart,
                            end: control.selectionEnd,
                        }));
                    } catch {
                        // Do not interrupt filtering if focus cannot be saved.
                    }
                }
            });

            controls.forEach((control) => {
                if (isSearch(control)) {
                    const scheduleSearch = () => {
                        clearTimeout(timer);

                        if (!composing) {
                            timer = setTimeout(submitFilters, 500);
                        }
                    };

                    control.addEventListener('input', scheduleSearch);
                    control.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter' && !event.isComposing) {
                            event.preventDefault();
                            submitFilters();
                        }
                    });
                    control.addEventListener('compositionstart', () => {
                        composing = true;
                        clearTimeout(timer);
                    });
                    control.addEventListener('compositionend', () => {
                        composing = false;
                        scheduleSearch();
                    });
                } else if (control.tagName === 'SELECT' || control.type === 'date') {
                    control.addEventListener('change', submitFilters);
                }

                if (savedFocus?.formIndex === formIndex && savedFocus.name === control.name
                    && savedFocus.value === control.value && isSearch(control)) {
                    control.focus({ preventScroll: true });
                    control.setSelectionRange(savedFocus.start, savedFocus.end);
                }
            });

            window.addEventListener('pageshow', () => {
                clearTimeout(timer);
                submitting = false;
            });
        });
    });
</script>

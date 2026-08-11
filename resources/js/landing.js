document.documentElement.classList.add('js');

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const header = document.querySelector('[data-header]');
const menuButton = document.querySelector('[data-menu-button]');
const mobileMenu = document.querySelector('[data-mobile-menu]');
const backToTop = document.querySelector('[data-back-to-top]');
const revealElements = [...document.querySelectorAll('.reveal')];

const setMenuOpen = (isOpen) => {
    menuButton?.setAttribute('aria-expanded', String(isOpen));
    menuButton?.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
    mobileMenu?.classList.toggle('is-open', isOpen);
    document.body.classList.toggle('menu-open', isOpen);
};

menuButton?.addEventListener('click', () => {
    setMenuOpen(menuButton.getAttribute('aria-expanded') !== 'true');
});

mobileMenu?.addEventListener('click', (event) => {
    if (event.target.closest('a')) {
        setMenuOpen(false);
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && menuButton?.getAttribute('aria-expanded') === 'true') {
        setMenuOpen(false);
        menuButton.focus();
    }
});

const onScroll = () => {
    const scrollY = window.scrollY;
    header?.classList.toggle('is-scrolled', scrollY > 12);
    backToTop?.classList.toggle('is-visible', scrollY > 650);
};

onScroll();
window.addEventListener('scroll', onScroll, { passive: true });

backToTop?.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
});

if (reduceMotion || !('IntersectionObserver' in window)) {
    revealElements.forEach((element) => element.classList.add('is-visible'));
} else {
    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

    revealElements.forEach((element) => revealObserver.observe(element));
}

const modeContent = {
    clinic: {
        label: 'Clinic consultation',
        title: 'Plan an in-person visit',
        copy: 'Find nearby veterinary care and keep the appointment details close.',
        icon: 'clinic',
        previewTitle: 'Clinic consultation',
        remoteTitle: '',
        remoteCopy: '',
    },
    video: {
        label: 'Video consultation',
        title: 'See and speak with a vet online',
        copy: 'Connect face to face from a quiet place when a remote consultation is appropriate.',
        icon: 'video',
        previewTitle: 'Video consultation',
        remoteTitle: 'Meet by video',
        remoteCopy: 'Share what you are noticing and speak with a veterinary professional.',
    },
    audio: {
        label: 'Audio consultation',
        title: 'Talk through a concern',
        copy: 'Choose a focused voice consultation when video is not needed or practical.',
        icon: 'audio',
        previewTitle: 'Audio consultation',
        remoteTitle: 'Talk by audio',
        remoteCopy: 'A clear conversation for questions, follow-ups, and guidance.',
    },
    chat: {
        label: 'Chat consultation',
        title: 'Keep the conversation in writing',
        copy: 'Use chat when written details and a message history are helpful.',
        icon: 'chat',
        previewTitle: 'Chat consultation',
        remoteTitle: 'Message securely',
        remoteCopy: 'Keep questions and veterinary guidance together in one thread.',
    },
};

const modeTabs = [...document.querySelectorAll('[data-mode]')];
const modeLabel = document.querySelector('[data-mode-label]');
const modeTitle = document.querySelector('[data-mode-title]');
const modeCopy = document.querySelector('[data-mode-copy]');
const modeIcon = document.querySelector('.mode-icon use');
const previewTitle = document.querySelector('[data-preview-title]');
const clinicScene = document.querySelector('[data-preview-scene="clinic"]');
const remoteScene = document.querySelector('[data-preview-scene="remote"]');
const remoteTitle = document.querySelector('[data-remote-title]');
const remoteCopy = document.querySelector('[data-remote-copy]');
const remoteIcon = document.querySelector('.remote-symbol use');

const setMode = (mode, shouldFocus = false) => {
    const content = modeContent[mode];
    if (!content) return;

    modeTabs.forEach((tab) => {
        const isActive = tab.dataset.mode === mode;
        tab.setAttribute('aria-selected', String(isActive));
        tab.tabIndex = isActive ? 0 : -1;
        if (isActive && shouldFocus) tab.focus();
    });

    modeLabel.textContent = content.label;
    modeTitle.textContent = content.title;
    modeCopy.textContent = content.copy;
    modeIcon.setAttribute('href', `#icon-${content.icon}`);
    previewTitle.textContent = content.previewTitle;

    const isClinic = mode === 'clinic';
    clinicScene.hidden = !isClinic;
    remoteScene.hidden = isClinic;

    if (!isClinic) {
        remoteTitle.textContent = content.remoteTitle;
        remoteCopy.textContent = content.remoteCopy;
        remoteIcon.setAttribute('href', `#icon-${content.icon}`);
    }
};

modeTabs.forEach((tab, index) => {
    tab.addEventListener('click', () => setMode(tab.dataset.mode));
    tab.addEventListener('keydown', (event) => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        let nextIndex = index;
        if (event.key === 'ArrowRight') nextIndex = (index + 1) % modeTabs.length;
        if (event.key === 'ArrowLeft') nextIndex = (index - 1 + modeTabs.length) % modeTabs.length;
        if (event.key === 'Home') nextIndex = 0;
        if (event.key === 'End') nextIndex = modeTabs.length - 1;
        setMode(modeTabs[nextIndex].dataset.mode, true);
    });
});

const waitlistForm = document.querySelector('#waitlist-form');
const waitlistSuccess = document.querySelector('#waitlist-success');
const submitButton = waitlistForm?.querySelector('button[type="submit"]');
const formProgressSteps = [...document.querySelectorAll('.form-progress span')];
const formError = document.querySelector('#form-error');
const resetFormButton = document.querySelector('[data-reset-form]');

formProgressSteps.forEach((step, index) => step.dataset.step = String(index + 1));

const setFieldError = (field, message) => {
    const errorElement = document.querySelector(`#${field.id}-error`);
    field.setAttribute('aria-invalid', String(Boolean(message)));
    if (errorElement) errorElement.textContent = message;
};

const validateField = (field) => {
    if (field.id === 'name') {
        const value = field.value.trim();
        const message = !value
            ? 'Please enter your name.'
            : value.length < 2
                ? 'Please enter at least two characters.'
                : '';
        setFieldError(field, message);
        return !message;
    }

    if (field.id === 'email') {
        const value = field.value.trim();
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
        const message = !value
            ? 'Please enter your email address.'
            : !emailPattern.test(value)
                ? 'Enter an email address in the format name@example.com.'
                : '';
        setFieldError(field, message);
        return !message;
    }

    if (field.id === 'consent') {
        const message = field.checked ? '' : 'Please agree to receive launch updates.';
        setFieldError(field, message);
        return !message;
    }

    return true;
};

['name', 'email', 'consent'].forEach((id) => {
    const field = document.querySelector(`#${id}`);
    field?.addEventListener(id === 'consent' ? 'change' : 'blur', () => validateField(field));
    field?.addEventListener(id === 'consent' ? 'change' : 'input', () => {
        if (field.getAttribute('aria-invalid') === 'true') validateField(field);
    });
});

const setProgress = (step) => {
    formProgressSteps.forEach((item, index) => item.classList.toggle('active', index === step));
};

const wait = (milliseconds) => new Promise((resolve) => window.setTimeout(resolve, milliseconds));

waitlistForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    formError.hidden = true;
    formError.textContent = '';

    const fields = ['name', 'email', 'consent'].map((id) => document.querySelector(`#${id}`));
    const validationResults = fields.map((field) => validateField(field));
    const valid = validationResults.every(Boolean);

    if (!valid) {
        fields.find((field) => field.getAttribute('aria-invalid') === 'true')?.focus();
        return;
    }

    submitButton.disabled = true;
    submitButton.classList.add('is-loading');
    setProgress(1);

    await wait(reduceMotion ? 50 : 850);

    try {
        const formData = new FormData(waitlistForm);
        const request = {
            name: String(formData.get('name')).trim(),
            email: String(formData.get('email')).trim().toLowerCase(),
            interest: formData.get('interest'),
            consentedAt: new Date().toISOString(),
        };
        const savedRequests = JSON.parse(localStorage.getItem('respaw-early-access') || '[]');
        const withoutDuplicate = savedRequests.filter((item) => item.email !== request.email);
        localStorage.setItem('respaw-early-access', JSON.stringify([...withoutDuplicate, request]));

        waitlistForm.hidden = true;
        waitlistSuccess.hidden = false;
        setProgress(2);
        waitlistSuccess.focus();
    } catch {
        formError.textContent = 'We could not save your request on this device. Please email hello@respaw.in and we will help.';
        formError.hidden = false;
        setProgress(0);
    } finally {
        submitButton.disabled = false;
        submitButton.classList.remove('is-loading');
    }
});

resetFormButton?.addEventListener('click', () => {
    waitlistForm.reset();
    document.querySelector('#name').value = '';
    document.querySelector('#email').value = '';
    waitlistSuccess.hidden = true;
    waitlistForm.hidden = false;
    setProgress(0);
    document.querySelector('#name').focus();
});

const openDialogButtons = [...document.querySelectorAll('[data-dialog-open]')];
const dialogs = [...document.querySelectorAll('dialog')];

openDialogButtons.forEach((button) => {
    button.addEventListener('click', () => {
        const dialog = document.querySelector(`#${button.dataset.dialogOpen}`);
        if (!dialog) return;
        dialog.showModal();
        document.body.classList.add('dialog-open');
    });
});

dialogs.forEach((dialog) => {
    dialog.querySelector('[data-dialog-close]')?.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
        const rect = dialog.getBoundingClientRect();
        const isBackdrop = event.clientX < rect.left
            || event.clientX > rect.right
            || event.clientY < rect.top
            || event.clientY > rect.bottom;
        if (isBackdrop) dialog.close();
    });
    dialog.addEventListener('close', () => document.body.classList.remove('dialog-open'));
});

document.querySelectorAll('[data-year]').forEach((element) => {
    element.textContent = String(new Date().getFullYear());
});

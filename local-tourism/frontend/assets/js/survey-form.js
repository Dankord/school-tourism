const steps = document.querySelectorAll('.formStep');
const next = document.querySelectorAll('.next[type="button"]');
const previous = document.querySelectorAll('.previous');
const progressBar = document.getElementById('progressBar');
const form = document.getElementById('form');
const toastEl = document.getElementById('formToast');
const today = new Date().toISOString().split('T')[0];
document.getElementById('trip-date').max = today;

const formLoadedAtEl = document.getElementById('form_loaded_at');
if (formLoadedAtEl) formLoadedAtEl.value = String(Date.now());

const captchaQuestionEl = document.getElementById('captchaQuestion');
const captchaInputEl = document.getElementById('captcha_answer');
const captchaRefreshBtn = document.getElementById('captchaRefresh');
const honeypotEl = document.getElementById('website');

let currentStep = 0;

function textLeadingCharInvalid(value) {
    const text = String(value).trim();
    if (!text.length) return false;
    const char = text[0];
    return char === '-' || (char >= '0' && char <= '9');
}

function hideToast() {
    if (!toastEl) return;
    toastEl.classList.remove('form-toast--visible');
    toastEl.hidden = true;
}

function showToast(message) {
    if (!toastEl) {
        window.alert(message);
        return;
    }
    toastEl.textContent = message;
    toastEl.hidden = false;
    toastEl.classList.add('form-toast--visible');
}

if (toastEl) {
    toastEl.addEventListener('click', hideToast);
}

function trimFreeTextFields() {
    document.querySelectorAll('#text, #problems, #addition').forEach((element) => {
        if (element) element.value = element.value.trim();
    });
}

function validateLeadingCharFields() {
    const checks = [
        { id: 'text', name: 'Destination name', optional: false },
        { id: 'problems', name: 'Issues field', optional: true },
        { id: 'addition', name: 'Suggestions field', optional: true },
    ];
    for (const { id, name, optional } of checks) {
        const element = document.getElementById(id);
        if (!element) continue;
        const raw = element.value;
        if (optional && !String(raw).trim()) continue;
        if (!optional && !String(raw).trim()) continue;
        if (textLeadingCharInvalid(raw)) {
            showToast(`${name} cannot start with a number or a hyphen.`);
            // element.focus();
            return false;
        }
    }
    return true;
}

next.forEach(btn => {
    btn.addEventListener('click', () => {
        if (currentStep === 0) {
            const dest = document.getElementById('text');
            if (dest) dest.value = dest.value.trim();
            if (dest && textLeadingCharInvalid(dest.value)) {
                showToast('Destination name cannot start with a number or a hyphen.');
                dest.focus();
                return;
            }
        }

        if(currentStep < steps.length - 1) {
            currentStep++;
            showStep(currentStep);
        }
    })
})

previous.forEach(btn => {
    btn.addEventListener('click', () => {
        if(currentStep > 0) {
            currentStep--;
            showStep(currentStep);
        }
    })
})

function showStep(step) {
    steps.forEach((element, index) => {
        element.classList.toggle('active', index === step);
    })
    updateProgress();
}

function updateProgress() {
    const percent = ((currentStep + 1) / steps.length) * 100;
    progressBar.style.width = percent + "%";
}

showStep(currentStep);

const serverErrorMessages = {
    missing: 'Some required answers are missing. Please review each step.',
    invalid_start: 'Text cannot start with a number or a hyphen. Please fix the highlighted fields.',
    server: 'We could not save your survey. Please try again in a moment.',
    spam: 'Your submission was flagged as automated. Please try again.',
    too_fast: 'Please take a moment to review your answers before submitting.',
    captcha: 'The verification answer was incorrect. Please try the new question.',
    rate_fast: 'You just submitted a response. Please wait a few minutes before trying again.',
    rate_hour: 'You have submitted several responses recently. Please try again later.',
};

if (typeof URLSearchParams !== 'undefined' && toastEl) {
    const params = new URLSearchParams(window.location.search);
    const err = params.get('error');
    if (err && serverErrorMessages[err]) {
        showToast(serverErrorMessages[err]);
        const clean = window.location.pathname + window.location.hash;
        window.history.replaceState({}, '', clean);
    }
}

if (form) {
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        form.method = 'POST';
        trimFreeTextFields();
        if (!validateLeadingCharFields()) return;
        if (honeypotEl && honeypotEl.value.trim() !== '') {
            showToast(serverErrorMessages.spam);
            return;
        }
        if (captchaInputEl && captchaInputEl.value.trim() === '') {
            showToast('Please answer the verification question before submitting.');
            captchaInputEl.focus();
            return;
        }
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        form.submit();
    });
}

function loadCaptcha() {
    if (!captchaQuestionEl) return;
    captchaQuestionEl.textContent = 'Loading...';
    if (captchaInputEl) captchaInputEl.value = '';
    fetch('../../backend/api/captcha.php', { credentials: 'same-origin', cache: 'no-store' })
        .then((response) => (response.ok ? response.json() : null))
        .then((data) => {
            if (data && data.success && typeof data.question === 'string') {
                captchaQuestionEl.textContent = data.question;
            } else {
                captchaQuestionEl.textContent = 'Unavailable. Refresh and try again.';
            }
        })
        .catch(() => {
            captchaQuestionEl.textContent = 'Unavailable. Refresh and try again.';
        });
}

if (captchaRefreshBtn) {
    captchaRefreshBtn.addEventListener('click', loadCaptcha);
}

loadCaptcha();
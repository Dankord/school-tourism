const steps = document.querySelectorAll('.formStep');
const next = document.querySelectorAll('.next[type="button"]');
const previous = document.querySelectorAll('.previous');
const progressBar = document.getElementById('progressBar');
const form = document.getElementById('form');

let currentStep = 0;

next.forEach(btn => {
    btn.addEventListener('click', () => {
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

//FORCE POSTTTT
if (form) {
    form.addEventListener('submit', () => {
        // Force explicit POST PLEASE, this took me 2 HOURSSS
        form.method = 'POST';
    });
}
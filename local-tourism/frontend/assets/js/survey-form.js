const steps = document.querySelectorAll('.formStep');
const next = document.querySelectorAll('.next');
const previous = document.querySelectorAll('.previous');
const progressBar = document.getElementById('progressBar');

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
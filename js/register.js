// Practical 5 - Registration Form Real-Time Frontend Validation
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registrationForm');
    if (!form) return;

    const fullNameInput = document.getElementById('fullName');
    const emailInput = document.getElementById('email');
    const mobileInput = document.getElementById('mobile');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const courseSelect = document.getElementById('course');
    const yearSelect = document.getElementById('year');
    const termsCheck = document.getElementById('terms');
    const strengthBar = document.getElementById('passwordStrengthBar');
    const strengthText = document.getElementById('passwordStrengthText');

    // Validation Regex Patterns (matching Practical 5 specification)
    const nameRegex = /^[A-Za-z ]{2,50}$/;
    const emailRegex = /^[a-zA-Z0-9._%+-]+@charusat\.edu\.in$/i;
    const mobileRegex = /^[6-9]\d{9}$/;
    const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d).{6,}$/;

    function validateField(input, condition) {
        if (condition) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            return true;
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            return false;
        }
    }

    // Password Strength Meter
    passwordInput.addEventListener('input', () => {
        const val = passwordInput.value;
        let score = 0;
        if (val.length >= 6) score++;
        if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
        if (/\d/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        if (val.length === 0) {
            strengthBar.style.width = '0%';
            strengthBar.className = 'progress-bar';
            strengthText.textContent = '';
        } else if (score <= 1) {
            strengthBar.style.width = '33%';
            strengthBar.className = 'progress-bar bg-danger';
            strengthText.textContent = 'Weak';
            strengthText.className = 'small text-danger fw-semibold';
        } else if (score === 2 || score === 3) {
            strengthBar.style.width = '66%';
            strengthBar.className = 'progress-bar bg-warning';
            strengthText.textContent = 'Medium';
            strengthText.className = 'small text-warning fw-semibold';
        } else {
            strengthBar.style.width = '100%';
            strengthBar.className = 'progress-bar bg-success';
            strengthText.textContent = 'Strong';
            strengthText.className = 'small text-success fw-semibold';
        }

        validateField(passwordInput, passwordRegex.test(val));
    });

    // Real-Time Event Listeners
    fullNameInput.addEventListener('input', () => {
        validateField(fullNameInput, nameRegex.test(fullNameInput.value.trim()));
    });

    emailInput.addEventListener('input', () => {
        validateField(emailInput, emailRegex.test(emailInput.value.trim()));
    });

    mobileInput.addEventListener('input', () => {
        validateField(mobileInput, mobileRegex.test(mobileInput.value.trim()));
    });

    confirmPasswordInput.addEventListener('input', () => {
        validateField(confirmPasswordInput, confirmPasswordInput.value === passwordInput.value && confirmPasswordInput.value.length > 0);
    });

    courseSelect.addEventListener('change', () => {
        validateField(courseSelect, courseSelect.value !== '');
    });

    yearSelect.addEventListener('change', () => {
        validateField(yearSelect, yearSelect.value !== '');
    });

    termsCheck.addEventListener('change', () => {
        validateField(termsCheck, termsCheck.checked);
    });

    // Form Submit Handler
    form.addEventListener('submit', (e) => {
        const isNameValid = validateField(fullNameInput, nameRegex.test(fullNameInput.value.trim()));
        const isEmailValid = validateField(emailInput, emailRegex.test(emailInput.value.trim()));
        const isMobileValid = validateField(mobileInput, mobileRegex.test(mobileInput.value.trim()));
        const isPassValid = validateField(passwordInput, passwordRegex.test(passwordInput.value));
        const isConfirmValid = validateField(confirmPasswordInput, confirmPasswordInput.value === passwordInput.value && confirmPasswordInput.value.length > 0);
        const isCourseValid = validateField(courseSelect, courseSelect.value !== '');
        const isYearValid = validateField(yearSelect, yearSelect.value !== '');
        const isTermsValid = validateField(termsCheck, termsCheck.checked);

        const genderSelected = document.querySelector('input[name="gender"]:checked');
        const genderContainer = document.getElementById('genderContainer');
        let isGenderValid = true;
        if (!genderSelected) {
            isGenderValid = false;
            genderContainer.classList.add('text-danger');
        } else {
            genderContainer.classList.remove('text-danger');
        }

        if (!isNameValid || !isEmailValid || !isMobileValid || !isPassValid || !isConfirmValid || !isCourseValid || !isYearValid || !isTermsValid || !isGenderValid) {
            e.preventDefault();
            e.stopPropagation();
        }
    });
});

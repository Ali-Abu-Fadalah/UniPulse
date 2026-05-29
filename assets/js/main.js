document.addEventListener('DOMContentLoaded', function () {

    const forms = document.querySelectorAll('form');
    forms.forEach(function (form) {
        form.addEventListener('submit', function () {
            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Please wait...';
            }
        });
    });

    const inputs = document.querySelectorAll('input');
    inputs.forEach(function (input) {
        input.addEventListener('focus', function () {
            this.closest('.form-group')?.classList.add('focused');
        });
        input.addEventListener('blur', function () {
            this.closest('.form-group')?.classList.remove('focused');
        });
    });

});

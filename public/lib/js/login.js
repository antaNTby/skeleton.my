// login.js

//toggle-password
document.addEventListener('DOMContentLoaded', () => {

        document.querySelectorAll('.toggle-password').forEach(button => {
                button.addEventListener('click', function() {
                        const targetId = this.getAttribute(
                                'data-target');

                        const input = document.getElementById(targetId);
                        const icon = this.querySelector('i');

                        if(input.type === 'password') {
                                input.type = 'text';
                                icon.classList.replace('bi-eye',
                                        'bi-eye-slash');
                                button.classList.toggle('opacity-25');
                        } else {
                                input.type = 'password';
                                icon.classList.replace('bi-eye-slash',
                                        'bi-eye');
                                button.classList.toggle('opacity-25');
                        }
                });
        });
});

document.addEventListener("DOMContentLoaded", function () {
    const loginForm = document.getElementById('loginForm');
    const errorMsg = document.getElementById('errorMsg');

    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();

            if (!email || !password) {
                errorMsg.textContent = 'Please enter both email and password';
                return;
            }

            fetch('login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                credentials: 'include',
                body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
            })
            .then(response => response.json())
            .then(data => {
                console.log('Server Response:', data);
                if (data.success) {
                    sessionStorage.setItem('user_id', data.user_id);
                    sessionStorage.setItem('role', data.redirect.includes('admin') ? 'admin' : 'student');
                    window.location.href = data.redirect;
                } else {
                    errorMsg.textContent = data.message || 'Login failed';
                }
            })
            .catch(error => {
                console.error('Login error:', error);
                errorMsg.textContent = 'An error occurred during login';
            });
        });
    }
});

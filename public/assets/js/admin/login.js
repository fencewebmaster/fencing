/**
 * FC Admin — login page script.
 */
(function () {
    'use strict';

    function toast(kind, message) {
        var root = document.getElementById('toast-container');
        if (!root || !message) {
            return;
        }

        var el = document.createElement('div');
        el.className = 'fc-login-toast fc-login-toast--' + (kind === 'ok' ? 'ok' : 'error');
        el.innerHTML =
            '<i class="fas ' +
            (kind === 'ok' ? 'fa-circle-check' : 'fa-circle-exclamation') +
            '" aria-hidden="true"></i><span></span>';
        el.querySelector('span').textContent = message;
        root.appendChild(el);
        window.setTimeout(function () {
            if (el.parentNode) {
                el.parentNode.removeChild(el);
            }
        }, 4200);
    }

    function adminUrl(path) {
        var base = String((window.FC_LOGIN && window.FC_LOGIN.adminBase) || '').replace(/\/+$/, '');
        path = String(path || '').replace(/^\/+/, '');
        return path ? base + '/' + path : base + '/';
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('login-form');
        var toggleBtn = document.getElementById('toggle-password');
        var passwordInput = document.getElementById('password');
        var errorBox = document.getElementById('login-error');
        var errorText = document.getElementById('login-error-text');
        var themeToggle = document.getElementById('fc-login-theme-toggle');

        function showLoginError(message) {
            if (!message) {
                return;
            }
            errorText.textContent = message;
            errorBox.classList.remove('hidden');
            toast('error', message);
        }

        function clearLoginError() {
            errorBox.classList.add('hidden');
            errorText.textContent = '';
        }

        function showFieldError(field, message) {
            var errEl = form.querySelector('[data-error="' + field + '"]');
            var input = form.elements[field];
            if (errEl) {
                errEl.textContent = message || '';
                errEl.classList.toggle('hidden', !message);
            }
            if (input) {
                input.classList.toggle('is-error', !!message);
            }
        }

        function clearFieldErrors() {
            form.querySelectorAll('[data-error]').forEach(function (el) {
                el.textContent = '';
                el.classList.add('hidden');
            });
            form.querySelectorAll('.fc-settings-field').forEach(function (input) {
                input.classList.remove('is-error');
            });
        }

        if (themeToggle) {
            themeToggle.addEventListener('click', function () {
                var html = document.documentElement;
                var next = html.getAttribute('data-fc-admin-theme') === 'dark' ? 'light' : 'dark';
                html.setAttribute('data-fc-admin-theme', next);
                try {
                    localStorage.setItem('fc-admin-appearance', next);
                } catch (e) {
                    /* ignore */
                }
            });
        }

        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', function () {
                var isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                toggleBtn.querySelector('i').className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
            });
        }

        if (!form) {
            return;
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            clearLoginError();
            clearFieldErrors();

            var username = String(form.username.value || '').trim();
            var password = String(form.password.value || '');

            if (!username) {
                showFieldError('username', 'Username is required.');
                showLoginError('Please enter your username.');
                form.username.focus();
                return;
            }
            if (!password) {
                showFieldError('password', 'Password is required.');
                showLoginError('Please enter your password.');
                form.password.focus();
                return;
            }

            var btn = document.getElementById('login-btn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Signing in...';

            var cfg = window.FC_LOGIN || {};
            fetch(cfg.api || 'api.php?module=auth&action=login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    username: username,
                    password: password,
                    remember: !!(form.remember && form.remember.checked),
                    csrf_token: cfg.csrf || form.csrf_token.value
                })
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { res: res, data: data };
                    });
                })
                .then(function (result) {
                    var data = result.data || {};
                    if (data.ok) {
                        clearLoginError();
                        toast('ok', data.message || 'Login successful!');
                        var redirect = '';
                        if (cfg.redirect) {
                            redirect = cfg.redirect;
                        } else if (data.redirect) {
                            redirect = adminUrl(data.redirect);
                        } else {
                            redirect = adminUrl('dashboard');
                        }
                        window.setTimeout(function () {
                            window.location.href = redirect;
                        }, 700);
                        return;
                    }

                    var message = data.message || 'Login failed. Please check your credentials.';
                    showLoginError(message);
                    if (data.errors) {
                        Object.keys(data.errors).forEach(function (key) {
                            showFieldError(key, data.errors[key]);
                        });
                    } else {
                        showFieldError('password', message);
                    }
                })
                .catch(function () {
                    showLoginError('Connection error. Please check your network and try again.');
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-arrow-right-to-bracket"></i> Sign in';
                });
        });

        form.querySelectorAll('input').forEach(function (input) {
            input.addEventListener('input', function () {
                clearLoginError();
                showFieldError(input.name, '');
            });
        });
    });
})();

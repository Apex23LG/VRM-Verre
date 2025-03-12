document.addEventListener("DOMContentLoaded", function () {
    const proxyDashboard = document.getElementById("proxy-dashboard");
    const fullForm = document.getElementById("proxy-auth-form");

    const loadingOverlay = document.getElementById("loading-overlay");
    loadingOverlay.style.display = "flex";

    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }
    const proxyToken = getCookie("proxy_token");

    if (proxyToken) {
        fetch(proxyAjax.ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=validate_proxy_token&proxy_token=${encodeURIComponent(proxyToken)}`,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                proxyDashboard.style.display = "block";
                fullForm.style.display = "none";

                fetchBatteryData(proxyToken, 1);
            } else {
                fullForm.style.display = "block";
                proxyDashboard.style.display = "none";
            }
        })
        .catch(error => {
            console.error("Errore durante la validazione del token:", error);
            fullForm.style.display = "block";
            proxyDashboard.style.display = "none";
        })
        .finally(() => {
            loadingOverlay.style.display = "none"; // Nascondi l'overlay
        });
    } else {
        fullForm.style.display = "block";
        proxyDashboard.style.display = "none";
        loadingOverlay.style.display = "none";
    }

    async function fetchBatteryData(proxyToken, idSite) {
        try {
            const response = await fetch(proxyAjax.ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=proxy_get_battery_data&proxy_token=${encodeURIComponent(proxyToken)}a&idSite=${encodeURIComponent(idSite)}`,
            });

            const data = await response.json();

            if (data.success) {
                console.log(data.data);
                displayBatteryData(data.data);
            } else {
                console.error("Errore nei dati della batteria:", data.message);
            }
        } catch (error) {
            console.error("Errore durante il recupero dei dati della batteria:", error);
        } finally {
            loadingOverlay.style.display = "none"; // Nascondi l'overlay
        }
    }

    function displayBatteryData(data) {
        const container = document.getElementById("vrm-data");

        const html = `
            <p>Stato di carica (SOC): ${data.data.soc}%</p>
            <p>Tensione: ${data.data.voltage} V</p>
            <p>Corrente: ${data.data.current} A</p>
            <p>Potenza: ${data.data.power} W</p>
            <p>Consumo: ${data.data.consumedAh} Ah</p>
            <p>Tempo rimanente: ${data.data.timeToGo} secondi</p>
            <p>Allarme: ${data.data.alarm ? 'Attivo' : 'Inattivo'}</p>
            <p>Motivo allarme: ${data.data.alarmReason || 'Nessuno'}</p>
            <p>Temperatura: ${data.data.temperature} °C</p>
        `;

        container.innerHTML = html;
    }
});

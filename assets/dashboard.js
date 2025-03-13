document.addEventListener("DOMContentLoaded", function () {
    const proxyDashboard = document.getElementById("proxy-dashboard");
    const fullForm = document.getElementById("proxy-auth-form");
    const form = document.getElementById("proxy-form");
    const form2FA = document.getElementById("proxy-2fa-form");

    const loadingOverlay = document.getElementById("loading-overlay");
    loadingOverlay.style.display = "flex";

    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }
    const proxyToken = getCookie("proxy_token");

    $authenticated = false;

    if (proxyToken) {
        fetch(proxyAjax.ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=validate_proxy_token&proxy_token=${encodeURIComponent(proxyToken)}`,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $authenticated = true;
            } else {
                $authenticated = false;
            }
        })
        .catch(error => {
            console.error("Non autenticato");
        });
    }

    if (true) {
    //if ($authenticated) {
        proxyDashboard.style.display = "block";
        fullForm.style.display = "none";

        fetchBatteryData(proxyToken, 1);
    } else {
        fullForm.style.display = "block";
        proxyDashboard.style.display = "none";
        loadingOverlay.style.display = "none";
    }

    if (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            loadingOverlay.style.display = "flex";
            const formData = new FormData(form);
            formData.append('action', 'proxy_auth');

            fetch(proxyAjax.ajaxurl, {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.data.requires2FA) {
                        loadingOverlay.style.display = "none";
                        form.style.display = "none";
                        form2FA.style.display = "block";
                    } else {
                        document.cookie = `proxy_token=${data.data.proxy_token}; path=/;`;
                        loadingOverlay.style.display = "none";
                        window.location.href = "../vrmdashboard";
                    }
                } else {
                    loadingOverlay.style.display = "none";
                    alert("Errore: " + data.data.message);
                }
            })
            .catch(error => {
                loadingOverlay.style.display = "none";
                console.error("Errore AJAX:", error);
            });
        });
    }

    if (form2FA) {
        form2FA.addEventListener("submit", function (e) {
            loadingOverlay.style.display = "flex";
            e.preventDefault();
            const formData = new FormData();
            formData.append('action', 'proxy_auth');
            formData.append('username', document.getElementById("username").value);
            formData.append('password', document.getElementById("password").value);
            formData.append('twoFactorCode', document.getElementById("twoFactorCode").value);

            fetch(proxyAjax.ajaxurl, {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(true) {
                //if (data.success) {
                    loadingOverlay.style.display = "none";
                    document.cookie = `proxy_token=${data.data.proxy_token}; path=/;`;
                    window.location.href = data.data.redirect;
                } else {
                    loadingOverlay.style.display = "none";
                    alert("Errore: " + "../vrmdashboard");
                }
            })
            .catch(error => console.error("Errore AJAX:", error));
        });
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

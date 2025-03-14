document.addEventListener("DOMContentLoaded", function () {
    const proxyDashboard = document.getElementById("proxy-dashboard");
    const fullForm = document.getElementById("proxy-auth-form");
    const form = document.getElementById("proxy-form");
    const loadingOverlay = document.getElementById("loading-overlay");
    const logoutButton = document.getElementById("logout-btn");
    let refreshInterval;
    loadingOverlay.style.display = "flex";

    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }

    async function checkAuthentication() {
        const proxyToken = getCookie("proxy_token");
        if (proxyToken) {
            try {
                const response = await fetch(proxyAjax.ajaxurl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=validate_proxy_token&proxy_token=${encodeURIComponent(proxyToken)}`,
                });

                const data = await response.json();
                return data.valid;
            } catch (error) {
                console.error("Non autenticato", error);
                return false;
            }
        }
        return false;
    }

    async function runApp() {
        const isAuthenticated = await checkAuthentication();

        if (isAuthenticated) {
            proxyDashboard.style.display = "block";
            fullForm.style.display = "none";
            const proxyToken = getCookie("proxy_token");
            fetchBatteryData(proxyToken, 1);

            refreshInterval = setInterval(() => {
                fetchBatteryData(proxyToken, 1);
            }, 2000);

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
                        loadingOverlay.style.display = "none";
                        window.location.href = "../vrmdashboard";
                    } else {
                        loadingOverlay.style.display = "none";
                        alert("Errore: Login non riuscito, verifica le credenziali e riprova.");
                    }
                })
                .catch(error => {
                    loadingOverlay.style.display = "none";
                    console.error("Errore AJAX:", error);
                });
            });
        }

        if (logoutButton) {
            logoutButton.addEventListener("click", function (e) {
                e.preventDefault();
                const proxyToken = getCookie("proxy_token");
                fetch(proxyAjax.ajaxurl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=proxy_logout&proxy_token=${encodeURIComponent(proxyToken)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log("Successo:", data.message);
                        clearInterval(refreshInterval);
                        document.cookie = " proxy_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                        window.location.href = "../vrmdashboard";
                    } else {
                        alert("Errore: " + data.message);
                    }
                })
                .catch(error => {
                    console.error("Errore AJAX:", error);
                });
            });
        }
    }

    async function fetchBatteryData(proxyToken, idSite) {
        console.log(proxyToken);
            fetch(proxyAjax.ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=proxy_get_battery_data&proxy_token=${encodeURIComponent(proxyToken)}&idSite=${encodeURIComponent(idSite)}`,
            }).then(response => response.json())
            .then(async data => {
                console.log(data.data);
                if (data.success) {
                    await displayBatteryData(data.data.data);
                    loadingOverlay.style.display = "none";
                } else {
                    document.cookie = " proxy_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                    clearInterval(refreshInterval);
                    window.location.href = "../vrmdashboard";
                    loadingOverlay.style.display = "none";

                }
            })
             .catch (error => {
            console.error("Errore durante il recupero dei dati della batteria:", error);
            loadingOverlay.style.display = "none";
            });
        
    }

    async function displayBatteryData(data) {
        const container = document.getElementById("vrm-data");
        console.log(data);
    
        try {
            // Fetch the HTML from the WordPress AJAX endpoint
            const response = await fetch(proxyAjax.ajaxurl + '?action=proxy_vrm_data_template');
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
    
            let html = await response.text();
            
            // Replace placeholders with actual data in the HTML
            html = html
                .replace(/{{soc}}/g, data.soc)
                .replace(/{{voltage}}/g, data.voltage)
                .replace(/{{current}}/g, data.current)
                .replace(/{{power}}/g, data.power)
                .replace(/{{consumedAh}}/g, data.consumedAh)
                .replace(/{{timeToGo}}/g, data.timeToGo)
                .replace(/{{temperature}}/g, data.temperature);
    
            // Insert the modified HTML into the container
            container.innerHTML = html;
    
            // Handle scripts separately
            const scripts = container.querySelectorAll('script');
            scripts.forEach(script => {
                const newScript = document.createElement('script');
                if (script.src) {
                    // If the script has a src attribute, load it
                    newScript.src = script.src;
                } else {
                    // If the script is inline, replace placeholders in its content
                    let scriptContent = script.textContent;
                    scriptContent = scriptContent
                    .replace(/{{soc}}/g, data.soc)
                    .replace(/{{voltage}}/g, data.voltage)
                    .replace(/{{current}}/g, data.current)
                    .replace(/{{power}}/g, data.power)
                    .replace(/{{consumedAh}}/g, data.consumedAh)
                    .replace(/{{timeToGo}}/g, data.timeToGo)
                    .replace(/{{temperature}}/g, data.temperature);
                    newScript.textContent = scriptContent;
                }
                document.body.appendChild(newScript);
            });
    
        } catch (error) {
            console.error("Error loading battery data template:", error);
        }
    }
        /*

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
    */

    runApp();
});



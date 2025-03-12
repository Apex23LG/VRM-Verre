document.addEventListener("DOMContentLoaded", function () {
    const authButton = document.getElementById("proxy-auth-button");
    const form = document.getElementById("proxy-form");
    const form2FA = document.getElementById("proxy-2fa-form");
    const proxyDashboard = document.getElementById("proxy-dashboard");
    const fullForm = document.getElementById("proxy-auth-form");

    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }
    const proxyToken = getCookie("proxy_token");
    
    if (authButton) {
        authButton.addEventListener("click", function () {
            window.location.href = "../vrmdashboard";
        });
    }

    // Gestione del submit del form iniziale (username e password)
    if (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault();
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
                        // Nascondi il form iniziale e mostra il form 2FA
                        form.style.display = "none";
                        form2FA.style.display = "block";
                    } else {
                        // Login riuscito, reindirizza
                        document.cookie = `proxy_token=${data.data.proxy_token}; path=/;`;
                        window.location.href = data.data.redirect;
                    }
                } else {
                    alert("Errore: " + data.data.message);
                }
            })
            .catch(error => console.error("Errore AJAX:", error));
        });
    }

    if (form2FA) {
        form2FA.addEventListener("submit", function (e) {
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
                if (data.success) {
                    // Login riuscito, reindirizza
                    document.cookie = `proxy_token=${data.data.proxy_token}; path=/;`;
                    window.location.href = data.data.redirect;
                } else {
                    alert("Errore: " + data.data.message);
                }
            })
            .catch(error => console.error("Errore AJAX:", error));
        });
    }
    
    if (proxyToken&&window.location.pathname.endsWith("/vrmdashboard")) {
        fetch(proxyAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=validate_proxy_token&proxy_token=${encodeURIComponent(proxyToken)}`,
        })
        .then(response => response.json())
        .then(data => {
            if (data.valid) {
                proxyDashboard.style.display = "block";
                fullForm.style.display = "none";
            } else {
                fullForm.style.display = "block";
                proxyDashboard.style.display = "none";
            }
        })
        .catch(error => {
            console.error("Errore durante la validazione del token:", error);
            fullForm.style.display = "block";
            proxyDashboard.style.display = "none";

        });
    } else {
        fullForm.style.display = "block";
        proxyDashboard.style.display = "none";


    }

    
});
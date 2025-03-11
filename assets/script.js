document.addEventListener("DOMContentLoaded", function () {
    const authButton = document.getElementById("proxy-auth-button");
    const overlay = document.getElementById("proxy-auth-overlay");
    const closeButton = document.getElementById("proxy-auth-close");
    const form = document.getElementById("proxy-form");
    const form2FA = document.getElementById("proxy-2fa-form");
    const background = document.getElementById("proxy-auth-background");

    // Mostra l'overlay quando si clicca il pulsante "Apri il form"
    if (authButton && overlay) {
        authButton.addEventListener("click", function () {
            overlay.style.display = "flex";
            form.style.display = "flex";
            form2FA.style.display = "none";
        });
    }

    // Nasconde l'overlay quando si clicca la X o fuori dal box
    if (closeButton && overlay) {
        closeButton.addEventListener("click", function () {
            overlay.style.display = "none";
        });
    }
    if (background) {
        background.addEventListener("click", function () {
            overlay.style.display = "none";
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

    // Gestione del submit del form 2FA
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
});
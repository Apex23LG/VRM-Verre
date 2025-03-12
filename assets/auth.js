document.addEventListener("DOMContentLoaded", function () {
    const authButton = document.getElementById("proxy-auth-button");
    const form = document.getElementById("proxy-form");
    const form2FA = document.getElementById("proxy-2fa-form");

    if (authButton) {
        authButton.addEventListener("click", function () {
            window.location.href = "../vrmdashboard";
        });
    }

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
                        form.style.display = "none";
                        form2FA.style.display = "block";
                    } else {
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
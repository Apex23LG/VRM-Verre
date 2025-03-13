document.addEventListener("DOMContentLoaded", function () {
    const authButton = document.getElementById("proxy-auth-button");
    

    if (authButton) {
        authButton.addEventListener("click", function () {
            window.location.href = "../vrmdashboard";
        });
    }

    
});
// ===============================
// General front-end interactivity
// ===============================

// Auto-hide flash alerts after 4s
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.auto-dismiss').forEach(function (el) {
        setTimeout(function () {
            const alert = bootstrap.Alert.getOrCreateInstance(el);
            alert.close();
        }, 4000);
    });
});

// Payment method visual toggle
function selectPayOption(el, radioId) {
    document.querySelectorAll('.pay-option').forEach(o => o.classList.remove('active'));
    el.classList.add('active');
    document.getElementById(radioId).checked = true;
}

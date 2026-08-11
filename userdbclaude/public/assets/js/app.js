/* =====================================================================
   ECOWASmail Admin — Interactions front-end (vanilla JS, sans dépendance)
   ===================================================================== */
(function () {
    "use strict";

    /* ---- Thème clair / sombre (persistant) ---- */
    (function () {
        var root = document.documentElement;
        var btn = document.getElementById("themeToggle");
        if (!btn) return;

        function syncIcon() {
            // On reconstruit l'élément <i> car Lucide remplace <i> par <svg>
            // au rendu : un simple querySelector('i') échouerait au 2ᵉ clic.
            var isDark = root.getAttribute("data-theme") === "dark";
            btn.innerHTML = '<i data-lucide="' + (isDark ? "sun" : "moon") + '"></i>';
        }
        syncIcon(); // avant le lucide.createIcons() du layout

        btn.addEventListener("click", function () {
            var next = root.getAttribute("data-theme") === "dark" ? "light" : "dark";
            root.setAttribute("data-theme", next);
            try { localStorage.setItem("theme", next); } catch (e) {}
            syncIcon();
            if (window.lucide) lucide.createIcons();
        });
    })();

    /* ---- Fermeture des messages flash ---- */
    document.addEventListener("click", function (e) {
        if (e.target.classList.contains("flash__close")) {
            e.target.closest(".flash").remove();
        }
    });

    /* ---- Auto-disparition des flash de succès après 5 s ---- */
    document.querySelectorAll(".flash--success").forEach(function (el) {
        setTimeout(function () {
            el.style.transition = "opacity .4s";
            el.style.opacity = "0";
            setTimeout(() => el.remove(), 400);
        }, 5000);
    });

    /* ---- Filtre instantané de la page affichée (côté client) ---- */
    var liveFilter = document.getElementById("liveFilter");
    if (liveFilter) {
        liveFilter.addEventListener("keyup", function () {
            var q = this.value.toLowerCase();
            document.querySelectorAll("#usersTable tbody tr").forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().indexOf(q) > -1 ? "" : "none";
            });
        });
    }

    /* ---- Confirmation de suppression via modale ---- */
    var deleteForm = document.getElementById("deleteForm");
    var deleteInput = document.getElementById("deleteUserid");

    document.querySelectorAll(".js-delete").forEach(function (btn) {
        btn.addEventListener("click", function () {
            var id = this.dataset.id;
            var nom = this.dataset.nom || "cet utilisateur";
            openConfirm(
                "Supprimer l'utilisateur ?",
                "« " + nom + " » sera définitivement supprimé. Cette action est irréversible.",
                function () {
                    deleteInput.value = id;
                    deleteForm.submit();
                }
            );
        });
    });

    /* ---- Modale générique ---- */
    function openConfirm(title, message, onConfirm) {
        var overlay = document.createElement("div");
        overlay.className = "modal-overlay";
        overlay.innerHTML =
            '<div class="modal" role="dialog" aria-modal="true">' +
            '  <div class="modal__icon"><i data-lucide="trash-2"></i></div>' +
            '  <h3></h3><p></p>' +
            '  <div class="modal__actions">' +
            '    <button class="btn btn--ghost" data-cancel>Annuler</button>' +
            '    <button class="btn btn--danger" data-ok>Supprimer</button>' +
            '  </div>' +
            "</div>";
        overlay.querySelector("h3").textContent = title;
        overlay.querySelector("p").textContent = message;
        document.body.appendChild(overlay);
        if (window.lucide) lucide.createIcons();

        function close() { overlay.remove(); }
        overlay.addEventListener("click", function (e) {
            if (e.target === overlay || e.target.hasAttribute("data-cancel")) close();
        });
        overlay.querySelector("[data-ok]").addEventListener("click", function () {
            close();
            onConfirm();
        });
        document.addEventListener("keydown", function esc(e) {
            if (e.key === "Escape") { close(); document.removeEventListener("keydown", esc); }
        });
    }

    /* ---- Validation simple du formulaire utilisateur ---- */
    var userForm = document.getElementById("userForm");
    if (userForm) {
        userForm.addEventListener("submit", function (e) {
            var uname = userForm.querySelector('[name="uname"]');
            if (uname && uname.value.trim() === "") {
                e.preventDefault();
                alert("Le champ Username ne doit pas être vide.");
                uname.focus();
            }
        });
    }
})();

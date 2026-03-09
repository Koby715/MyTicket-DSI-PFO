// Override du comportement du bouton "Résoudre" pour afficher la modal de rapport
document.addEventListener('DOMContentLoaded', function () {
    console.log('Resolution report script loaded');

    // Créer l'instance de la modal
    const reportModalElement = document.getElementById('reportResolutionModal');
    if (!reportModalElement) {
        console.error('Modal reportResolutionModal not found!');
        return;
    }

    const reportModal = new bootstrap.Modal(reportModalElement);

    // Réinitialiser l'état du bouton à l'ouverture pour éviter le blocage après une action précédente
    reportModalElement.addEventListener('show.bs.modal', function () {
        const submitBtn = document.getElementById('submitReportBtn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="ti ti-check me-1"></i> Confirmer la résolution';
        }
        // Cacher les anciennes erreurs
        const errorAlert = document.getElementById('report-error-alert');
        if (errorAlert) errorAlert.classList.add('d-none');
    });

    // Utiliser la délégation d'événements pour intercepter le bouton "Résoudre"
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-quick-action[data-action="resolve"]');
        if (!btn) return;

        console.log('Resolve button intercepted via delegation');

        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        const ticketId = btn.dataset.id;
        const row = btn.closest('tr');
        const refElement = row?.querySelector('.text-primary');
        const ticketRef = refElement ? refElement.textContent.trim() : '#N/A';

        document.getElementById('report-ticket-id').value = ticketId;
        document.getElementById('report-ticket-ref').textContent = ticketRef;
        document.getElementById('report-content').value = '';
        document.getElementById('report-attachment').value = '';

        reportModal.show();
    }, true);

    // Gestion de la soumission du rapport
    const submitBtn = document.getElementById('submitReportBtn');
    if (submitBtn) {
        submitBtn.addEventListener('click', function () {
            const form = document.getElementById('reportResolutionForm');
            const ticketId = document.getElementById('report-ticket-id').value;
            const reportContent = document.getElementById('report-content').value.trim();
            const fileInput = document.getElementById('report-attachment');
            
            // Éléments UI pour les messages d'erreur
            const errorAlert = document.getElementById('report-error-alert');
            const errorMessage = document.getElementById('report-error-message');
            
            // Fonction utilitaire pour afficher une erreur proprement
            const showError = (msg) => {
                if (errorAlert && errorMessage) {
                    errorMessage.textContent = msg;
                    errorAlert.classList.remove('d-none');
                    // Scroll automatique vers l'erreur dans la modal
                    errorAlert.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    Swal.fire('Erreur', msg, 'warning');
                }
            };

            // Cacher les anciennes erreurs
            if (errorAlert) errorAlert.classList.add('d-none');

            // Validation du commentaire
            if (reportContent.length < 10) {
                showError('Le commentaire doit contenir au moins 10 caractères.');
                return;
            }

            // Validation du fichier (si présent)
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const maxSize = 5 * 1024 * 1024; // 5 Mo
                const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];

                if (file.size > maxSize) {
                    showError('Le fichier est trop volumineux (Max 5Mo).');
                    return;
                }

                if (!allowedTypes.includes(file.type)) {
                    showError('Format de fichier non autorisé (PDF, JPG, PNG uniquement).');
                    return;
                }
            }

            // --- ÉTAPE 2 : GESTION DE LA LATENCE (Feedback immédiat) ---

            // Désactivation du bouton et affichage d'un spinner
            const originalBtnHtml = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Envoi...';

            // Préparation des données
            const formData = new FormData(form);

            // Envoi AJAX
            fetch('submit-resolution-report.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Réactiver le bouton immédiatement
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnHtml;

                        // Fermeture immédiate de la modal Bootstrap pour éviter les conflits de Z-index
                        const modalElem = document.getElementById('reportResolutionModal');
                        const bsModal = bootstrap.Modal.getInstance(modalElem) || new bootstrap.Modal(modalElem);
                        bsModal.hide();

                        // Affichage du succès APRÈS fermeture
                        Swal.fire({
                            icon: 'success',
                            title: 'Succès !',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            // Mise à jour dynamique du statut dans le tableau
                            const row = document.querySelector(`.ticket-row[data-id="${ticketId}"]`);
                            if (row) {
                                const statusBadge = row.querySelector('.status-badge');
                                if (statusBadge) {
                                    statusBadge.textContent = 'Résolu';
                                    statusBadge.className = 'status-badge bg-light-success text-success';
                                }
                                row.style.backgroundColor = 'rgba(40, 167, 69, 0.05)';
                            }
                            // Si la fonction existe dans le scope global (admin-dashboard-new.php)
                            if (typeof refreshKPIs === 'function') {
                                refreshKPIs();
                            }
                        });
                    } else {
                        // Réactivation du bouton en cas d'erreur serveur
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnHtml;
                        showError(data.message || 'Une erreur est survenue sur le serveur.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                    showError('Échec de la communication réseau avec le serveur.');
                });
        });
    }
});

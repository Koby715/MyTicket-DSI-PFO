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

            // Validation du commentaire
            if (reportContent.length < 10) {
                Swal.fire('Erreur', 'Le commentaire doit contenir au moins 10 caractères.', 'warning');
                return;
            }

            // Validation du fichier (si présent)
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const maxSize = 5 * 1024 * 1024; // 5 Mo
                const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];

                if (file.size > maxSize) {
                    Swal.fire('Erreur', 'Le fichier est trop volumineux. Taille maximale : 5 Mo.', 'warning');
                    return;
                }

                if (!allowedTypes.includes(file.type)) {
                    Swal.fire('Erreur', 'Type de fichier non autorisé. Formats acceptés : PDF, JPG, PNG.', 'warning');
                    return;
                }
            }

            // Préparation des données
            const formData = new FormData(form);

            // Afficher un loader
            Swal.fire({
                title: 'Soumission en cours...',
                text: 'Veuillez patienter',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            // Envoi AJAX
            fetch('submit-resolution-report.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        reportModal.hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Succès !',
                            text: data.message,
                            timer: 2500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Erreur', data.message, 'error');
                    }
                })
                .catch(error => {
                    Swal.close();
                    console.error('Error:', error);
                    Swal.fire('Erreur', 'Une erreur est survenue lors de la soumission du rapport.', 'error');
                });
        });
    }
});

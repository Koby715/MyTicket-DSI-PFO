<?php
/**
 * liste-tickets-user.php
 * Affiche tous les tickets d'un utilisateur authentifié via token
 */

require_once 'config/db.php';

// ============= AUTHENTIFICATION PAR TOKEN =============

$userEmail = null;
$authError = null;
$dataError = null;

// Récupérer le token depuis l'URL
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($token)) {
    $authError = "Lien invalide. Aucun token fourni.";
} else {
    try {
        // Vérifier si le token existe dans la base de données
        $stmt = $pdo->prepare("SELECT email FROM tickets WHERE token = ? LIMIT 1");
        $stmt->execute([$token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $userEmail = $result['email'];
        } else {
            $authError = "Lien invalide ou expiré.";
        }
    } catch (PDOException $e) {
        $authError = "Erreur lors de la vérification du token: " . $e->getMessage();
        error_log("Erreur DB Token: " . $e->getMessage());
    }
}

// ============= RÉCUPÉRATION DES TICKETS =============

$tickets = [];
$totalTickets = 0;
$ticketsPerPage = 15;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $ticketsPerPage;

if ($userEmail) {
    try {
        // Compter le nombre total de tickets
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tickets WHERE email = ?");
        $stmt->execute([$userEmail]);
        $totalTickets = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Calculer le nombre total de pages
        $totalPages = ceil($totalTickets / $ticketsPerPage);
        
        // Récupérer les tickets avec pagination
        $stmt = $pdo->prepare("
            SELECT 
                id,
                reference,
                subject,
                description,
                category_id,
                priority_id,
                status_id,
                created_at,
                updated_at
            FROM tickets
            WHERE email = ?
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $userEmail, PDO::PARAM_STR);
        $stmt->bindValue(2, $ticketsPerPage, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Enrichir chaque ticket avec les noms des catégories, priorités et statuts
        foreach ($tickets as &$ticket) {
            // Nettoyer la description des balises HTML
            $ticket['description'] = strip_tags($ticket['description']);
            
            // Récupérer le nom de la catégorie
            try {
                $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
                $stmt->execute([$ticket['category_id']]);
                $cat = $stmt->fetch(PDO::FETCH_ASSOC);
                $ticket['category_name'] = $cat['name'] ?? 'N/A';
            } catch (PDOException $e) {
                $ticket['category_name'] = 'N/A';
            }
            
            // Récupérer le nom de la priorité
            try {
                $stmt = $pdo->prepare("SELECT name FROM priorities WHERE id = ?");
                $stmt->execute([$ticket['priority_id']]);
                $pri = $stmt->fetch(PDO::FETCH_ASSOC);
                $ticket['priority_name'] = $pri['name'] ?? 'N/A';
            } catch (PDOException $e) {
                $ticket['priority_name'] = 'N/A';
            }
            
            // Récupérer le label du statut (pas 'name')
            try {
                $stmt = $pdo->prepare("SELECT label FROM statuses WHERE id = ?");
                $stmt->execute([$ticket['status_id']]);
                $sta = $stmt->fetch(PDO::FETCH_ASSOC);
                $ticket['status_name'] = $sta['label'] ?? 'N/A';
            } catch (PDOException $e) {
                $ticket['status_name'] = 'N/A';
            }
            
            // Couleurs pour les priorités
            $priorityColors = [
                'Faible' => '#10b981',
                'Moyenne' => '#f59e0b',
                'Urgent' => '#ef4444',
                'Élevée' => '#dc2626',
                'Haute' => '#dc2626'
            ];
            $ticket['priority_color'] = $priorityColors[$ticket['priority_name']] ?? '#6c757d';
            
            // Couleurs pour les statuts
            $statusColors = [
                'Nouveau' => '#3b82f6',
                'En cours' => '#f59e0b',
                'Résolu' => '#10b981',
                'Fermé' => '#6c757d',
                'En attente' => '#8b5cf6'
            ];
            $ticket['status_color'] = $statusColors[$ticket['status_name']] ?? '#3b82f6';
        }
    } catch (PDOException $e) {
        $dataError = "Erreur lors de la récupération des tickets: " . $e->getMessage();
        error_log("Erreur DB Tickets: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<!-- [Head] start -->

<head>
  <title>Mes Tickets - PFO DSI</title>
  <!-- [Meta] -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <!-- [Favicon] icon -->
  <link rel="icon" href="assets/img/logo/favicon-pfo.png" type="image/x-icon"> 

  <!-- [Google Font] Family -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" id="main-font-link">
  <!-- [Tabler Icons] https://tablericons.com -->
  <link rel="stylesheet" href="../assets/fonts/tabler-icons.min.css" >
  <!-- [Feather Icons] https://feathericons.com -->
  <link rel="stylesheet" href="../assets/fonts/feather.css" >
  <!-- [Font Awesome Icons] https://fontawesome.com/icons -->
  <link rel="stylesheet" href="../assets/fonts/fontawesome.css" >
  <!-- [Material Icons] https://fonts.google.com/icons -->
  <link rel="stylesheet" href="../assets/fonts/material.css" >
  <!-- [Template CSS Files] -->
  <link rel="stylesheet" href="../assets/css/style.css" id="main-style-link" >
  <link rel="stylesheet" href="../assets/css/style-preset.css" >

</head>
<!-- [Head] end -->
<!-- [Body] Start -->

<body data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light">
  <!-- [ Pre-loader ] start -->
<div class="loader-bg">
  <div class="loader-track">
    <div class="loader-fill"></div>
  </div>
</div>
<!-- [ Pre-loader ] End -->

<!-- [ Header Simple ] start -->
<header style="background: white; border-bottom: 1px solid #e5e7eb; padding: 1rem 2rem; position: sticky; top: 0; z-index: 1000;">
  <div style="display: flex; align-items: center; justify-content: space-between; max-width: 1400px; margin: 0 auto;">
    <div style="display: flex; align-items: center; gap: 1rem;">
      <a href="../../index.php">
        <img src="assets/img/logo/monimage.png" alt="logo" style="max-height:40px; width:auto;">
      </a>
    </div>
  </div>
</header>
<!-- [ Header ] end -->

  <!-- [ Main Content ] start -->
  <div style="padding: 2rem; max-width: 1400px; margin: 0 auto;">
      

      <!-- [ Main Content ] start -->

      <?php if ($authError): ?>
        <!-- Erreur d'authentification -->
        <div class="row">
          <div class="col-sm-12">
            <div class="card">
              <div class="card-body text-center py-5">
                <div class="mb-4">
                  <i class="ti ti-alert-circle" style="font-size: 80px; color: #ef4444;"></i>
                </div>
                <h3 class="mb-3"><?= htmlspecialchars($authError) ?></h3>
                <p class="text-muted mb-4">Veuillez vérifier le lien reçu par email ou créer un nouveau ticket.</p>
                <a href="ajout-ticket.php" class="btn btn-primary">
                  <i class="ti ti-plus me-2"></i> Créer un Ticket
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php elseif ($dataError): ?>
        <!-- Erreur de récupération des données -->
        <div class="row">
          <div class="col-sm-12">
            <div class="card">
              <div class="card-body text-center py-5">
                <div class="mb-4">
                  <i class="ti ti-database-off" style="font-size: 80px; color: #f59e0b;"></i>
                </div>
                <h3 class="mb-3">Erreur de base de données</h3>
                <div class="alert alert-warning text-start">
                  <strong>Détails techniques :</strong><br>
                  <?= htmlspecialchars($dataError) ?>
                </div>
                <p class="text-muted mb-4">Email authentifié : <strong><?= htmlspecialchars($userEmail) ?></strong></p>
                <a href="ajout-ticket.php" class="btn btn-primary">
                  <i class="ti ti-plus me-2"></i> Créer un Ticket
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php else: ?>
        <!-- Affichage des tickets -->
        <div class="row">
          <div class="col-sm-12">
            <div class="card">
              <div class="card-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                  <div>
                    <h4>Tickets de la page : <?= count($tickets) ?> / Total : <?= $totalTickets ?></h4>
                    <p class="text-muted mb-0">Email: <strong><?= htmlspecialchars($userEmail) ?></strong></p>
                  </div>
                  <div style="display: flex; gap: 0.5rem;">
                    <a href="../../index.php" class="btn btn-secondary btn-sm">
                      <i class="ti ti-home me-1"></i> Retour Accueil
                    </a>
                    <a href="ajout-ticket.php" class="btn btn-primary btn-sm">
                      <i class="ti ti-plus me-1"></i> Nouveau Ticket
                    </a>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <?php if (empty($tickets)): ?>
                  <div class="text-center py-5">
                    <i class="ti ti-inbox" style="font-size: 60px; color: #ccc;"></i>
                    <p class="text-muted mt-3">Aucun ticket trouvé pour cet email.</p>
                    <a href="ajout-ticket.php" class="btn btn-primary mt-2">
                      <i class="ti ti-plus me-2"></i> Créer un Ticket
                    </a>
                  </div>
                <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-hover" id="pc-dt-simple">
                      <thead>
                        <tr>
                          <th>Référence</th>
                          <th>Catégorie</th>
                          <th>Priorité</th>
                          <th>Objet</th>
                          <th>Description</th>
                          <th>Statut</th>
                          <th>Date de Création</th>
                          <th class="text-center">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                          <tr>
                            <td>
                              <span class="badge bg-light-primary rounded-pill f-12">
                                <?= htmlspecialchars($ticket['reference']) ?>
                              </span>
                            </td>
                            <td><?= htmlspecialchars($ticket['category_name'] ?? 'N/A') ?></td>
                            <td>
                              <span class="badge rounded-pill f-12" style="background-color: <?= htmlspecialchars($ticket['priority_color'] ?? '#6c757d') ?>; color: white;">
                                <?= htmlspecialchars($ticket['priority_name'] ?? 'N/A') ?>
                              </span>
                            </td>
                            <td><?= htmlspecialchars($ticket['subject']) ?></td>
                            <td>
                              <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($ticket['description']) ?>">
                                <?= htmlspecialchars($ticket['description']) ?>
                              </div>
                            </td>
                            <td>
                              <span class="badge rounded-pill f-12" style="background-color: <?= htmlspecialchars($ticket['status_color'] ?? '#6c757d') ?>; color: white;">
                                <?= htmlspecialchars($ticket['status_name'] ?? 'N/A') ?>
                              </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></td>
                            <td class="text-center">
                              <ul class="list-inline me-auto mb-0">
                                <li class="list-inline-item align-bottom" data-bs-toggle="tooltip" title="Voir">
                                  <a href="#" class="avtar avtar-xs btn-link-secondary" 
                                     data-bs-toggle="modal" 
                                     data-bs-target="#ticket-view-modal"
                                     onclick="viewTicket(<?= htmlspecialchars(json_encode($ticket)) ?>)">
                                    <i class="ti ti-eye f-18"></i>
                                  </a>
                                </li>
                                <li class="list-inline-item align-bottom" data-bs-toggle="tooltip" title="Modifier">
                                  <?php if (strtolower($ticket['status_name']) === 'nouveau'): ?>
                                    <a href="#" class="avtar avtar-xs btn-link-primary" 
                                       data-bs-toggle="modal" 
                                       data-bs-target="#ticket-edit-modal"
                                       onclick="editTicket(<?= htmlspecialchars(json_encode($ticket)) ?>)">
                                      <i class="ti ti-edit-circle f-18"></i>
                                    </a>
                                  <?php else: ?>
                                    <a href="#" class="avtar avtar-xs btn-link-secondary" 
                                       style="opacity: 0.5; cursor: not-allowed;" 
                                       onclick="return false;">
                                      <i class="ti ti-edit-circle f-18"></i>
                                    </a>
                                  <?php endif; ?>
                                </li>
                              </ul>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                  
                  <!-- Pagination -->
                  <?php if ($totalPages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-4">
                      <div>
                        <p class="text-muted mb-0">
                          Affichage de <?= (($currentPage - 1) * $ticketsPerPage) + 1 ?> à 
                          <?= min($currentPage * $ticketsPerPage, $totalTickets) ?> sur 
                          <?= $totalTickets ?> tickets
                        </p>
                      </div>
                      <nav aria-label="Pagination">
                        <ul class="pagination mb-0">
                          <!-- Bouton Précédent -->
                          <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?token=<?= urlencode($token) ?>&page=<?= $currentPage - 1 ?>" aria-label="Précédent">
                              <span aria-hidden="true">&laquo;</span>
                            </a>
                          </li>
                          
                          <?php
                          // Afficher les numéros de page
                          $startPage = max(1, $currentPage - 2);
                          $endPage = min($totalPages, $currentPage + 2);
                          
                          // Première page
                          if ($startPage > 1): ?>
                            <li class="page-item">
                              <a class="page-link" href="?token=<?= urlencode($token) ?>&page=1">1</a>
                            </li>
                            <?php if ($startPage > 2): ?>
                              <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                          <?php endif; ?>
                          
                          <!-- Pages du milieu -->
                          <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                              <a class="page-link" href="?token=<?= urlencode($token) ?>&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                          <?php endfor; ?>
                          
                          <!-- Dernière page -->
                          <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?>
                              <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                              <a class="page-link" href="?token=<?= urlencode($token) ?>&page=<?= $totalPages ?>"><?= $totalPages ?></a>
                            </li>
                          <?php endif; ?>
                          
                          <!-- Bouton Suivant -->
                          <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?token=<?= urlencode($token) ?>&page=<?= $currentPage + 1 ?>" aria-label="Suivant">
                              <span aria-hidden="true">&raquo;</span>
                            </a>
                          </li>
                        </ul>
                      </nav>
                    </div>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- [ Main Content ] end -->
    </div>
  <!-- [ Footer ] start -->
  <footer style="background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 1.5rem 2rem; margin-top: 3rem;">
    <div style="max-width: 1400px; margin: 0 auto; text-align: center;">
      <p style="margin: 0; color: #6b7280; font-size: 0.875rem;">Ticket Développé par la DSI PFO-Construction | 2026.</p>
    </div>
  </footer>

  <!-- Modal Voir Ticket -->
  <div class="modal fade" id="ticket-view-modal" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <h5 class="mb-0">Détails du Ticket</h5>
          <a href="#" class="avtar avtar-s btn-link-danger" data-bs-dismiss="modal">
            <i class="ti ti-x f-20"></i>
          </a>
        </div>
        <div class="modal-body">
          <div class="card">
            <div class="card-header">
              <h5>Informations du Ticket</h5>
            </div>
            <div class="card-body">
              <ul class="list-group list-group-flush">
                <li class="list-group-item px-0 pt-0">
                  <div class="row">
                    <div class="col-md-6">
                      <p class="mb-1 text-muted">Référence</p>
                      <h6 class="mb-0" id="view-reference">-</h6>
                    </div>
                    <div class="col-md-6">
                      <p class="mb-1 text-muted">Statut</p>
                      <h6 class="mb-0" id="view-status">-</h6>
                    </div>
                  </div>
                </li>
                <li class="list-group-item px-0">
                  <div class="row">
                    <div class="col-md-6">
                      <p class="mb-1 text-muted">Catégorie</p>
                      <h6 class="mb-0" id="view-category">-</h6>
                    </div>
                    <div class="col-md-6">
                      <p class="mb-1 text-muted">Priorité</p>
                      <h6 class="mb-0" id="view-priority">-</h6>
                    </div>
                  </div>
                </li>
                <li class="list-group-item px-0">
                  <p class="mb-1 text-muted">Objet</p>
                  <h6 class="mb-0" id="view-subject">-</h6>
                </li>
                <li class="list-group-item px-0">
                  <p class="mb-1 text-muted">Description</p>
                  <p class="mb-0" id="view-description">-</p>
                </li>
                <li class="list-group-item px-0 pb-0">
                  <div class="row">
                    <div class="col-md-6">
                      <p class="mb-1 text-muted">Date de Création</p>
                      <h6 class="mb-0" id="view-created">-</h6>
                    </div>
                    <div class="col-md-6">
                      <p class="mb-1 text-muted">Dernière Mise à Jour</p>
                      <h6 class="mb-0" id="view-updated">-</h6>
                    </div>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Modifier Ticket -->
  <div class="modal fade" id="ticket-edit-modal" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="mb-0">Modifier le Ticket</h5>
          <a href="#" class="avtar avtar-s btn-link-danger" data-bs-dismiss="modal">
            <i class="ti ti-x f-20"></i>
          </a>
        </div>
        <div class="modal-body">
          <form id="edit-ticket-form" enctype="multipart/form-data">
            <input type="hidden" id="edit-ticket-id" name="ticket_id">
            <div class="form-group mb-3">
              <label class="form-label">Référence</label>
              <input type="text" class="form-control" id="edit-reference" readonly>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Objet</label>
              <input type="text" class="form-control" id="edit-subject" name="subject" placeholder="Objet du ticket">
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Description</label>
              <textarea class="form-control" id="edit-description" name="description" rows="5" placeholder="Description détaillée"></textarea>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group mb-3">
                  <label class="form-label">Catégorie</label>
                  <select class="form-select" id="edit-category" name="category_id">
                    <option value="">Sélectionner une catégorie</option>
                    <!-- Les options seront chargées dynamiquement -->
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group mb-3">
                  <label class="form-label">Priorité</label>
                  <select class="form-select" id="edit-priority" name="priority_id">
                    <option value="">Sélectionner une priorité</option>
                    <!-- Les options seront chargées dynamiquement -->
                  </select>
                </div>
              </div>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Pièces jointes (optionnel)</label>
              <input type="file" class="form-control" id="edit-attachments" name="attachments[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif">
              <small class="text-muted">Formats acceptés : PDF, Word, Images. Taille max : 5 Mo par fichier</small>
            </div>
          </form>
        </div>
        <div class="modal-footer justify-content-end">
          <button type="button" class="btn btn-link-danger" data-bs-dismiss="modal">Annuler</button>
          <button type="button" class="btn btn-primary" onclick="saveTicket()">Enregistrer</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Required Js -->
<script src="../assets/js/plugins/popper.min.js"></script>
<script src="../assets/js/plugins/simplebar.min.js"></script>
<script src="../assets/js/plugins/bootstrap.min.js"></script>
<script src="../assets/js/fonts/custom-font.js"></script>
<script src="../assets/js/pcoded.js"></script>
<script src="../assets/js/plugins/feather.min.js"></script>

<script>layout_change('light');</script>
<script>change_box_container('false');</script>
<script>layout_rtl_change('false');</script>
<script>preset_change("preset-1");</script>
<script>font_change("Public-Sans");</script>

<!-- Scripts pour les modals -->
<script>
  // Fonction pour afficher les détails du ticket
  function viewTicket(ticket) {
    document.getElementById('view-reference').textContent = ticket.reference;
    document.getElementById('view-status').innerHTML = '<span class="badge rounded-pill" style="background-color: ' + ticket.status_color + '; color: white;">' + ticket.status_name + '</span>';
    document.getElementById('view-category').textContent = ticket.category_name;
    document.getElementById('view-priority').innerHTML = '<span class="badge rounded-pill" style="background-color: ' + ticket.priority_color + '; color: white;">' + ticket.priority_name + '</span>';
    document.getElementById('view-subject').textContent = ticket.subject;
    document.getElementById('view-description').textContent = ticket.description;
    
    // Formater les dates
    const createdDate = new Date(ticket.created_at);
    const updatedDate = new Date(ticket.updated_at);
    document.getElementById('view-created').textContent = createdDate.toLocaleString('fr-FR');
    document.getElementById('view-updated').textContent = updatedDate.toLocaleString('fr-FR');
  }

  // Fonction pour préparer l'édition du ticket
  function editTicket(ticket) {
    document.getElementById('edit-ticket-id').value = ticket.id;
    document.getElementById('edit-reference').value = ticket.reference;
    document.getElementById('edit-subject').value = ticket.subject;
    document.getElementById('edit-description').value = ticket.description;
    
    // Sauvegarder les valeurs actuelles avant de charger les options
    document.getElementById('edit-category').setAttribute('data-current-value', ticket.category_id);
    document.getElementById('edit-priority').setAttribute('data-current-value', ticket.priority_id);
    
    // Charger les catégories et priorités
    loadCategories();
    loadPriorities();
  }

  // Charger les catégories
  function loadCategories() {
    fetch('get-categories.php')
      .then(response => response.json())
      .then(data => {
        const select = document.getElementById('edit-category');
        const currentValue = select.getAttribute('data-current-value');
        
        select.innerHTML = '<option value="">Sélectionner une catégorie</option>';
        data.forEach(cat => {
          select.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
        });
        
        // Restaurer la valeur sélectionnée après le chargement
        if (currentValue) {
          select.value = currentValue;
        }
      })
      .catch(error => {
        console.error('Erreur lors du chargement des catégories:', error);
        alert('Erreur lors du chargement des catégories');
      });
  }

  // Charger les priorités
  function loadPriorities() {
    fetch('get-priorities.php')
      .then(response => response.json())
      .then(data => {
        const select = document.getElementById('edit-priority');
        const currentValue = select.getAttribute('data-current-value');
        
        select.innerHTML = '<option value="">Sélectionner une priorité</option>';
        data.forEach(pri => {
          select.innerHTML += `<option value="${pri.id}">${pri.name}</option>`;
        });
        
        // Restaurer la valeur sélectionnée après le chargement
        if (currentValue) {
          select.value = currentValue;
        }
      })
      .catch(error => {
        console.error('Erreur lors du chargement des priorités:', error);
        alert('Erreur lors du chargement des priorités');
      });
  }

  // Sauvegarder les modifications du ticket
  function saveTicket() {
    const formData = new FormData(document.getElementById('edit-ticket-form'));
    
    fetch('update-ticket.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Ticket modifié avec succès !');
        location.reload();
      } else {
        alert('Erreur : ' + data.message);
      }
    })
    .catch(error => {
      alert('Erreur lors de la modification du ticket');
      console.error(error);
    });
  }
</script>

</body>
<!-- [Body] end -->

</html>


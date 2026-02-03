<?php
/**
 * manage-statuses.php
 * Gestion des statuts du référentiel
 */

session_start();
require_once 'config/db.php';

// Protection de la page
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];
$admin_role = $_SESSION['admin_role'];

$message = "";
$error = "";

// --- TRAITEMENT DES ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'add') {
                $label = trim($_POST['label']);
                if (!empty($label)) {
                    $stmt = $pdo->prepare("INSERT INTO statuses (label) VALUES (?)");
                    $stmt->execute([$label]);
                    $message = "Statut ajouté avec succès !";
                } else {
                    $error = "Le libellé du statut est requis.";
                }
            } elseif ($_POST['action'] === 'edit') {
                $id = $_POST['id'];
                $label = trim($_POST['label']);
                if (!empty($id) && !empty($label)) {
                    $stmt = $pdo->prepare("UPDATE statuses SET label = ? WHERE id = ?");
                    $stmt->execute([$label, $id]);
                    $message = "Statut mis à jour avec succès !";
                }
            } elseif ($_POST['action'] === 'delete') {
                $id = $_POST['id'];
                if (!empty($id)) {
                    // Vérifier si le statut est utilisé
                    $check = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE status_id = ?");
                    $check->execute([$id]);
                    if ($check->fetchColumn() > 0) {
                        $error = "Impossible de supprimer ce statut car il est utilisé par des tickets.";
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM statuses WHERE id = ?");
                        $stmt->execute([$id]);
                        $message = "Statut supprimé avec succès !";
                    }
                }
            }
        } catch (PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

// --- RÉCUPÉRATION DES STATUTS ---
try {
    $stmt = $pdo->query("SELECT * FROM statuses ORDER BY label ASC");
    $statuses = $stmt->fetchAll();
} catch (PDOException $e) {
    $statuses = [];
    $error = "Erreur lors de la récupération : " . $e->getMessage();
}

function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'nouveau': return 'bg-light-primary text-primary';
        case 'en cours': return 'bg-light-warning text-warning';
        case 'en attente': return 'bg-light-info text-info';
        case 'résolu': return 'bg-light-success text-success';
        case 'fermé': return 'bg-light-secondary text-secondary';
        default: return 'bg-light-secondary text-secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Gérer les Statuts - PFO DSI</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- Favicon -->
    <link rel="icon" href="../../assets/img/logo/favicon-pfo.png" type="image/x-icon">
    
    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="../assets/fonts/tabler-icons.min.css">
    <link rel="stylesheet" href="../assets/fonts/feather.css">
    <link rel="stylesheet" href="../assets/fonts/fontawesome.css">
    <link rel="stylesheet" href="../assets/fonts/material.css">
    
    <!-- Template CSS -->
    <link rel="stylesheet" href="../assets/css/style.css" id="main-style-link">
    <link rel="stylesheet" href="../assets/css/style-preset.css">
    
    <style>
        :root {
            --pc-primary: #20317b;
            --pc-secondary: #6c757d;
        }
        .pc-sidebar {
            background: #ffffff;
            box-shadow: 0 0 1px rgba(0,0,0,0.1), 0 2px 4px rgba(0,0,0,0.02);
            border-right: 1px solid #eef2f6;
        }
        .sidebar-logo {
            padding: 25px 20px;
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        .sidebar-logo img {
            max-width: 160px;
        }
        .pc-sidebar .pc-link {
            border-radius: 10px;
            margin: 2px 15px;
            padding: 12px 15px;
            font-weight: 500;
            color: #585978;
        }
        .pc-sidebar .pc-link:hover {
            color: #20317b !important;
            background: #f8fafc;
        }
        .pc-sidebar .pc-item.active > .pc-link {
            background: #f0f4ff !important;
            color: #20317b !important;
            font-weight: 700;
        }
        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #64748b;
            cursor: pointer;
        }
        .action-btn:hover {
            background: #fff;
            color: var(--pc-primary);
            border-color: var(--pc-primary);
            transform: scale(1.1);
        }
        .action-btn.btn-delete:hover {
            color: #ef4444;
            border-color: #ef4444;
        }
        .status-pill {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>

<body data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light">
    <!-- [ Navigation ] start -->
    <nav class="pc-sidebar">
        <div class="navbar-wrapper">
            <div class="m-header sidebar-logo">
                <a href="admin-dashboard.php" class="b-brand">
                    <img src="../../assets/img/logo/monimage.png" alt="logo" class="logo-lg">
                </a>
            </div>
            <div class="navbar-content">
                <ul class="pc-navbar mt-2">
                    <li class="pc-item">
                        <a href="admin-dashboard.php" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-dashboard"></i></span>
                            <span class="pc-mtext">Dashboard</span>
                        </a>
                    </li>
                    <li class="pc-item pc-caption">
                        <label>Support Client</label>
                    </li>
                    <li class="pc-item pc-hasmenu">
                        <a href="#!" class="pc-link"><span class="pc-micon"><i class="ti ti-ticket"></i></span><span class="pc-mtext">Tickets</span><span class="pc-arrow"><i data-feather="chevron-right"></i></span></a>
                        <ul class="pc-submenu">
                            <li class="pc-item"><a class="pc-link" href="admin-dashboard.php?filter=all">Tous les tickets</a></li>
                            <li class="pc-item"><a class="pc-link" href="admin-dashboard.php?filter=mine">Mes tickets</a></li>
                        </ul>
                    </li>
                    <li class="pc-item pc-caption">
                        <label>Configuration</label>
                    </li>
                    <li class="pc-item pc-hasmenu">
                        <a href="#!" class="pc-link"><span class="pc-micon"><i class="ti ti-folders"></i></span><span class="pc-mtext">Référentiels</span><span class="pc-arrow"><i data-feather="chevron-right"></i></span></a>
                        <ul class="pc-submenu">
                            <li class="pc-item"><a class="pc-link" href="manage-categories.php">Catégories</a></li>
                            <li class="pc-item"><a class="pc-link" href="manage-priorities.php">Priorités</a></li>
                            <li class="pc-item active"><a class="pc-link" href="manage-statuses.php">Statuts</a></li>
                        </ul>
                    </li>
                    <li class="pc-item pc-hasmenu">
                        <a href="#!" class="pc-link"><span class="pc-micon"><i class="ti ti-users"></i></span><span class="pc-mtext">Utilisateurs</span><span class="pc-arrow"><i data-feather="chevron-right"></i></span></a>
                        <ul class="pc-submenu">
                            <li class="pc-item"><a class="pc-link" href="manage-users.php?role=AGENT">Agents</a></li>
                            <li class="pc-item"><a class="pc-link" href="manage-users.php?role=ADMIN">Admins</a></li>
                        </ul>
                    </li>
                    <li class="pc-item pc-caption">
                        <label>Système</label>
                    </li>
                    <li class="pc-item pc-hasmenu">
                        <a href="#!" class="pc-link"><span class="pc-micon"><i class="ti ti-settings"></i></span><span class="pc-mtext">Paramètres</span><span class="pc-arrow"><i data-feather="chevron-right"></i></span></a>
                        <ul class="pc-submenu">
                            <li class="pc-item"><a class="pc-link" href="manage-notifications.php">Notifications</a></li>
                            <li class="pc-item"><a class="pc-link" href="manage-sla.php">SLA / Délais</a></li>
                            <li class="pc-item"><a class="pc-link" href="manage-auto-expiration.php">Expiration auto.</a></li>
                        </ul>
                    </li>
                    <li class="pc-item mt-5 pt-3 border-top">
                        <a href="logout.php" class="pc-link text-danger">
                            <span class="pc-micon"><i class="ti ti-logout text-danger"></i></span>
                            <span class="pc-mtext font-weight-bold">Déconnexion</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- [ Navigation ] end -->

    <!-- [ Header ] start -->
    <header class="pc-header">
        <div class="header-wrapper">
            <div class="me-auto pc-mob-drp">
                <ul class="list-unstyled">
                    <li class="pc-h-item pc-sidebar-collapse">
                        <a href="#" class="pc-head-link ms-0" id="sidebar-hide">
                            <i class="ti ti-menu-2"></i>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="ms-auto">
                <ul class="list-unstyled">
                    <li class="dropdown pc-h-item header-user-profile">
                        <a class="pc-head-link dropdown-toggle arrow-none me-0 border-0" data-bs-toggle="dropdown" href="#" role="button">
                            <img src="../assets/images/user/avatar-1.jpg" alt="user-image" class="user-avtar">
                            <span>
                                <span class="user-name"><?= htmlspecialchars($admin_name) ?></span>
                                <span class="user-desc"><?= htmlspecialchars($admin_role) ?></span>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end pc-h-dropdown shadow-lg border-0">
                            <a href="logout.php" class="dropdown-item text-danger"><i class="ti ti-logout"></i> Déconnexion</a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </header>
    <!-- [ Header ] end -->

    <!-- [ Main Content ] start -->
    <div class="pc-container">
        <div class="pc-content">
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h2 class="m-b-10" style="font-weight: 800; color: #1e293b;">Gestion des <span class="text-primary">Statuts</span></h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="ti ti-circle-check me-2"></i> <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="ti ti-alert-triangle me-2"></i> <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-12">
                    <div class="card border-0 shadow-sm" style="border-radius: 20px;">
                        <div class="card-header bg-white border-0 py-4 px-4 d-flex justify-content-between align-items-center">
                            <h4 class="mb-0" style="font-weight: 700; color: #1e293b;">Liste des statuts</h4>
                            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addModal">
                                <i class="ti ti-plus me-1"></i> Ajouter un statut
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4" style="width: 80px;">#</th>
                                            <th>LIBELLÉ</th>
                                            <th>APERÇU DU BADGE</th>
                                            <th class="text-end pe-4">ACTIONS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($statuses)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5">
                                                    <i class="ti ti-loader f-40 text-muted mb-3 d-block"></i>
                                                    <p class="text-muted">Aucun statut trouvé.</p>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php $i = 1; foreach ($statuses as $st): ?>
                                                <tr>
                                                    <td class="ps-4 fw-600 text-muted">#<?= $i++ ?></td>
                                                    <td><span class="fw-700 text-dark"><?= htmlspecialchars($st['label']) ?></span></td>
                                                    <td>
                                                        <span class="status-pill <?= getStatusBadgeClass($st['label']) ?>">
                                                            <?= htmlspecialchars($st['label']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end pe-4">
                                                        <div class="d-flex gap-2 justify-content-end">
                                                            <button class="action-btn btn-edit" 
                                                                    data-id="<?= $st['id'] ?>" 
                                                                    data-label="<?= htmlspecialchars($st['label']) ?>"
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#editModal">
                                                                <i class="ti ti-edit"></i>
                                                            </button>
                                                            <button class="action-btn btn-delete" 
                                                                    data-id="<?= $st['id'] ?>" 
                                                                    data-label="<?= htmlspecialchars($st['label']) ?>"
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#deleteModal">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajouter -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="modal-title fw-700">Nouveau Statut</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body px-4">
                        <div class="mb-3">
                            <label class="form-label">Libellé du statut</label>
                            <input type="text" name="label" class="form-control bg-light border-0" placeholder="ex: En cours, Résolu..." required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pb-4 px-4">
                        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Modifier -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="modal-title fw-700">Modifier le Statut</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit-id">
                    <div class="modal-body px-4">
                        <div class="mb-3">
                            <label class="form-label">Libellé du statut</label>
                            <input type="text" name="label" id="edit-label" class="form-control bg-light border-0" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pb-4 px-4">
                        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Supprimer -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="modal-title fw-700 text-danger">Supprimer le Statut</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete-id">
                    <div class="modal-body px-4">
                        <p>Êtes-vous sûr de vouloir supprimer le statut <b id="delete-label"></b> ? Cette action est irréversible.</p>
                    </div>
                    <div class="modal-footer border-0 pb-4 px-4">
                        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-4">Supprimer</button>
                    </div>
                </form>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var editModal = document.getElementById('editModal');
            editModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var id = button.getAttribute('data-id');
                var label = button.getAttribute('data-label');
                
                document.getElementById('edit-id').value = id;
                document.getElementById('edit-label').value = label;
            });

            var deleteModal = document.getElementById('deleteModal');
            deleteModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var id = button.getAttribute('data-id');
                var label = button.getAttribute('data-label');
                
                document.getElementById('delete-id').value = id;
                document.getElementById('delete-label').textContent = label;
            });
        });
    </script>
</body>
</html>

<?php
/**
 * manage-notifications.php
 * Gestion des modèles de notifications par email
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

// --- INITIALISATION DE LA TABLE SI BESOIN ---
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS notification_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_name VARCHAR(100) NOT NULL UNIQUE,
        description VARCHAR(255),
        subject VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        is_enabled TINYINT(1) DEFAULT 1,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Insertion des templates par défaut si vides
    $check = $pdo->query("SELECT COUNT(*) FROM notification_templates");
    if ($check->fetchColumn() == 0) {
        $default_templates = [
            ['ticket_created', 'Accusé de réception client', 'Confirmation de création de ticket - {reference}', 
"<h2>✅ Ticket créé avec succès</h2>
<p>Bonjour <strong>{customer_name}</strong>,</p>
<p>Nous confirmons la réception de votre demande. Votre ticket a bien été enregistré dans notre système et sera traité par notre équipe dans les meilleurs délais.</p>

<div class=\"alert alert-info\">
    <strong>🆕 Nouveau ticket créé</strong><br>
    <strong>Ticket :</strong> {reference}
</div>

<div class=\"info-box\">
    <strong>Objet :</strong> {subject}<br>
    <strong>Date de création :</strong> {created_date}<br>
    <strong>Statut :</strong> Nouveau
</div>

<p>Vous recevrez des mises à jour régulières sur l'avancement de votre demande. Notre équipe vous contactera si des informations supplémentaires sont nécessaires.</p>

<div style=\"text-align: center;\">
    <a href=\"{link}\" style=\"display: inline-block; padding: 12px 25px; background-color: #0066cc; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;\">Voir les détails</a>
</div>

<div class=\"divider\"></div>

<table>
    <tr style=\"background: #f0f0f0;\">
        <td><strong>Statut actuel :</strong></td>
        <td style=\"text-align: right; color: #0066cc; font-weight: bold;\">Nouveau</td>
    </tr>
</table>"],

            ['status_changed', 'Changement de statut', 'Mise à jour de votre ticket - {reference}', 
"<h2>📢 Votre ticket a été mis à jour</h2>
<p>Bonjour <strong>{customer_name}</strong>,</p>
<p>Nous vous informons qu'il y a du nouveau concernant votre demande :</p>

<div class=\"alert alert-info\">
    <strong>🔄 Nouveau statut :</strong> <span style=\"font-size: 16px; font-weight: bold;\">{status}</span><br>
    <strong>Ticket :</strong> {reference}
</div>

<div class=\"info-box\">
    <strong>Objet :</strong> {subject}<br>
    <strong>Date de mise à jour :</strong> {updated_date}<br>
    <strong>Dernière mise à jour :</strong> {last_message}
</div>

<p>Notre équipe de support continue de traiter votre demande avec attention. Vous serez averti dès qu'il y aura une nouvelle évolution.</p>

<div style=\"text-align: center;\">
    <a href=\"{link}\" class=\"btn btn-block\">Voir les détails</a>
</div>

<div class=\"divider\"></div>

<table>
    <tr style=\"background: #f0f0f0;\">
        <td><strong>Statut actuel :</strong></td>
        <td style=\"text-align: right; color: #0066cc; font-weight: bold;\">{status}</td>
    </tr>
</table>"],

            ['agent_assigned', 'Notification assignation agent', 'Nouveau ticket assigné - {reference}', 
"<h2>👤 Agent assigné à votre ticket</h2>
<p>Bonjour <strong>{customer_name}</strong>,</p>
<p>Votre demande a été attribuée à un agent spécialisé qui assurera son traitement dans les meilleurs délais.</p>

<div class=\"alert alert-success\">
    <strong>✓ Agent assigné</strong><br>
    <strong>Nom :</strong> {agent_name}<br>
    <strong>Spécialité :</strong> {agent_department}
</div>

<div class=\"info-box\">
    <strong>Ticket :</strong> {reference}<br>
    <strong>Objet :</strong> {subject}<br>
    <strong>Priorité :</strong> <span style=\"color: #ff6b6b; font-weight: bold;\">{priority}</span><br>
    <strong>Date d'assignment :</strong> {assigned_date}
</div>

<p><strong>{agent_name}</strong> examinera votre demande et prendra contact avec vous dans les plus brefs délais si des informations supplémentaires sont nécessaires.</p>

<div class=\"divider\"></div>

<p style=\"font-size: 13px; color: #666;\">Un agent dédié signifie une meilleure qualité de service et un suivi plus personnalisé de votre demande.</p>"],

            ['new_message', 'Nouveau message sur le ticket', 'Nouveau message pour le ticket - {reference}', 
"<h2>💬 Nouveau message reçu</h2>
<p>Bonjour <strong>{customer_name}</strong>,</p>
<p>Une nouvelle réponse a été ajoutée à votre ticket. Consultez le message ci-dessous ou accédez à votre espace pour voir la conversation complète.</p>

<div class=\"alert alert-info\">
    <strong>Message de :</strong> {message_author}<br>
    <strong>Ticket :</strong> {reference}<br>
    <strong>Reçu le :</strong> {message_date}
</div>

<div class=\"info-box\" style=\"background: #fffacd; border-left-color: #ffc107;\">
    <p style=\"margin: 0; color: #333;\">{message_preview}</p>
</div>

<p style=\"font-size: 13px; color: #666; font-style: italic;\">Ceci est un aperçu du message. Pour voir le message complet avec les pièces jointes éventuelles, veuillez consulter votre espace client.</p>

<div style=\"text-align: center;\">
    <a href=\"{link}\" class=\"btn btn-block\">Voir la conversation</a>
</div>

<div class=\"divider\"></div>

<p>Vous continuerez à recevoir des notifications pour tous les nouveaux messages concernant ce ticket. Merci de votre patience et de votre confiance.</p>"]
        ];
        $stmt = $pdo->prepare("INSERT INTO notification_templates (event_name, description, subject, body) VALUES (?, ?, ?, ?)");
        foreach ($default_templates as $tmpl) {
            $stmt->execute($tmpl);
        }
    }
} catch (PDOException $e) {
    $error = "Erreur Initialisation : " . $e->getMessage();
}

// --- TRAITEMENT DES ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        // Server-side authorization: only ADMIN and SUPERVISOR can edit notification templates
        if (!in_array($admin_role, ['ADMIN', 'SUPERVISOR'])) {
            $error = "Vous n'avez pas les droits nécessaires pour modifier les modèles de notification.";
        } else {
            try {
                $id = $_POST['id'];
                $subject = trim($_POST['subject']);
                $body = trim($_POST['body']);
                $is_enabled = isset($_POST['is_enabled']) ? 1 : 0;

                if (!empty($id) && !empty($subject) && !empty($body)) {
                    $stmt = $pdo->prepare("UPDATE notification_templates SET subject = ?, body = ?, is_enabled = ? WHERE id = ?");
                    $stmt->execute([$subject, $body, $is_enabled, $id]);
                    $message = "Modèle de notification mis à jour avec succès !";
                } else {
                    $error = "Le sujet et le corps du message sont requis.";
                }
            } catch (PDOException $e) {
                $error = "Erreur : " . $e->getMessage();
            }
        }
    }
}

// --- RÉCUPÉRATION DES TEMPLATES ---
try {
    $stmt = $pdo->query("SELECT * FROM notification_templates ORDER BY id ASC");
    $templates = $stmt->fetchAll();
} catch (PDOException $e) {
    $templates = [];
    $error = "Erreur lors de la récupération : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Paramètres Notifications - PFO DSI</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" href="../../assets/img/logo/favicon-pfo.png" type="image/x-icon">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="../assets/fonts/tabler-icons.min.css">
    <link rel="stylesheet" href="../assets/fonts/feather.css">
    <link rel="stylesheet" href="../assets/fonts/fontawesome.css">
    <link rel="stylesheet" href="../assets/fonts/material.css">
    <link rel="stylesheet" href="../assets/css/style.css" id="main-style-link">
    <link rel="stylesheet" href="../assets/css/style-preset.css">
    <style>
        :root { --pc-primary: #20317b; }
        .pc-sidebar { background: #ffffff; border-right: 1px solid #eef2f6; }
        .sidebar-logo { padding: 25px 20px; text-align: center; border-bottom: 1px solid #f1f5f9; }
        .sidebar-logo img { max-width: 160px; }
        .pc-sidebar .pc-link { border-radius: 10px; margin: 2px 15px; padding: 12px 15px; font-weight: 500; color: #585978; }
        .pc-sidebar .pc-item.active > .pc-link { background: #f0f4ff !important; color: #20317b !important; font-weight: 700; }
        .action-btn { width: 36px; height: 36px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; background: #f8fafc; border: 1px solid #e2e8f0; color: #64748b; cursor: pointer; }
        .action-btn:hover { background: #20317b; color: white; transform: scale(1.1); }
        .badge-event { font-family: monospace; font-size: 0.8rem; background: #eef2f6; color: #475569; padding: 2px 8px; border-radius: 4px; }
    </style>
</head>
<body data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light">
    <!-- [ Navigation ] start -->
    <nav class="pc-sidebar">
        <div class="navbar-wrapper">
            <div class="m-header sidebar-logo">
                <a href="admin-dashboard-new.php" class="b-brand">
                    <img src="../../assets/img/logo/monimage.png" alt="logo" class="logo-lg">
                </a>
            </div>
            <div class="navbar-content">
                <ul class="pc-navbar mt-2">
                    <li class="pc-item"><a href="admin-dashboard-new.php" class="pc-link"><span class="pc-micon"><i class="ti ti-dashboard"></i></span><span class="pc-mtext">Dashboard</span></a></li>
                    <li class="pc-item pc-caption"><label>Support Client</label></li>
                    <li class="pc-item pc-hasmenu">
                        <a href="#!" class="pc-link"><span class="pc-micon"><i class="ti ti-ticket"></i></span><span class="pc-mtext">Tickets</span><span class="pc-arrow"><i data-feather="chevron-right"></i></span></a>
                        <ul class="pc-submenu">
                            <li class="pc-item"><a class="pc-link" href="admin-dashboard-new.php?filter=all">Tous les tickets</a></li>
                            <li class="pc-item"><a class="pc-link" href="admin-dashboard-new.php?filter=mine">Mes tickets</a></li>
                        </ul>
                    </li>
                    <li class="pc-item pc-caption"><label>Configuration</label></li>
                    <li class="pc-item pc-hasmenu">
                        <a href="#!" class="pc-link"><span class="pc-micon"><i class="ti ti-folders"></i></span><span class="pc-mtext">Référentiels</span><span class="pc-arrow"><i data-feather="chevron-right"></i></span></a>
                        <ul class="pc-submenu">
                            <li class="pc-item"><a class="pc-link" href="manage-categories.php">Catégories</a></li>
                            <li class="pc-item"><a class="pc-link" href="manage-priorities.php">Priorités</a></li>
                            <li class="pc-item"><a class="pc-link" href="manage-statuses.php">Statuts</a></li>
                        </ul>
                    </li>
                    <li class="pc-item pc-hasmenu">
                        <a href="#!" class="pc-link"><span class="pc-micon"><i class="ti ti-users"></i></span><span class="pc-mtext">Utilisateurs</span><span class="pc-arrow"><i data-feather="chevron-right"></i></span></a>
                        <ul class="pc-submenu">
                            <li class="pc-item"><a class="pc-link" href="manage-users.php?role=AGENT">Agents</a></li>
                            <li class="pc-item"><a class="pc-link" href="manage-users.php?role=ADMIN">Admins</a></li>
                        </ul>
                    </li>
                    <li class="pc-item pc-caption"><label>Système</label></li>
                    <li class="pc-item pc-hasmenu active">
                        <a href="#!" class="pc-link"><span class="pc-micon"><i class="ti ti-settings"></i></span><span class="pc-mtext">Paramètres</span><span class="pc-arrow"><i data-feather="chevron-right"></i></span></a>
                        <ul class="pc-submenu">
                            <li class="pc-item active"><a class="pc-link" href="manage-notifications.php">Notifications</a></li>
                            <li class="pc-item"><a class="pc-link" href="manage-sla.php">SLA / Délais</a></li>
                            <li class="pc-item"><a class="pc-link" href="manage-auto-expiration.php">Expiration auto.</a></li>
                        </ul>
                    </li>
                    <li class="pc-item mt-5 pt-3 border-top"><a href="logout.php" class="pc-link text-danger"><span class="pc-micon"><i class="ti ti-logout text-danger"></i></span><span class="pc-mtext font-weight-bold">Déconnexion</span></a></li>
                </ul>
            </div>
        </div>
    </nav>
    <header class="pc-header">
        <div class="header-wrapper">
            <div class="me-auto pc-mob-drp">
                <ul class="list-unstyled">
                    <li class="pc-h-item pc-sidebar-collapse"><a href="#" class="pc-head-link ms-0" id="sidebar-hide"><i class="ti ti-menu-2"></i></a></li>
                </ul>
            </div>
            <div class="ms-auto">
                <ul class="list-unstyled">
                    <li class="dropdown pc-h-item header-user-profile">
                        <a class="pc-head-link dropdown-toggle arrow-none me-0 border-0" data-bs-toggle="dropdown" href="#" role="button">
                            <img src="../assets/images/user/avatar-1.jpg" alt="user-image" class="user-avtar">
                            <span><span class="user-name"><?= htmlspecialchars($admin_name) ?></span><span class="user-desc"><?= htmlspecialchars($admin_role) ?></span></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end pc-h-dropdown shadow-lg border-0">
                            <a href="logout.php" class="dropdown-item text-danger"><i class="ti ti-logout"></i> Déconnexion</a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <div class="pc-container">
        <div class="pc-content">
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h2 class="m-b-10" style="font-weight: 800; color: #1e293b;">Paramètres des <span class="text-primary">Notifications</span></h2>
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
                        <div class="card-header bg-white border-0 py-4 px-4">
                            <h4 class="mb-0" style="font-weight: 700; color: #1e293b;">Modèles d'emails système</h4>
                            <p class="text-muted mb-0">Personnalisez les messages envoyés automatiquement par la plateforme.</p>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">ÉVÉNEMENT</th>
                                            <th>DESCRIPTION</th>
                                            <th>SUJET DE L'EMAIL</th>
                                            <th class="text-center">STATUT</th>
                                            <th class="text-end pe-4">ACTIONS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($templates as $tmpl): ?>
                                            <tr>
                                                <td class="ps-4"><span class="badge-event"><?= htmlspecialchars($tmpl['event_name']) ?></span></td>
                                                <td><span class="fw-600 text-dark"><?= htmlspecialchars($tmpl['description']) ?></span></td>
                                                <td><span class="text-muted"><?= htmlspecialchars($tmpl['subject']) ?></span></td>
                                                <td class="text-center">
                                                    <?php if ($tmpl['is_enabled']): ?>
                                                        <span class="badge bg-light-success text-success px-3 py-1 rounded-pill">Activé</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-danger text-danger px-3 py-1 rounded-pill">Désactivé</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <?php if (in_array($admin_role, ['ADMIN', 'SUPERVISOR'])): ?>
                                                        <button class="action-btn btn-edit" 
                                                                data-id="<?= $tmpl['id'] ?>" 
                                                                data-event="<?= htmlspecialchars($tmpl['event_name']) ?>"
                                                                data-desc="<?= htmlspecialchars($tmpl['description']) ?>"
                                                                data-subject="<?= htmlspecialchars($tmpl['subject']) ?>"
                                                                data-body="<?= htmlspecialchars($tmpl['body']) ?>"
                                                                data-active="<?= $tmpl['is_enabled'] ?>"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editModal">
                                                            <i class="ti ti-edit"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="action-btn" disabled title="Lecture seule"><i class="ti ti-eye"></i></button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Modifier -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="modal-title fw-700">Modifier le modèle : <span id="modal-event-name" class="text-primary"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit-id">
                    <div class="modal-body px-4">
                        <div class="p-3 bg-light rounded-3 mb-4">
                            <p class="mb-0 fw-bold text-dark"><i class="ti ti-info-circle me-1"></i> Variables disponibles :</p>
                            <code class="text-primary">{reference}, {customer_name}, {subject}, {priority}, {status}, {link}</code>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sujet de l'email</label>
                            <input type="text" name="subject" id="edit-subject" class="form-control bg-light border-0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Corps du message (HTML ou Texte)</label>
                            <textarea name="body" id="edit-body" class="form-control bg-light border-0" rows="8" required></textarea>
                        </div>
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" name="is_enabled" id="edit-active">
                            <label class="form-check-label" for="edit-active">Activer cette notification</label>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pb-4 px-4">
                        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Annuler</button>
                        <?php if (in_array($admin_role, ['ADMIN', 'SUPERVISOR'])): ?>
                            <button type="submit" class="btn btn-primary rounded-pill px-4">Enregistrer les modifications</button>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary rounded-pill px-4" disabled>Lecture seule</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
                document.getElementById('edit-id').value = button.getAttribute('data-id');
                document.getElementById('modal-event-name').textContent = button.getAttribute('data-desc');
                document.getElementById('edit-subject').value = button.getAttribute('data-subject');
                document.getElementById('edit-body').value = button.getAttribute('data-body');
                document.getElementById('edit-active').checked = (button.getAttribute('data-active') == 1);
            });
        });
    </script>
</body>
</html>

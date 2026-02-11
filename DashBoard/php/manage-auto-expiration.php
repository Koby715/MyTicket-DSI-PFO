<?php
/**
 * manage-auto-expiration.php
 * Gestion de la fermeture automatique des tickets inactifs
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
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT,
        description TEXT
    )");

    // Insertion des paramètres par défaut si inexistants
    $default_settings = [
        ['auto_close_enabled', '0', 'Activer la fermeture automatique (0/1)'],
        ['auto_close_days', '5', 'Nombre de jours d\'inactivité avant fermeture'],
        ['auto_close_status_from', '0', 'ID du statut d\'origine (0 = n\'importe lequel sauf fermé)'],
        ['auto_close_status_to', '0', 'ID du statut cible']
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
    foreach ($default_settings as $s) { $stmt->execute($s); }
} catch (PDOException $e) {
    $error = "Erreur Initialisation : " . $e->getMessage();
}

// --- TRAITEMENT DES ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_settings') {
        // Server-side authorization: only ADMIN and SUPERVISOR can modify settings
        if (!in_array($admin_role, ['ADMIN', 'SUPERVISOR'])) {
            $error = "Vous n'avez pas les droits nécessaires pour modifier ces paramètres.";
        } else {
            try {
                $pdo->beginTransaction();
                
                $settings = [
                    'auto_close_enabled' => isset($_POST['auto_close_enabled']) ? '1' : '0',
                    'auto_close_days' => intval($_POST['auto_close_days']),
                    'auto_close_status_from' => intval($_POST['auto_close_status_from']),
                    'auto_close_status_to' => intval($_POST['auto_close_status_to'])
                ];

                foreach ($settings as $key => $value) {
                    $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
                    $stmt->execute([$value, $key]);
                }

                $pdo->commit();
                $message = "Paramètres d'expiration auto. mis à jour avec succès !";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Erreur : " . $e->getMessage();
            }
        }
    }
}

// --- RÉCUPÉRATION DES PARAMÈTRES ET STATUTS ---
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $stmt = $pdo->query("SELECT id, label FROM statuses ORDER BY label ASC");
    $statuses = $stmt->fetchAll();
} catch (PDOException $e) {
    $settings = [];
    $statuses = [];
    $error = "Erreur lors de la récupération : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Paramètres Expiration - PFO DSI</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <link rel="icon" href="../../assets/img/logo/favicon-pfo.png" type="image/x-icon">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="../assets/fonts/tabler-icons.min.css">
    <link rel="stylesheet" href="../assets/fonts/feather.css">
    <link rel="stylesheet" href="../assets/fonts/fontawesome.css">
    <link rel="stylesheet" href="../assets/css/style.css" id="main-style-link">
    <link rel="stylesheet" href="../assets/css/style-preset.css">
    <style>
        :root { --pc-primary: #20317b; }
        .pc-sidebar { background: #ffffff; border-right: 1px solid #eef2f6; }
        .sidebar-logo { padding: 25px 20px; text-align: center; border-bottom: 1px solid #f1f5f9; }
        .sidebar-logo img { max-width: 160px; }
        .pc-sidebar .pc-link { border-radius: 10px; margin: 2px 15px; padding: 12px 15px; font-weight: 500; color: #585978; }
        .pc-sidebar .pc-item.active > .pc-link { background: #f0f4ff !important; color: #20317b !important; font-weight: 700; }
        .settings-card { background: #fff; border-radius: 20px; border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    </style>
</head>
<body data-pc-preset="preset-1" data-pc-theme="light">
    <!-- [ Navigation ] -->
    <nav class="pc-sidebar">
        <div class="navbar-wrapper">
            <div class="m-header sidebar-logo">
                <a href="admin-dashboard.php" class="b-brand">
                    <img src="../../assets/img/logo/monimage.png" alt="logo" class="logo-lg">
                </a>
            </div>
            <div class="navbar-content">
                <ul class="pc-navbar mt-2">
                    <li class="pc-item"><a href="admin-dashboard.php" class="pc-link"><span class="pc-micon"><i class="ti ti-dashboard"></i></span><span class="pc-mtext">Dashboard</span></a></li>
                    <li class="pc-item pc-caption"><label>Support Client</label></li>
                    <li class="pc-item pc-hasmenu"><a href="#!" class="pc-link"><span class="pc-micon"><i class="ti ti-ticket"></i></span><span class="pc-mtext">Tickets</span><span class="pc-arrow"><i data-feather="chevron-right"></i></span></a>
                        <ul class="pc-submenu">
                            <li class="pc-item"><a class="pc-link" href="admin-dashboard.php?filter=all">Tous les tickets</a></li>
                            <li class="pc-item"><a class="pc-link" href="admin-dashboard.php?filter=mine">Mes tickets</a></li>
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
                            <li class="pc-item"><a class="pc-link" href="manage-notifications.php">Notifications</a></li>
                            <li class="pc-item"><a class="pc-link" href="manage-sla.php">SLA / Délais</a></li>
                            <li class="pc-item active"><a class="pc-link" href="manage-auto-expiration.php">Expiration auto.</a></li>
                        </ul>
                    </li>
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
        </div>
    </header>

    <div class="pc-container">
        <div class="pc-content">
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h2 class="m-b-10" style="font-weight: 800; color: #1e293b;">Paramètres d'<span class="text-primary">Expiration Auto.</span></h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible show border-0 shadow-sm" role="alert">
                    <i class="ti ti-circle-check me-2"></i> <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible show border-0 shadow-sm" role="alert">
                    <i class="ti ti-alert-circle me-2"></i> <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-xl-8">
                    <div class="card settings-card shadow-sm p-4">
                        <div class="card-header bg-transparent border-0 px-0 mb-4">
                            <h4 class="fw-800 text-dark">Règles de clôture automatique</h4>
                            <p class="text-muted">Configurez le système pour fermer automatiquement les tickets sans réponse.</p>
                        </div>
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="update_settings">
                            
                            <div class="mb-5 py-4 px-4 bg-light rounded-4 d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0 fw-700">Activer l'expiration automatique</h5>
                                    <p class="text-muted small mb-0">Si désactivé, aucun ticket ne sera fermé automatiquement.</p>
                                </div>
                                <div class="form-check form-switch form-check-lg">
                                    <input class="form-check-input" type="checkbox" name="auto_close_enabled" id="enabledSwitch" style="width: 3.5rem; height: 1.75rem;" <?= ($settings['auto_close_enabled'] == '1') ? 'checked' : '' ?>>
                                </div>
                            </div>

                            <div class="row g-4">
                                <div class="col-md-12">
                                    <label class="form-label fw-700">Temps d'inactivité avant fermeture (Jours)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0"><i class="ti ti-calendar-time"></i></span>
                                        <input type="number" name="auto_close_days" class="form-control bg-light border-0" value="<?= $settings['auto_close_days'] ?? 5 ?>" min="1">
                                    </div>
                                    <small class="text-muted">Le ticket sera fermé après ce nombre de jours sans nouveau message.</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-700">Statut concerné (Origine)</label>
                                    <select name="auto_close_status_from" class="form-select bg-light border-0">
                                        <option value="0">Tous les statuts ouverts</option>
                                        <?php foreach ($statuses as $st): ?>
                                            <option value="<?= $st['id'] ?>" <?= ($settings['auto_close_status_from'] == $st['id']) ? 'selected' : '' ?>><?= htmlspecialchars($st['label']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-700">Statut final (Cible)</label>
                                    <select name="auto_close_status_to" class="form-select bg-light border-0">
                                        <option value="0">-- Sélectionner --</option>
                                        <?php foreach ($statuses as $st): ?>
                                            <option value="<?= $st['id'] ?>" <?= ($settings['auto_close_status_to'] == $st['id']) ? 'selected' : '' ?>><?= htmlspecialchars($st['label']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 mt-5">
                                    <?php if (in_array($admin_role, ['ADMIN', 'SUPERVISOR'])): ?>
                                        <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill shadow">Enregistrer les paramètres</button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-secondary btn-lg w-100 rounded-pill shadow" disabled>Lecture seule — pas d'accès en écriture</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <div class="mt-4 p-4 bg-light-info rounded-4 border-1 border-info border-dashed">
                        <div class="d-flex align-items-center">
                            <i class="ti ti-info-circle f-30 text-info me-3"></i>
                            <div>
                                <h6 class="mb-1 fw-700 text-info">Note Technique</h6>
                                <p class="mb-0 small text-info">L'expiration automatique nécessite l'exécution d'une tâche planifiée (Cron Job) sur votre serveur pour traiter les tickets en arrière-plan.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/plugins/popper.min.js"></script>
    <script src="../assets/js/plugins/simplebar.min.js"></script>
    <script src="../assets/js/plugins/bootstrap.min.js"></script>
    <script src="../assets/js/pcoded.js"></script>
</body>
</html>

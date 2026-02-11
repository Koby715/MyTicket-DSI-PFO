<?php
/**
 * manage-sla.php
 * Gestion des délais de réponse et de résolution (SLA)
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
    $pdo->exec("CREATE TABLE IF NOT EXISTS sla_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        priority_id INT NOT NULL UNIQUE,
        response_time INT NOT NULL DEFAULT 4, -- en heures
        resolution_time INT NOT NULL DEFAULT 24, -- en heures
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    $error = "Erreur Initialisation : " . $e->getMessage();
}

// --- TRAITEMENT DES ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_sla') {
        // Server-side authorization: only ADMIN and SUPERVISOR can update SLA
        if (!in_array($admin_role, ['ADMIN', 'SUPERVISOR'])) {
            $error = "Vous n'avez pas les droits nécessaires pour modifier les SLA.";
        } else {
            try {
                $priority_id = $_POST['priority_id'];
                $response_time = intval($_POST['response_time']);
                $resolution_time = intval($_POST['resolution_time']);

                // Utiliser REPLACE pour insérer ou mettre à jour
                $stmt = $pdo->prepare("INSERT INTO sla_settings (priority_id, response_time, resolution_time) 
                                       VALUES (?, ?, ?) 
                                       ON DUPLICATE KEY UPDATE response_time = VALUES(response_time), resolution_time = VALUES(resolution_time)");
                $stmt->execute([$priority_id, $response_time, $resolution_time]);
                $message = "Paramètres SLA mis à jour avec succès !";
            } catch (PDOException $e) {
                $error = "Erreur : " . $e->getMessage();
            }
        }
    }
}

// --- RÉCUPÉRATION DES PRIORITÉS ET SLA ---
try {
    $stmt = $pdo->query("
        SELECT p.id, p.name, p.level, s.response_time, s.resolution_time 
        FROM priorities p 
        LEFT JOIN sla_settings s ON p.id = s.priority_id 
        ORDER BY p.level ASC
    ");
    $sla_data = $stmt->fetchAll();
} catch (PDOException $e) {
    $sla_data = [];
    $error = "Erreur lors de la récupération : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Paramètres SLA - PFO DSI</title>
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
        .action-btn { width: 36px; height: 36px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; background: #f8fafc; border: 1px solid #e2e8f0; color: #64748b; cursor: pointer; }
        .action-btn:hover { background: #20317b; color: white; transform: scale(1.1); }
        .sla-card { transition: all 0.3s ease; border: 1px solid #eef2f6; border-radius: 15px; }
        .sla-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
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
                            <li class="pc-item active"><a class="pc-link" href="manage-sla.php">SLA / Délais</a></li>
                            <li class="pc-item"><a class="pc-link" href="manage-auto-expiration.php">Expiration auto.</a></li>
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
                                <h2 class="m-b-10" style="font-weight: 800; color: #1e293b;">Paramètres <span class="text-primary">SLA / Délais</span></h2>
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
                    <i class="ti ti-alert-circle me-2"></i> <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-12">
                    <div class="card border-0 shadow-sm" style="border-radius: 20px;">
                        <div class="card-header bg-white border-0 py-4 px-4">
                            <h4 class="mb-0" style="font-weight: 700; color: #1e293b;">Délais de traitement par priorité</h4>
                            <p class="text-muted mb-0">Définissez les temps maximum de réponse et de résolution attendus.</p>
                        </div>
                        <div class="card-body p-4">
                            <div class="row">
                                <?php foreach ($sla_data as $row): ?>
                                    <div class="col-xl-4 col-md-6 mb-4">
                                        <div class="card sla-card p-4">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h5 class="mb-0 fw-800 text-dark"><?= htmlspecialchars($row['name']) ?></h5>
                                                <span class="badge bg-light-primary text-primary">Niveau <?= $row['level'] ?></span>
                                            </div>
                                            <form action="" method="POST">
                                                <input type="hidden" name="action" value="update_sla">
                                                <input type="hidden" name="priority_id" value="<?= $row['id'] ?>">
                                                <div class="row g-3">
                                                    <div class="col-12">
                                                        <label class="form-label small text-uppercase fw-700">Temps de réponse (Heures)</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-light border-0"><i class="ti ti-clock-play"></i></span>
                                                            <input type="number" name="response_time" class="form-control bg-light border-0" value="<?= $row['response_time'] ?? 4 ?>" min="1">
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label small text-uppercase fw-700">Temps de résolution (Heures)</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-light border-0"><i class="ti ti-circle-check"></i></span>
                                                            <input type="number" name="resolution_time" class="form-control bg-light border-0" value="<?= $row['resolution_time'] ?? 24 ?>" min="1">
                                                        </div>
                                                    </div>
                                                    <div class="col-12 mt-4">
                                                        <?php if (in_array($admin_role, ['ADMIN', 'SUPERVISOR'])): ?>
                                                            <button type="submit" class="btn btn-primary w-100 rounded-pill">Mettre à jour</button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-secondary w-100 rounded-pill" disabled>Lecture seule</button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
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

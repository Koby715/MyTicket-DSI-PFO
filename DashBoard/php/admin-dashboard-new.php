<?php
/**
 * admin-dashboard.php
 * Dashboard d'administration complet et premium optimisé UX
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

// --- RÉCUPÉRATION DES STATISTIQUES (KPI) ---
try {
    // 📂 Tickets ouverts (Nouveau, En cours, En attente)
    $stmt = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status_id IN (SELECT id FROM statuses WHERE label IN ('Nouveau', 'En cours', 'En attente'))");
    $stats['open'] = $stmt->fetchColumn();

    // 🕒 En attente utilisateur
    $stmt = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status_id IN (SELECT id FROM statuses WHERE label = 'En attente')");
    $stats['pending_user'] = $stmt->fetchColumn();

    // ✅ Résolus aujourd'hui
    $stmt = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status_id IN (SELECT id FROM statuses WHERE label = 'Résolu') AND DATE(updated_at) = CURDATE()");
    $stats['resolved_today'] = $stmt->fetchColumn();

    // 🔴 Tickets urgents
    $stmt = $pdo->query("SELECT COUNT(*) FROM tickets WHERE priority_id IN (SELECT id FROM priorities WHERE name IN ('Urgent', 'Critique', 'Haute', 'Élevée')) AND status_id NOT IN (SELECT id FROM statuses WHERE label IN ('Résolu', 'Fermé'))");
    $stats['urgent'] = $stmt->fetchColumn();

    // 👤 Tickets assignés à moi
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE assigned_to = ? AND status_id NOT IN (SELECT id FROM statuses WHERE label IN ('Résolu', 'Fermé'))");
    $stmt->execute([$admin_id]);
    $stats['assigned_to_me'] = $stmt->fetchColumn();

    // ⏳ Temps moyen de réponse
    $stats['avg_response_time'] = "2h 15m"; // Valeur simulée car requiert une table d'historique
    $stats['expired_count'] = 0; // Sera calculé dynamiquement dans la boucle pour l'instant

} catch (PDOException $e) {
    error_log("Erreur Stats Dashboard: " . $e->getMessage());
    $stats = ['open' => 0, 'pending_user' => 0, 'resolved_today' => 0, 'urgent' => 0, 'assigned_to_me' => 0, 'avg_response_time' => 'N/A', 'expired_count' => 0];
}

// --- LOGIQUE DE FILTRAGE ÉVOLUÉE ---
$where_clauses = ["1=1"];
$params = [];

// Recherche globale
if (!empty($_GET['search'])) {
    $search = "%" . trim($_GET['search']) . "%";
    $where_clauses[] = "(t.reference LIKE ? OR t.subject LIKE ? OR t.email LIKE ? OR t.nom LIKE ?)";
    $params[] = $search; $params[] = $search; $params[] = $search; $params[] = $search;
}

// Filtres simples
if (!empty($_GET['status'])) {
    $where_clauses[] = "t.status_id = ?";
    $params[] = $_GET['status'];
}
if (!empty($_GET['category'])) {
    $where_clauses[] = "t.category_id = ?";
    $params[] = $_GET['category'];
}
if (!empty($_GET['priority'])) {
    $where_clauses[] = "t.priority_id = ?";
    $params[] = $_GET['priority'];
}
if (!empty($_GET['assigned_to'])) {
    if ($_GET['assigned_to'] === 'NULL') {
        $where_clauses[] = "t.assigned_to IS NULL";
    } else {
        $where_clauses[] = "t.assigned_to = ?";
        $params[] = $_GET['assigned_to'];
    }
}

// Filtres de dates
if (!empty($_GET['date_from'])) {
    $where_clauses[] = "DATE(t.created_at) >= ?";
    $params[] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $where_clauses[] = "DATE(t.created_at) <= ?";
    $params[] = $_GET['date_to'];
}

// Filtres rapides (Sidebar ou KPI)
$current_filter = $_GET['filter'] ?? 'all';
switch ($current_filter) {
    case 'mine':
        $where_clauses[] = "t.assigned_to = ?";
        $params[] = $admin_id;
        break;
    case 'unassigned':
        $where_clauses[] = "t.assigned_to IS NULL";
        break;
    case 'urgent':
        $where_clauses[] = "p.name IN ('Urgent', 'Critique')";
        $where_clauses[] = "s.label NOT IN ('Résolu', 'Fermé')";
        break;
    case 'pending_user':
        $where_clauses[] = "s.label = 'En attente'";
        break;
    case 'closed':
        $where_clauses[] = "s.label IN ('Résolu', 'Fermé')";
        break;
}

$where_sql = implode(" AND ", $where_clauses);

// --- RÉCUPÉRATION DES TICKETS ---
try {
    $sql = "SELECT t.*, 
                   c.name as category_name, 
                   p.name as priority_name, 
                   s.label as status_label,
                   u.name as assigned_name
            FROM tickets t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN priorities p ON t.priority_id = p.id
            LEFT JOIN statuses s ON t.status_id = s.id
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE $where_sql
            ORDER BY t.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur Fetch Tickets: " . $e->getMessage());
    $tickets = [];
}

// Référentiels pour les selects
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$priorities = $pdo->query("SELECT id, name FROM priorities ORDER BY name")->fetchAll();
$statuses = $pdo->query("SELECT id, label FROM statuses ORDER BY label")->fetchAll();
$agents = $pdo->query("SELECT id, name FROM users WHERE role IN ('ADMIN', 'AGENT', 'SUPERVISOR') ORDER BY name")->fetchAll();

/**
 * Logique SLA simulée : Retourne la date d'expiration
 */
function getExpiryDate($created_at, $priority) {
    $date = new DateTime($created_at);
    switch (strtolower($priority)) {
        case 'urgent': case 'critique': $date->modify('+4 hours'); break;
        case 'haute': case 'élevée': $date->modify('+24 hours'); break;
        case 'moyenne': $date->modify('+48 hours'); break;
        default: $date->modify('+72 hours'); break;
    }
    return $date;
}

function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'nouveau': return 'bg-light-primary text-primary';
        case 'en cours': return 'bg-light-warning text-warning';
        case 'en attente': return 'bg-light-info text-info';
        case 'résolu': return 'bg-light-success text-success';
        case 'fermé': return 'bg-light-secondary text-secondary';
        default: return 'bg-light-primary text-primary';
    }
}

function getPriorityBadgeClass($priority) {
    switch (strtolower($priority)) {
        case 'urgent': case 'critique': return 'badge-light-danger';
        case 'haute': case 'élevée': return 'badge-light-warning';
        case 'moyenne': return 'badge-light-primary';
        default: return 'badge-light-success';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Dashboard Admin - PFO DSI</title>
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
        .kpi-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: none;
            border-radius: 16px;
            overflow: hidden;
            position: relative;
        }
        .kpi-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(32, 49, 123, 0.12) !important;
        }
        .kpi-icon {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin-bottom: 20px;
        }
        .secondary-indicator {
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 15px;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .sidebar-logo {
            padding: 20px;
            text-align: center;
        }
        .sidebar-logo img {
            max-width: 150px;
            filter: none;
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
            filter: none; /* Supprime l'inversion de couleur pour le mode clair */
        }
        .pc-sidebar .pc-link {
            border-radius: 10px;
            margin: 2px 15px;
            padding: 12px 15px;
            font-weight: 500;
            color: #585978; /* Couleur du texte de la page d'accueil */
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
        .pc-sidebar .pc-mtext {
            color: inherit;
        }
        .pc-sidebar .pc-micon {
            color: inherit;
        }
        .pc-sidebar .pc-caption {
            color: #94a3b8;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.70rem;
            letter-spacing: 0.5px;
            padding: 20px 30px 5px;
        }
        .sidebar-logo a {
            display: block;
        }
        .pc-sidebar .pc-arrow {
            color: #94a3b8;
        }
        .pc-sidebar .pc-item.pc-hasmenu.active > .pc-link {
            background: #f0f4ff !important;
            color: #20317b !important;
        }
        .table-urgent {
            background-color: rgba(239, 68, 68, 0.03) !important;
            border-left: 4px solid #ef4444 !important;
        }
        .table-expired {
            background-color: rgba(0, 0, 0, 0.02) !important;
            border-left: 4px solid #334155 !important;
        }
        .status-badge {
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
        }
        .action-btn:hover {
            background: #fff;
            color: var(--pc-primary);
            border-color: var(--pc-primary);
            transform: scale(1.1);
        }
        .filter-zone {
            background: #ffffff;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.03);
            border: 1px solid rgba(0,0,0,0.05);
        }
        .form-label {
            font-weight: 600;
            color: #475569;
            font-size: 0.85rem;
            margin-bottom: 8px;
        }
        .badge-light-danger { background: #fee2e2; color: #b91c1c; }
        .badge-light-warning { background: #fef3c7; color: #b45309; }
        .badge-light-primary { background: #e0e7ff; color: #4338ca; }
        .badge-light-success { background: #dcfce7; color: #15803d; }
    </style>
</head>

<body data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light">
    <!-- [ Navigation ] start -->
    <nav class="pc-sidebar">
        <div class="navbar-wrapper">
            <div class="m-header sidebar-logo">
                <a href="admin-dashboard.php" class="b-brand">
                    <img src="../../assets/img/logo/monimage.png" alt="logo" class="logo-lg">
                    <img src="../../assets/img/logo/monimage.png" alt="logo" class="logo-sm" style="max-height: 30px;">
                </a>
            </div>
            <div class="navbar-content">
                <ul class="pc-navbar mt-2">
                    <li class="pc-item <?= ($current_filter == 'all' && !isset($_GET['status'])) ? 'active' : '' ?>">
                        <a href="admin-dashboard.php" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-dashboard"></i></span>
                            <span class="pc-mtext">Dashboard</span>
                        </a>
                    </li>
                    <li class="pc-item pc-caption">
                        <label>Support Client</label>
                    </li>
                    <li class="pc-item pc-hasmenu <?= ($current_filter != 'all') ? 'active' : '' ?>">
                        <a href="#!" class="pc-link"><span class="pc-micon"><i class="ti ti-ticket"></i></span><span class="pc-mtext">Tickets</span><span class="pc-arrow"><i data-feather="chevron-right"></i></span></a>
                        <ul class="pc-submenu">
                            <li class="pc-item"><a class="pc-link" href="?filter=all">Tous les tickets</a></li>
                            <li class="pc-item"><a class="pc-link" href="?filter=mine">Mes tickets</a></li>
                            <li class="pc-item"><a class="pc-link" href="?filter=unassigned">Non assignés</a></li>
                            <li class="pc-item"><a class="pc-link" href="?filter=pending_user">Attente utilisateur</a></li>
                            <li class="pc-item"><a class="pc-link" href="?filter=urgent">Urgents</a></li>
                            <li class="pc-item"><a class="pc-link" href="?filter=closed">Fermés</a></li>
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
                    <li class="pc-item mt-5 pt-3 border-top border-white-10">
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
                    <li class="dropdown pc-h-item">
                        <a class="pc-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#" role="button">
                            <i class="ti ti-bell"></i>
                            <span class="badge bg-danger pc-h-badge">3</span>
                        </a>
                        <div class="dropdown-menu dropdown-notification dropdown-menu-end pc-h-dropdown">
                            <div class="dropdown-header d-flex align-items-center justify-content-between">
                                <h5 class="m-0">Notifications</h5>
                                <a href="#!" class="text-primary f-12">Tout marquer comme lu</a>
                            </div>
                            <div class="dropdown-divider"></div>
                            <div class="notification-list" style="max-height: 300px; overflow-y: auto;">
                                <div class="dropdown-item d-flex align-items-center">
                                    <div class="avtar avtar-s bg-light-primary text-primary me-3"><i class="ti ti-plus"></i></div>
                                    <div class="flex-grow-1">
                                        <p class="mb-0 f-13">Nouveau ticket #TKT-2026-00045 créé par Jean D.</p>
                                        <small class="text-muted">Il y a 5 min</small>
                                    </div>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <div class="text-center py-2"><a href="#!" class="text-primary f-12">Voir toutes les notifications</a></div>
                        </div>
                    </li>
                    <li class="dropdown pc-h-item header-user-profile">
                        <a class="pc-head-link dropdown-toggle arrow-none me-0 border-0" data-bs-toggle="dropdown" href="#" role="button">
                            <img src="../assets/images/user/avatar-1.jpg" alt="user-image" class="user-avtar">
                            <span>
                                <span class="user-name"><?= htmlspecialchars($admin_name) ?></span>
                                <span class="user-desc"><?= htmlspecialchars($admin_role) ?></span>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end pc-h-dropdown shadow-lg border-0">
                            <div class="dropdown-header"><h5>Bienvenue</h5></div>
                            <a href="#!" class="dropdown-item"><i class="ti ti-user"></i> Mon Profil</a>
                            <a href="#!" class="dropdown-item"><i class="ti ti-settings"></i> Paramètres</a>
                            <div class="dropdown-divider"></div>
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
            <!-- [ Breadcrumb ] start -->
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
            <!-- [ Breadcrumb ] start -->
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h2 class="m-b-10" style="font-weight: 800; color: #1e293b;">Vue d'ensemble <span class="text-primary">Support</span></h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- [ Breadcrumb ] end -->

            <!-- KPI Cards Section -->
            <div class="row">
                <div class="col-md-6 col-xl-2">
                    <div class="card kpi-card shadow-sm" onclick="window.location.href='?filter=all'">
                        <div class="card-body">
                            <div class="kpi-icon bg-light-primary text-primary">
                                <i class="ti ti-ticket"></i>
                            </div>
                            <h6 class="text-muted mb-1 font-weight-600">Tickets ouverts</h6>
                            <h3 class="mb-0" style="font-weight: 700;"><?= $stats['open'] ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2">
                    <div class="card kpi-card shadow-sm" onclick="window.location.href='?filter=pending_user'">
                        <div class="card-body">
                            <div class="kpi-icon bg-light-info text-info">
                                <i class="ti ti-clock-pause"></i>
                            </div>
                            <h6 class="text-muted mb-1 font-weight-600">Attente user</h6>
                            <h3 class="mb-0" style="font-weight: 700;"><?= $stats['pending_user'] ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2">
                    <div class="card kpi-card shadow-sm">
                        <div class="card-body">
                            <div class="kpi-icon bg-light-success text-success">
                                <i class="ti ti-check"></i>
                            </div>
                            <h6 class="text-muted mb-1 font-weight-600">Résolus (Auj.)</h6>
                            <h3 class="mb-0" style="font-weight: 700;"><?= $stats['resolved_today'] ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2">
                    <div class="card kpi-card shadow-sm" onclick="window.location.href='?filter=urgent'">
                        <div class="card-body">
                            <div class="kpi-icon bg-light-danger text-danger">
                                <i class="ti ti-alert-triangle"></i>
                            </div>
                            <h6 class="text-muted mb-1 font-weight-600">Urgents</h6>
                            <h3 class="mb-0" style="font-weight: 700;"><?= $stats['urgent'] ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="card kpi-card shadow-sm bg-primary text-white" onclick="window.location.href='?filter=mine'">
                        <div class="card-body">
                            <div class="kpi-icon bg-white text-primary">
                                <i class="ti ti-user-check"></i>
                            </div>
                            <div class="d-flex justify-content-between align-items-end">
                                <div>
                                    <h6 class="text-white-50 mb-1 font-weight-600">Assignés à moi</h6>
                                    <h3 class="mb-0 text-white" style="font-weight: 800; font-size: 2rem;"><?= $stats['assigned_to_me'] ?></h3>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-white text-primary rounded-pill px-3 py-2">Action requise</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Indicateurs secondaires (pro) -->
            <!-- <div class="row mb-4">
                <div class="col-md-4">
                    <div class="secondary-indicator d-flex align-items-center">
                        <div class="avtar avtar-s bg-light-secondary text-secondary me-3"><i class="ti ti-hourglass-low"></i></div>
                        <div>
                            <p class="mb-0 text-muted f-12">Temps moyen de réponse</p>
                            <h6 class="mb-0 font-weight-700"><?= $stats['avg_response_time'] ?> <span class="text-success f-10"><i class="ti ti-trending-down"></i> -12%</span></h6>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="secondary-indicator d-flex align-items-center">
                        <div class="avtar avtar-s bg-light-secondary text-secondary me-3"><i class="ti ti-device-heart-monitor"></i></div>
                        <div>
                            <p class="mb-0 text-muted f-12">Temps moyen de résolution</p>
                            <h6 class="mb-0 font-weight-700">4h 45m</h6>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="secondary-indicator d-flex align-items-center">
                        <div class="avtar avtar-s bg-light-danger text-danger me-3"><i class="ti ti-calendar-off"></i></div>
                        <div>
                            <p class="mb-0 text-muted f-12">Tickets expirés (SLA)</p>
                            <h6 class="mb-0 font-weight-700">3 actifs</h6>
                        </div>
                    </div>
                </div>
            </div> -->

            <!-- Zone de filtres & actions -->
            <div class="filter-zone">
                <form action="" method="GET" id="filterForm">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <label class="form-label">RECHERCHE GLOBALE</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="ti ti-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control border-0 bg-light" placeholder="Référence, Email, Sujet, Nom..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">STATUT</label>
                            <select name="status" class="form-select border-0 bg-light">
                                <option value="">Tous</option>
                                <?php foreach ($statuses as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= (isset($_GET['status']) && $_GET['status'] == $s['id']) ? 'selected' : '' ?>><?= $s['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">PRIORITÉ</label>
                            <select name="priority" class="form-select border-0 bg-light">
                                <option value="">Toutes</option>
                                <?php foreach ($priorities as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= (isset($_GET['priority']) && $_GET['priority'] == $p['id']) ? 'selected' : '' ?>><?= $p['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">DATE (DU)</label>
                            <input type="date" name="date_from" class="form-control border-0 bg-light" value="<?= $_GET['date_from'] ?? '' ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">DATE (AU)</label>
                            <input type="date" name="date_to" class="form-control border-0 bg-light" value="<?= $_GET['date_to'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="row mt-4 align-items-center">
                        <div class="col-md-8 d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-4 d-flex align-items-center gap-2 rounded-pill">
                                <i class="ti ti-adjustments-horizontal"></i> Appliquer les filtres
                            </button>
                            <a href="admin-dashboard.php" class="btn btn-link-secondary text-decoration-none">Réinitialiser</a>
                            <div class="vr mx-2"></div>
                            <button type="button" onclick="exportToExcel()" class="btn btn-outline-success border-0 px-3 d-flex align-items-center gap-2">
                                <i class="ti ti-file-spreadsheet"></i> Exporter Excel
                            </button>
                            <button type="button" class="btn btn-outline-danger border-0 px-3 d-flex align-items-center gap-2">
                                <i class="ti ti-alert-square-rounded"></i> Expirés
                            </button>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="ajout-ticket.php" class="btn btn-dark px-4 d-flex align-items-center gap-2 rounded-pill ms-auto" style="width: fit-content;">
                                <i class="ti ti-plus"></i> Nouveau Ticket
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tableau principal des tickets -->
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 20px;">
                <div class="card-header bg-white border-0 py-4 px-4 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0" style="font-weight: 700; color: #1e293b;">Liste des Tickets <small class="text-muted fw-normal f-14 ms-2">(<?= count($tickets) ?> résultats)</small></h4>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm border-0 bg-light" style="width: 150px;">
                            <option>Tri par défaut</option>
                            <option>Plus récents</option>
                            <option>Urgence</option>
                        </select>
                        <button class="btn btn-light-secondary btn-sm"><i class="ti ti-dots-vertical"></i></button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4" style="width: 40px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="checkAll">
                                        </div>
                                    </th>
                                    <th class="f-12 text-muted fw-600">RÉFÉRENCE</th>
                                    <th class="f-12 text-muted fw-600">OBJET & DEMANDEUR</th>
                                    <th class="f-12 text-muted fw-600">CATÉGORIE</th>
                                    <th class="f-12 text-muted fw-600">PRIORITÉ</th>
                                    <th class="f-12 text-muted fw-600 text-center">STATUT</th>
                                    <th class="f-12 text-muted fw-600">ASSIGNÉ À</th>
                                    <th class="f-12 text-muted fw-600">ACTIVITÉ</th>
                                    <th class="f-12 text-muted fw-600">EXPIRATION (SLA)</th>
                                    <th class="f-12 text-muted fw-600 text-end pe-4">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tickets)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-5">
                                            <div class="py-4">
                                                <i class="ti ti-database-off f-40 text-muted mb-3 d-block"></i>
                                                <h5 class="text-muted">Aucun ticket ne correspond à vos filtres</h5>
                                                <p class="text-muted f-13">Essayez de modifier vos critères de recherche.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tickets as $t): 
                                        $expiry = getExpiryDate($t['created_at'], $t['priority_name'] ?? '');
                                        $now = new DateTime();
                                        $is_expired = ($expiry < $now && strtolower($t['status_label'] ?? '') !== 'résolu' && strtolower($t['status_label'] ?? '') !== 'fermé');
                                        $row_class = (strtolower($t['priority_name'] ?? '') === 'urgent') ? 'table-urgent' : '';
                                        if ($is_expired) $row_class = 'table-expired';
                                    ?>
                                        <tr class="<?= $row_class ?> cursor-pointer ticket-row" data-id="<?= $t['id'] ?>">
                                            <td class="ps-4">
                                                <div class="form-check">
                                                    <input class="form-check-input checkItem" type="checkbox" value="<?= $t['id'] ?>">
                                                </div>
                                            </td>
                                            <td>
                                                <span class="fw-700 text-primary">#<?= htmlspecialchars($t['reference']) ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <h6 class="mb-0 fw-600"><?= htmlspecialchars($t['subject']) ?></h6>
                                                        <span class="text-muted f-12"><?= htmlspecialchars($t['nom']) ?> (<?= htmlspecialchars($t['email']) ?>)</span>
                                                    </div>
                                                    <div class="ms-2 d-flex gap-1">
                                                        <?php if (!empty($t['attachments'])): ?>
                                                            <i class="ti ti-paperclip text-muted" data-bs-toggle="tooltip" title="Contient des pièces jointes"></i>
                                                        <?php endif; ?>
                                                        <i class="ti ti-message-2 text-primary f-14" data-bs-toggle="tooltip" title="2 nouvelles réponses"></i>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-light-secondary text-dark border-0"><?= htmlspecialchars($t['category_name'] ?? 'N/A') ?></span></td>
                                            <td>
                                                <span class="badge <?= getPriorityBadgeClass($t['priority_name'] ?? '') ?> px-2 py-1">
                                                    <?= htmlspecialchars($t['priority_name'] ?? 'Normale') ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="status-badge <?= getStatusBadgeClass($t['status_label'] ?? '') ?>">
                                                    <?= htmlspecialchars($t['status_label'] ?? 'Nouveau') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($t['assigned_name']): ?>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avtar avtar-xs bg-light-primary text-primary me-2 f-10" title="<?= htmlspecialchars($t['assigned_name']) ?>">
                                                            <?= strtoupper(substr($t['assigned_name'], 0, 1)) ?>
                                                        </div>
                                                        <span class="f-13 fw-500"><?= htmlspecialchars($t['assigned_name']) ?></span>
                                                        <?php if (in_array($admin_role, ['ADMIN', 'SUPERVISOR'])): ?>
                                                            <a href="javascript:void(0)" class="ms-2 text-muted btn-assign-trigger" data-id="<?= $t['id'] ?>" data-ref="<?= $t['reference'] ?>" data-current="<?= $t['assigned_to'] ?>">
                                                                <i class="ti ti-rotate"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <?php if (in_array($admin_role, ['ADMIN', 'SUPERVISOR'])): ?>
                                                        <button class="btn btn-sm btn-outline-primary py-0 px-2 f-11 rounded-pill btn-assign-trigger" data-id="<?= $t['id'] ?>" data-ref="<?= $t['reference'] ?>">Choisir un agent</button>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-secondary text-secondary rounded-pill f-11 px-2 border-0">Non assigné</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="f-12 text-muted">
                                                <i class="ti ti-clock f-14 me-1"></i>
                                                <?= date('d/m H:i', strtotime($t['updated_at'])) ?>
                                            </td>
                                            <td>
                                                <span class="f-13 <?= $is_expired ? 'text-danger fw-700' : 'text-muted' ?>">
                                                    <?= $is_expired ? 'EXPIRÉ' : $expiry->format('d/m H:i') ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="d-flex gap-1 justify-content-end">
                                                    <a href="javascript:void(0)" class="action-btn btn-view-trigger" data-id="<?= $t['id'] ?>" data-bs-toggle="tooltip" title="Voir les détails">
                                                        <i class="ti ti-eye"></i>
                                                    </a>
                                                    <div class="dropdown">
                                                        <a href="javascript:void(0)" class="action-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Actions rapides">
                                                            <i class="ti ti-dots"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="border-radius: 12px; min-width: 180px;">
                                                            <li><h6 class="dropdown-header f-11 text-muted text-uppercase">Actions rapides</h6></li>
                                                            <li><a class="dropdown-item btn-view-trigger" href="javascript:void(0)" data-id="<?= $t['id'] ?>"><i class="ti ti-file-info me-2"></i> Voir détails</a></li>
                                                            <?php if (in_array($admin_role, ['ADMIN', 'SUPERVISOR'])): ?>
                                                                <li><a class="dropdown-item btn-assign-trigger" href="javascript:void(0)" data-id="<?= $t['id'] ?>" data-ref="<?= $t['reference'] ?>" data-current="<?= $t['assigned_to'] ?>"><i class="ti ti-user-plus me-2"></i> Réassigner</a></li>
                                                            <?php endif; ?>
                                                            <li><a class="dropdown-item text-warning btn-quick-action" href="javascript:void(0)" data-id="<?= $t['id'] ?>" data-action="hold"><i class="ti ti-clock-pause me-2"></i> Mettre en attente</a></li>
                                                            <li><div class="dropdown-divider"></div></li>
                                                            <li><a class="dropdown-item text-success fw-600 btn-quick-action" href="javascript:void(0)" data-id="<?= $t['id'] ?>" data-action="resolve"><i class="ti ti-check me-2"></i> Résoudre</a></li>
                                                            <?php if ($admin_role === 'ADMIN'): ?>
                                                                <li><a class="dropdown-item text-danger btn-quick-action" href="javascript:void(0)" data-id="<?= $t['id'] ?>" data-action="delete"><i class="ti ti-trash me-2"></i> Supprimer</a></li>
                                                            <?php endif; ?>
                                                            <?php if (strtolower($t['status_label'] ?? '') === 'résolu' || strtolower($t['status_label'] ?? '') === 'fermé'): ?>
                                                                <li><a class="dropdown-item text-info btn-view-report" href="javascript:void(0)" data-id="<?= $t['id'] ?>"><i class="ti ti-report me-2"></i> Voir le rapport</a></li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 py-3 px-4">
                    <nav class="d-flex justify-content-between align-items-center">
                        <div class="f-13 text-muted">Affichage de <b><?= count($tickets) ?></b> tickets sur un total de <b><?= count($tickets) ?></b></div>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item disabled"><a class="page-link shadow-none border-0 bg-light me-1" href="#"><i class="ti ti-chevron-left"></i></a></li>
                            <li class="page-item active"><a class="page-link shadow-sm border-0 rounded-pill mx-1" href="#">1</a></li>
                            <li class="page-item"><a class="page-link shadow-none border-0 bg-light ms-1" href="#"><i class="ti ti-chevron-right"></i></a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->

    <!-- Modal d'Assignation -->
    <div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="modal-title fw-700">Assigner le ticket <span id="assign-ticket-ref" class="text-primary"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label fw-600">Choisir le responsable</label>
                        <select id="select-assign-agent" class="form-select bg-light border-0 py-2">
                            <option value="">-- Sélectionner un Agent --</option>
                            <?php foreach ($agents as $agent): ?>
                                <option value="<?= $agent['id'] ?>"><?= htmlspecialchars($agent['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <input type="hidden" id="assign-ticket-id">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" id="confirmAssignBtn" class="btn btn-primary rounded-pill px-4">Confirmer l'assignation</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Visualisation Détails Ticket -->
    <div class="modal fade" id="ticketDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 pt-4 px-4">
                    <div>
                        <h5 class="modal-title fw-700 mb-0">Ticket <span id="modal-ref" class="text-primary"></span></h5>
                        <p class="text-muted f-12 mb-0" id="modal-date-info"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 pt-2">
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <label class="f-11 text-muted text-uppercase fw-600 mb-1">Statut</label>
                            <div id="modal-status"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="f-11 text-muted text-uppercase fw-600 mb-1">Priorité</label>
                            <div id="modal-priority"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="f-11 text-muted text-uppercase fw-600 mb-1">Catégorie</label>
                            <div id="modal-category" class="fw-600"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="f-11 text-muted text-uppercase fw-600 mb-1">Assigné à</label>
                            <div id="modal-assigned" class="fw-600 text-primary"></div>
                        </div>
                    </div>

                    <div class="bg-light p-3 rounded-4 mb-4">
                        <label class="f-11 text-muted text-uppercase fw-600 mb-2">Demandeur</label>
                        <div class="d-flex align-items-center">
                            <div class="avtar avtar-s bg-primary text-white me-3" id="modal-user-avatar"></div>
                            <div>
                                <h6 class="mb-0 fw-700" id="modal-user-name"></h6>
                                <p class="mb-0 text-muted f-13" id="modal-user-email"></p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="f-11 text-muted text-uppercase fw-600 mb-1">Objet</label>
                        <h5 class="fw-700" id="modal-subject"></h5>
                    </div>

                    <div class="mb-4 text-break">
                        <label class="f-11 text-muted text-uppercase fw-600 mb-2">Description</label>
                        <div id="modal-description" class="p-3 border rounded-4 bg-white" style="min-height: 100px; white-space: pre-wrap;"></div>
                    </div>

                    <div id="modal-attachments-container" class="d-none">
                        <label class="f-11 text-muted text-uppercase fw-600 mb-2">Pièces jointes</label>
                        <div id="modal-attachments" class="d-flex flex-wrap gap-2"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4 bg-light bg-opacity-50">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Fermer</button>
                    
                    <button type="button" id="modal-btn-assign" class="btn btn-outline-primary rounded-pill px-4 d-none">Assigner</button>
                    <button type="button" id="modal-btn-reply" class="btn btn-primary rounded-pill px-4">Répondre</button>
            </div>
        </div>
    </div>
    </div>

    <div class="modal fade" id="reportResolutionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="modal-title fw-700">Rapport de Résolution - <span id="report-ticket-ref" class="text-primary"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="reportResolutionForm" enctype="multipart/form-data">
                    <div class="modal-body px-4">
                        <input type="hidden" name="ticket_id" id="report-ticket-id">
                        <div class="mb-3">
                            <label class="form-label fw-600 text-uppercase f-12">Commentaire de résolution <span class="text-danger">*</span></label>
                            <textarea name="report_content" id="report-content" class="form-control bg-light border-0" rows="5" placeholder="Décrivez en détail comment le problème a été résolu..." required minlength="10"></textarea>
                            <div class="d-flex justify-content-between mt-1"><small class="text-muted" id="report-char-count">0 / 5000 caractères</small></div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-600 text-uppercase f-12">Pièce jointe (optionnel)</label>
                            <input type="file" name="report_attachment" id="report-attachment" class="form-control bg-light border-0" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted f-11">Formats acceptés : PDF, JPG, PNG (Max 5Mo)</small>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pb-4 px-4">
                        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" id="submitReportBtn" class="btn btn-success rounded-pill px-4"><i class="ti ti-check me-1"></i> Confirmer la résolution</button>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/resolution-report.js"></script>

    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })

        // Gestion de l'ouverture du modal d'assignation
        const assignModalElem = document.getElementById('assignModal');
        const assignModal = assignModalElem ? new bootstrap.Modal(assignModalElem) : null;
        
        document.querySelectorAll('.btn-assign-trigger').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (!assignModal) return;
                
                const id = this.dataset.id;
                const ref = this.dataset.ref;
                const currentAgent = this.dataset.current || "";
                
                document.getElementById('assign-ticket-id').value = id;
                document.getElementById('assign-ticket-ref').textContent = ref;
                document.getElementById('select-assign-agent').value = currentAgent;
                assignModal.show();
            });
        });

        // Confirmation de l'assignation
        const confirmBtn = document.getElementById('confirmAssignBtn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function() {
                const ticketId = document.getElementById('assign-ticket-id').value;
                const agentId = document.getElementById('select-assign-agent').value;

                if(!agentId) {
                    Swal.fire('Erreur', 'Veuillez sélectionner un agent.', 'warning');
                    return;
                }

                const formData = new FormData();
                formData.append('ticket_id', ticketId);
                formData.append('agent_id', agentId);

                fetch('assign-ticket.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Succès',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Erreur', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Erreur', 'Une erreur est survenue lors de la communication avec le serveur.', 'error');
                });
            });
        }

        // Check all functionality
        document.getElementById('checkAll')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.checkItem');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        // Row interaction
        document.querySelectorAll('.ticket-row').forEach(row => {
            row.addEventListener('click', function(e) {
                // Empêcher l'ouverture si on clique sur une zone interactive (checkbox, boutons)
                if (e.target.closest('.form-check') || e.target.closest('.action-btn') || e.target.closest('.btn-assign-trigger') || e.target.closest('.dropdown-menu')) {
                    return;
                }
                const id = this.dataset.id;
                showTicketDetails(id);
            });
        });

        // Bouton "Voir les détails"
        document.querySelectorAll('.btn-view-trigger').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const id = this.dataset.id;
                showTicketDetails(id);
            });
        });

        const detailModal = new bootstrap.Modal(document.getElementById('ticketDetailModal'));

        function showTicketDetails(ticketId) {
            Swal.fire({
                title: 'Chargement...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            fetch(`get-ticket-details.php?id=${ticketId}`)
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        const t = data.data;
                        
                        document.getElementById('modal-ref').textContent = '#' + t.reference;
                        document.getElementById('modal-date-info').textContent = 'Créé le ' + new Date(t.created_at).toLocaleString('fr-FR');
                        
                        document.getElementById('modal-status').innerHTML = `<span class="status-badge ${getStatusBadgeClass(t.status_label)}">${t.status_label}</span>`;
                        document.getElementById('modal-priority').innerHTML = `<span class="badge ${getPriorityBadgeClass(t.priority_name)} px-2 py-1">${t.priority_name}</span>`;
                        document.getElementById('modal-category').textContent = t.category_name || 'N/A';
                        document.getElementById('modal-assigned').textContent = t.assigned_name || 'Non assigné';
                        
                        document.getElementById('modal-user-name').textContent = t.nom;
                        document.getElementById('modal-user-email').textContent = t.email;
                        document.getElementById('modal-user-avatar').textContent = (t.nom || '?').substring(0, 1).toUpperCase();
                        
                        document.getElementById('modal-subject').textContent = t.subject;
                        // Enlever les balises HTML de la description (ex: <p></p>)
                        const cleanDescription = (t.description || '').replace(/<\/?[^>]+(>|$)/g, "");
                        document.getElementById('modal-description').textContent = cleanDescription;
                        
                        // Pièces jointes
                        const attContainer = document.getElementById('modal-attachments-container');
                        const attList = document.getElementById('modal-attachments');
                        attList.innerHTML = '';
                        
                        if (t.attachments_list && t.attachments_list.length > 0) {
                            attContainer.classList.remove('d-none');
                            t.attachments_list.forEach(att => {
                                const ext = att.file_name.split('.').pop().toLowerCase();
                                let icon = 'ti-file';
                                if (['jpg','jpeg','png','gif'].includes(ext)) icon = 'ti-photo';
                                if (['pdf'].includes(ext)) icon = 'ti-file-text';
                                
                                attList.innerHTML += `
                                    <a href="${att.file_path.replace(/\\/g, '/')}" target="_blank" class="btn btn-sm btn-light-secondary d-flex align-items-center gap-2 border">
                                        <i class="ti ${icon}"></i> 
                                        <span class="f-12">${att.file_name}</span>
                                    </a>`;
                            });
                        } else {
                            attContainer.classList.add('d-none');
                        }
                        
                        // Boutons contextuels
                        const btnAssign = document.getElementById('modal-btn-assign');
                        const btnViewReport = document.getElementById('modal-btn-view-report');
                        
                        if (btnViewReport) {
                            if (['résolu', 'fermé'].includes((t.status_label || '').toLowerCase())) {
                                btnViewReport.classList.remove('d-none');
                                btnViewReport.dataset.id = t.id;
                                btnViewReport.classList.add('btn-view-report');
                            } else {
                                btnViewReport.classList.add('d-none');
                                btnViewReport.classList.remove('btn-view-report');
                            }
                        }
                        
                        if (!t.assigned_to && <?= json_encode(in_array($admin_role, ['ADMIN', 'SUPERVISOR'])) ?>) {
                            btnAssign.classList.remove('d-none');
                            btnAssign.onclick = () => {
                                detailModal.hide();
                                document.querySelector(`.btn-assign-trigger[data-id="${t.id}"]`)?.click();
                            };
                        } else {
                            btnAssign.classList.add('d-none');
                        }
                        detailModal.show();
                    } else {
                        Swal.fire('Erreur', data.message, 'error');
                    }
                })
                .catch(error => {
                    Swal.close();
                    console.error('Error:', error);
                    Swal.fire('Erreur', 'Impossible de récupérer les détails.', 'error');
                });
        }

        function getStatusBadgeClass(status) {
            switch ((status || '').toLowerCase()) {
                case 'nouveau': return 'bg-light-primary text-primary';
                case 'en cours': return 'bg-light-warning text-warning';
                case 'en attente': return 'bg-light-info text-info';
                case 'résolu': return 'bg-light-success text-success';
                case 'fermé': return 'bg-light-secondary text-secondary';
                default: return 'bg-light-primary text-primary';
            }
        }

        function getPriorityBadgeClass(priority) {
            switch ((priority || '').toLowerCase()) {
                case 'urgent': case 'critique': return 'badge-light-danger';
                case 'haute': case 'élevée': return 'badge-light-warning';
                case 'moyenne': return 'badge-light-primary';
                default: return 'badge-light-success';
            }
        }

        // Export Excel (Logique intelligente : Sélection ou Filtres)
        function exportToExcel() {
            const checkedItems = document.querySelectorAll('.checkItem:checked');
            
            if (checkedItems.length > 0) {
                // Si des lignes sont cochées, on exporte uniquement celles-là
                const selectedIds = Array.from(checkedItems).map(cb => cb.value).join(',');
                window.location.href = 'export-tickets-excel.php?ids=' + selectedIds;
            } else {
                // Sinon, on exporte selon les filtres globaux du formulaire
                const form = document.getElementById('filterForm');
                const params = new URLSearchParams(new FormData(form)).toString();
                window.location.href = 'export-tickets-excel.php?' + params;
            }
        }

        // Actions rapides (Mettre en attente, Résoudre, Supprimer)
        document.querySelectorAll('.btn-quick-action').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const id = this.dataset.id;
                const action = this.dataset.action;
                let actionText = "";
                let confirmText = "";
                let confirmColor = "#3085d6";

                switch(action) {
                    case 'hold': 
                        actionText = "Mettre en attente"; 
                        confirmText = "Oui, mettre en attente";
                        break;
                    case 'resolve': 
                        // Ce cas est géré par delegation dans resolution-report.js
                        return;
                    case 'delete': 
                        actionText = "Supprimer"; 
                        confirmText = "Oui, supprimer définitivement";
                        confirmColor = "#dc3545";
                        break;
                }

                Swal.fire({
                    title: `Voulez-vous vraiment ${actionText.toLowerCase()} ce ticket?`,
                    text: action === 'delete' ? "Cette action est irréversible !" : "Le statut du ticket sera mis à jour.",
                    icon: action === 'delete' ? 'warning' : 'question',
                    showCancelButton: true,
                    confirmButtonColor: confirmColor,
                    cancelButtonColor: '#aaa',
                    confirmButtonText: confirmText,
                    cancelButtonText: 'Annuler'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('ticket_id', id);
                        formData.append('action', action);

                        fetch('quick-ticket-actions.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Effectué !',
                                    text: data.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Erreur', data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire('Erreur', 'Une erreur est survenue lors de la communication avec le serveur.', 'error');
                        });
                    }
                });
            });
        });

                // Compteur de caractères pour le rapport de résolution
        const reportTextarea = document.getElementById('report-content');
        const charCountDisplay = document.getElementById('report-char-count');
        if (reportTextarea && charCountDisplay) {
            reportTextarea.addEventListener('input', function() {
                const count = this.value.length;
                charCountDisplay.textContent = `${count} / 5000 caractères`;
                charCountDisplay.classList.toggle('text-danger', count > 5000);
            });
        } 
        layout_change('light');
        change_box_container('false');
        layout_rtl_change('false');
        

        // Bouton "Voir le rapport"
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-view-report');
            if (!btn) return;
            
            e.preventDefault();
            e.stopPropagation();
            
            const id = btn.dataset.id;
            
            Swal.fire({
                title: 'Récupération du rapport...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            fetch(`get-rapport.php?ticket_id=${id}`)
                .then(response => {
                    if (!response.ok) throw new Error('Erreur réseau');
                    return response.json();
                })
                .then(data => {
                    Swal.close();
                    if (data.success && data.data) {
                        const r = data.data;
                        let attsHtml = "";
                        if (r.attachments && r.attachments.length > 0) {
                            attsHtml = '<div class="mt-3 text-start"><label class="f-11 fw-600 text-muted mb-2 text-uppercase">Pièces jointes :</label><div class="d-flex flex-wrap gap-2">';
                            r.attachments.forEach(a => {
                                let fPath = a.file_path.replace(/\\/g, '/');
                                attsHtml += `<a href="${fPath}" target="_blank" class="btn btn-sm btn-light border p-1 f-10 d-flex align-items-center gap-1"><i class="ti ti-paperclip"></i> ${a.file_name}</a>`;
                            });
                            attsHtml += '</div></div>';
                        }

                        Swal.fire({
                            title: '<span class="fw-700">Rapport de Résolution</span>',
                            html: `
                                <div class="text-start p-2">
                                    <div class="mb-3">
                                        <label class="f-11 fw-600 text-muted text-uppercase d-block mb-1">Agent responsable :</label>
                                        <span class="fw-700 text-primary f-14">${r.agent_name || 'Inconnu'}</span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="f-11 fw-600 text-muted text-uppercase d-block mb-1">Date de clôture :</label>
                                        <span class="f-12 text-dark">${new Date(r.created_at).toLocaleString('fr-FR')}</span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="f-11 fw-600 text-muted text-uppercase d-block mb-1">Commentaire :</label>
                                        <div class="p-3 bg-light rounded-3 f-13 shadow-sm border" style="white-space: pre-wrap; line-height: 1.5; text-align: justify;">${r.report_content}</div>
                                    </div>
                                    ${attsHtml}
                                </div>
                            `,
                            width: '550px',
                            confirmButtonText: 'Fermer',
                            confirmButtonColor: '#20317b',
                            customClass: { popup: 'rounded-4 shadow-lg' }
                        });
                    } else {
                        Swal.fire('Note', 'Aucun rapport détaillé n\'a été saisi pour ce ticket.', 'info');
                    }
                })
                .catch(error => {
                    Swal.close();
                    console.error('Fetch error:', error);
                    Swal.fire('Erreur', 'Impossible de charger les détails du rapport.', 'error');
                });
        });

        preset_change("preset-1");
        font_change("Public-Sans");
    </script>
</body>
</html>
<?php // MODIFIED BY AGENT





// TEST APPEND



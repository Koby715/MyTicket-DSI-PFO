<?php
/**
 * traitement-login-admin.php
 * Traitement de l'authentification des administrateurs
 */

session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Veuillez remplir tous les champs.";
        $_SESSION['old_email'] = $email;
        header("Location: admin-login.php");
        exit;
    }

    try {
        // Rechercher l'utilisateur avec le rôle ADMIN, AGENT ou SUPERVISOR (selon les besoins du dashboard admin)
        // Ici on filtre uniquement pour l'accès administratif
        $stmt = $pdo->prepare("SELECT id, name, email, password, role, is_active FROM users WHERE email = ? AND role IN ('ADMIN', 'SUPERVISOR') LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Vérifier si le compte est actif
            if (!$user['is_active']) {
                $_SESSION['login_error'] = "Votre compte est désactivé. Veuillez contacter l'administrateur.";
                $_SESSION['old_email'] = $email;
                header("Location: admin-login.php");
                exit;
            }

            // Vérifier le mot de passe
            if (password_verify($password, $user['password'])) {
                // Authentification réussie
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_name'] = $user['name'];
                $_SESSION['admin_role'] = $user['role'];
                $_SESSION['admin_email'] = $user['email'];

                // Rediriger vers le dashboard
                header("Location: admin-dashboard.php");
                exit;
            } else {
                $_SESSION['login_error'] = "Identifiants invalides.";
                $_SESSION['old_email'] = $email;
                header("Location: admin-login.php");
                exit;
            }
        } else {
            // Utilisateur non trouvé ou mauvais rôle
            $_SESSION['login_error'] = "Identifiants invalides ou accès non autorisé.";
            $_SESSION['old_email'] = $email;
            header("Location: admin-login.php");
            exit;
        }

    } catch (PDOException $e) {
        error_log("Erreur Login Admin: " . $e->getMessage());
        $_SESSION['login_error'] = "Une erreur technique est survenue.";
        header("Location: admin-login.php");
        exit;
    }
} else {
    header("Location: admin-login.php");
    exit;
}

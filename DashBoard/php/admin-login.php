<?php
/**
 * admin-login.php
 * Formulaire d'authentification des administrateurs
 */

session_start();

// Si déjà connecté, rediriger vers le dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: admin-dashboard.php");
    exit;
}

$error = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : null;
$email = isset($_SESSION['old_email']) ? $_SESSION['old_email'] : '';

// Nettoyer les messages d'erreur après affichage
unset($_SESSION['login_error']);
unset($_SESSION['old_email']);
?>
<!DOCTYPE html>
<html lang="fr">
<!-- [Head] start -->
<head>
  <title>Connexion Admin - PFO DSI</title>
  <!-- [Meta] -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <!-- [Favicon] icon -->
  <link rel="icon" href="../../assets/img/logo/favicon-pfo.png" type="image/x-icon"> 

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

  <style>
    :root {
      --pfo-blue: #20317b;
      --pfo-gray: #babecf;
      --pfo-white: #ffffff;
    }

    .access-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, var(--pfo-blue) 0%, #162255 100%);
      padding: 2rem;
    }
    
    .access-card {
      background: var(--pfo-white);
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      max-width: 450px;
      width: 100%;
      padding: 3rem;
      animation: slideUp 0.5s ease-out;
    }
    
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .access-header {
      text-align: center;
      margin-bottom: 2rem;
    }
    
    .access-logo {
      max-width: 180px;
      height: auto;
      margin-bottom: 1.5rem;
    }
    
    .access-title {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--pfo-blue);
      margin-bottom: 0.5rem;
    }
    
    .access-subtitle {
      color: var(--pfo-gray);
      font-size: 0.95rem;
    }
    
    .form-group {
      margin-bottom: 1.5rem;
    }
    
    .form-label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: var(--pfo-blue);
      font-size: 0.95rem;
    }
    
    .form-control {
      width: 100%;
      padding: 0.875rem 1rem;
      border: 2px solid var(--pfo-gray);
      border-radius: 10px;
      font-size: 1rem;
      transition: all 0.3s ease;
    }
    
    .form-control:focus {
      outline: none;
      border-color: var(--pfo-blue);
      box-shadow: 0 0 0 4px rgba(32, 49, 123, 0.1);
    }
    
    .btn-submit {
      width: 100%;
      padding: 1rem;
      background: var(--pfo-blue);
      color: var(--pfo-white);
      border: none;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(32, 49, 123, 0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }
    
    .btn-submit:hover {
      background: #162255;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(32, 49, 123, 0.4);
    }
    
    .alert {
      padding: 1rem;
      border-radius: 10px;
      margin-bottom: 1.5rem;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .alert-danger {
      background-color: #fee2e2;
      border: 1px solid #fecaca;
      color: #991b1b;
    }
    
    .back-link {
      text-align: center;
      margin-top: 1.5rem;
    }
    
    .back-link a {
      color: var(--pfo-blue);
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s ease;
    }
    
    .back-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<!-- [Head] end -->

<body data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light">
  <div class="access-container">
    <div class="access-card">
      <div class="access-header">
        <a href="../../index.php">
          <img src="../../assets/img/logo/monimage.png" alt="PFO Logo" class="access-logo">
        </a>
        <h1 class="access-title">Administration</h1>
        <p class="access-subtitle">Connectez-vous à votre espace gestionnaire</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger">
          <i class="ti ti-alert-circle"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="traitement-login-admin.php">
        <div class="form-group">
          <label for="email" class="form-label">
            <i class="ti ti-mail"></i> Adresse Email
          </label>
          <input 
            type="email" 
            class="form-control" 
            id="email" 
            name="email" 
            placeholder="votre.email@pfo-africa.com"
            value="<?= htmlspecialchars($email) ?>"
            required
            autofocus
          >
        </div>

        <div class="form-group">
          <label for="password" class="form-label">
            <i class="ti ti-lock"></i> Mot de passe
          </label>
          <input 
            type="password" 
            class="form-control" 
            id="password" 
            name="password" 
            placeholder="••••••••"
            required
          >
        </div>

        <button type="submit" class="btn-submit">
          <i class="ti ti-login"></i> Se connecter
        </button>
      </form>

      <div class="back-link">
        <a href="../../index.php">
          <i class="ti ti-arrow-left"></i> Retour au portail
        </a>
      </div>
    </div>
  </div>

  <!-- Required Js -->
  <script src="../assets/js/plugins/popper.min.js"></script>
  <script src="../assets/js/plugins/bootstrap.min.js"></script>
  <script src="../assets/js/pcoded.js"></script>
</body>
</html>

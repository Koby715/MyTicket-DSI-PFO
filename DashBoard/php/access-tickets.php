<?php
/**
 * access-tickets.php
 * Page de saisie d'email pour accéder aux tickets
 */

require_once 'config/db.php';

$error = null;
$email = '';

// Si un email est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    if (empty($email)) {
        $error = "Veuillez saisir votre adresse email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Veuillez saisir une adresse email valide.";
    } else {
        try {
            // Vérifier si l'email existe dans la base de données
            $stmt = $pdo->prepare("SELECT token FROM tickets WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // Rediriger vers la page des tickets avec le token
                header("Location: liste-tickets-user.php?token=" . urlencode($result['token']));
                exit;
            } else {
                $error = "Aucun ticket trouvé pour cette adresse email.";
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de la vérification de l'email.";
            error_log("Erreur DB Access Tickets: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<!-- [Head] start -->

<head>
  <title>Accéder à mes Tickets - PFO DSI</title>
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
      max-width: 500px;
      width: 100%;
      padding: 3rem;
      animation: slideUp 0.5s ease-out;
    }
    
    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
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
    }
    
    .btn-submit:hover {
      background: #162255;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(32, 49, 123, 0.4);
    }
    
    .btn-submit:active {
      transform: translateY(0);
    }
    
    .alert {
      padding: 1rem;
      border-radius: 10px;
      margin-bottom: 1.5rem;
      font-size: 0.9rem;
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
      color: #162255;
      text-decoration: underline;
    }
    
    .info-box {
      background: #f8fafc;
      border-left: 4px solid var(--pfo-blue);
      padding: 1rem;
      border-radius: 8px;
      margin-top: 1.5rem;
    }
    
    .info-box p {
      margin: 0;
      font-size: 0.875rem;
      color: #475569;
    }
    
    .info-box i {
      color: var(--pfo-blue);
      margin-right: 0.5rem;
    }
  </style>
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

  <div class="access-container">
    <div class="access-card">
      <div class="access-header">
        <a href="../../index.php">
          <img src="../../assets/img/logo/monimage.png" alt="PFO Logo" class="access-logo">
        </a>
        <!-- <h1 class="access-title">Mes Tickets</h1> -->
        <p class="access-subtitle">Saisissez votre adresse email pour accéder à la liste de vos tickets</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger">
          <i class="ti ti-alert-circle"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-group">
          <label for="email" class="form-label">
            <i class="ti ti-mail"></i> Adresse Mail Professionnelle
          </label>
          <input 
            type="email" 
            class="form-control" 
            id="email" 
            name="email" 
            placeholder="votre.nom@pfo-africa.com"
            value="<?= htmlspecialchars($email) ?>"
            required
            autofocus
          >
        </div>

        <button type="submit" class="btn-submit">
          <i class="ti ti-search"></i> Consulter mes Tickets
        </button>
      </form>

      <!-- <div class="info-box">
        <p>
          <i class="ti ti-info-circle"></i>
          Utilisez l'email utilisé lors de votre dernière demande.
        </p>
      </div> -->

      <div class="back-link">
        <a href="../../index.php">
          <i class="ti ti-arrow-left"></i> Retour au portail
        </a>
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
</body>
<!-- [Body] end -->

</html>

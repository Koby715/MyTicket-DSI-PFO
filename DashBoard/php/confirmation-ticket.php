<!DOCTYPE html>
<html lang="fr">
<!-- [Head] start -->

<head>
  <title>Confirmation Ticket - PFO DSI</title>
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
 <!-- [ Sidebar Menu ] start -->
<nav class="pc-sidebar">
  <div class="navbar-wrapper">
    <div class="m-header">
      <a href="../../index.php" class="b-brand text-primary">
        <!-- ========   Change your logo from here   ============ -->
        <img src="assets/img/logo/monimage.png" alt="logo" style="max-height:40px; width:auto;">
      </a>
    </div>
    <div class="navbar-content">
      <ul class="pc-navbar">
        <li class="pc-item">
          <a href="../../index.php" class="pc-link">
            <span class="pc-micon"><i class="ti ti-home"></i></span>
            <span class="pc-mtext">Accueil</span>
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<!-- [ Sidebar Menu ] end --> 
<!-- [ Header Topbar ] start -->
<header class="pc-header">
  <div class="header-wrapper"> 
<div class="me-auto pc-mob-drp">
  <ul class="list-unstyled">
    <!-- ======= Menu collapse Icon ===== -->
    <li class="pc-h-item pc-sidebar-collapse">
      <a href="#" class="pc-head-link ms-0" id="sidebar-hide">
        <i class="ti ti-menu-2"></i>
      </a>
    </li>
    <li class="pc-h-item pc-sidebar-popup">
      <a href="#" class="pc-head-link ms-0" id="mobile-collapse">
        <i class="ti ti-menu-2"></i>
      </a>
    </li>
  </ul>
</div>
 </div>
</header>
<!-- [ Header ] end -->

  <!-- [ Main Content ] start -->
  <div class="pc-container">
    <div class="pc-content">
      <!-- [ breadcrumb ] start -->
      <div class="page-header">
        <div class="page-block">
          <div class="row align-items-center">
            <div class="col-md-12">
              <div class="page-header-title">
                <h5 class="m-b-10">Ticket</h5>
              </div>
              <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="../../index.php">Accueil</a></li>
                <li class="breadcrumb-item" aria-current="page">Confirmation</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
      <!-- [ breadcrumb ] end -->

      <!-- [ Main Content ] start -->
      <div class="row">
        <!-- [ Confirmation Card ] start -->
        <div class="col-sm-12">
          <div class="card">
            <div class="card-body text-center py-5">
              
              <!-- Success Icon -->
              <div class="mb-4">
                <i class="ti ti-circle-check" style="font-size: 80px; color: #10b981;"></i>
              </div>

              <!-- Success Title -->
              <h2 class="mb-3">Ticket créé avec succès !</h2>

              <!-- Ticket Reference -->
              <div class="alert alert-info mb-4" role="alert">
                <h5 class="mb-2">Numéro de votre ticket</h5>
                <p class="mb-0" style="font-size: 24px; font-weight: bold; color: #1f2937;">
                  <span id="ticket-reference">-</span>
                </p>
              </div>

              <!-- Email Notification -->
              <div class="alert alert-success mb-4" role="alert">
                <i class="ti ti-mail me-2"></i>
                <strong>Un email vient de vous être envoyé</strong>
                <p class="mb-0 mt-2">Veuillez vérifier votre boîte de réception pour plus de détails</p>
              </div>

              <!-- Additional Info -->
              <div class="card bg-light mb-4">
                <div class="card-body">
                  <p class="mb-2"><strong>Conservez ce numéro de ticket</strong></p>
                  <p class="text-muted mb-0">Il vous sera nécessaire pour suivre votre demande</p>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="d-flex gap-2 justify-content-center flex-wrap">
                <a href="ajout-ticket.php" class="btn btn-primary">
                  <i class="ti ti-plus me-2"></i> Nouveau Ticket
                </a>
                <a href="../../index.php" class="btn btn-secondary">
                  <i class="ti ti-home me-2"></i> Accueil
                </a>
              </div>

            </div>
          </div>
        </div>
        <!-- [ Confirmation Card ] end -->
      </div>
      <!-- [ Main Content ] end -->
    </div>
  </div>
  <!-- [ Main Content ] end -->
  <footer class="pc-footer">
    <div class="footer-wrapper container-fluid">
      <div class="row">
        <div class="col-sm my-1">
          <p class="m-0">Ticket Développé par la DSI PFO-Construction | 2026.</p>
        </div>
      </div>
    </div>
  </footer>

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

<!-- Script pour afficher le numéro de ticket depuis URL -->
<script>
  (function () {
    // Récupérer le paramètre 'reference' depuis l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const reference = urlParams.get('reference');
    
    if (reference) {
      document.getElementById('ticket-reference').textContent = reference;
    } else {
      // Si pas de paramètre, rediriger vers le formulaire
      window.location.href = 'ajout-ticket.php';
    }
  })();
</script>

</body>
<!-- [Body] end -->

</html>

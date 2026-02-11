<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title>MonTicket - PFO DSI</title>
    <meta name="description" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
     <!-- [Favicon] icon -->
  <link rel="icon" href="assets/img/logo/favicon-pfo.png" type="image/x-icon"> <!-- [Google Font] Family -->

    <!-- ========================= CSS here ========================= -->
    <link rel="stylesheet" href="assets/css/bootstrap-5.0.0-alpha-2.min.css" />
    <link rel="stylesheet" href="assets/css/LineIcons.2.0.css"/>
    <link rel="stylesheet" href="assets/css/animate.css"/>
    <link rel="stylesheet" href="assets/css/lindy-uikit.css"/>
  </head>
  <body>
    <!-- == preloader start == -->
    <div class="preloader">
      <div class="loader">
        <div class="spinner">
          <div class="spinner-container">
            <div class="spinner-rotator">
              <div class="spinner-left">
                <div class="spinner-circle"></div>
              </div>
              <div class="spinner-right">
                <div class="spinner-circle"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- ========================= preloader end ========================= -->

    <!-- ========================= hero-section-wrapper-2 start ========================= -->
    <section id="home" class="hero-section-wrapper-2">

      <!-- ========================= header-2 start ========================= -->
      <header class="header header-2">
        <div class="navbar-area">
          <div class="container">
            <div class="row align-items-center">
              <div class="col-lg-12">
                <nav class="navbar navbar-expand-lg">
                  <a class="navbar-brand" href="index.php">
                    <img src="assets/img/logo/monimage.png" alt="Logo" />
                  </a>
                  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent2" aria-controls="navbarSupportedContent2" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="toggler-icon"></span>
                    <span class="toggler-icon"></span>
                    <span class="toggler-icon"></span>
                  </button>

                  <div class="collapse navbar-collapse sub-menu-bar" id="navbarSupportedContent2">
                    <ul id="nav2" class="navbar-nav ml-auto">
                      <!-- <li class="nav-item">
                        <a class="page-scroll active" href="#">Accueil</a>
                      </li>
                      <li class="nav-item">
                        <a class="page-scroll" href="#">Services</a>
                      </li>
                      <li class="nav-item">
                        <a class="page-scroll" href="#">About</a>
                      </li>
                      <li class="nav-item">
                        <a class="page-scroll" href="#">Pricing</a>
                      </li>
                      <li class="nav-item">
                        <a class="page-scroll" href="#">Pricing</a>
                      </li> -->
                    </ul>
                    <a href="DashBoard/php/admin-login.php" class="button button-sm radius-10 d-none d-lg-flex">Connexion</a>
                  </div>
                  <!-- navbar collapse -->
                </nav>
                <!-- navbar -->
              </div>
            </div>
            <!-- row -->
          </div>
          <!-- container -->
        </div>
        <!-- navbar area -->
      </header>
      <!-- ========================= header-2 end ========================= -->

      <!-- ========================= hero-2 start ========================= -->
      <div class="hero-section hero-style-2">
        <div class="container">
          <div class="row align-items-center">
            <div class="col-lg-6">
              <div class="hero-content-wrapper">
                <h4 class="wow fadeInUp" data-wow-delay=".2s">Département Informatique</h4>
                <h2 class="mb-30 wow fadeInUp" data-wow-delay=".4s">Gestion de Tickets</h2>
                <p class="mb-50 wow fadeInUp" data-wow-delay=".6s">Centralisez et suivez toutes vos demandes en un seul endroit. 
                  Créez des tickets en quelques clics et suivez leur progression en temps réel. 
                  Une solution simple et intuitive pour améliorer votre support et la collaboration.</p>
                    <div class="d-flex flex-column flex-md-row gap-3">
                    <div class="buttons">
                        <a href="DashBoard/php/ajout-ticket.php"
                        class="button button-lg radius-10">
                        Créer un Ticket
                        </a>
                    </div>

                    <div class="buttons">
                        <a href="DashBoard/php/access-tickets.php"
                        class="button button-lg radius-10">
                        Suivre mes Tickets
                        </a>
                    </div>
                    </div>


              </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image">
                <img src="assets/img/hero/hero-2/ticket-logo.png" alt="Logo Ticket" class="wow fadeInRight img-fluid" data-wow-delay=".2s" style="max-width:800px; width:100%; height:auto; display:block; margin:0 auto;">
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- ========================= hero-2 end ========================= -->

    </section>
    <!-- ========================= hero-section-wrapper-2 end ========================= -->


    <!-- ========================= scroll-top start ========================= -->
    <a href="#" class="scroll-top"> <i class="lni lni-chevron-up"></i> </a>
    <!-- ========================= scroll-top end ========================= -->
		

    <!-- ========================= JS here ========================= -->
    <script src="assets/js/bootstrap.5.0.0.alpha-2-min.js"></script>
    <script src="assets/js/count-up.min.js"></script>
    <script src="assets/js/wow.min.js"></script>
    <script src="assets/js/main.js"></script>
  </body>
</html>

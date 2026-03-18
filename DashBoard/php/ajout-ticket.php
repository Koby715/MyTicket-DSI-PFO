<!DOCTYPE html>
<html lang="fr">
<!-- [Head] start -->

<head>
  <title>Ajoutez un Ticket - PFO DSI</title>
  <!-- [Meta] -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="description" content="Mantis is made using Bootstrap 5 design framework. Download the free admin template & use it for your project.">
  <meta name="keywords" content="Mantis, Dashboard UI Kit, Bootstrap 5, Admin Template, Admin Dashboard, CRM, CMS, Bootstrap Admin Template">
  <meta name="author" content="CodedThemes">

  <!-- [Favicon] icon -->
  <link rel="icon" href="assets/img/logo/favicon-pfo.png" type="image/x-icon"> 

  <!-- [Page specific CSS] start -->
  <link href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/styles/monokai-sublime.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/plugins/quill.core.css">
  <link rel="stylesheet" href="../assets/css/plugins/quill.snow.css">
  <link rel="stylesheet" href="../assets/css/plugins/quill.bubble.css">
      <!-- fileupload-custom css -->
  <link rel="stylesheet" href="../assets/css/plugins/dropzone.min.css">
  <!-- [Page specific CSS] end -->

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
        <li class="pc-item">
          <a href="admin-dashboard-new.php" class="pc-link">
            <span class="pc-micon"><i class="ti ti-list"></i></span>
            <span class="pc-mtext">Tableau de bord</span>
          </a>
        </li>
      </ul>
      <!-- <div class="card text-center">
        <div class="card-body">
          <img src="../assets/images/img-navbar-card.png" alt="images" class="img-fluid mb-2">
          <h5>Upgrade To Pro</h5>
          <p>To get more features and components</p>
          <a href="https://codedthemes.com/item/berry-bootstrap-5-admin-template/" target="_blank"
          class="btn btn-success">Buy Now</a>
        </div>
      </div> -->
    </div>
  </div>
</nav>
<!-- [ Sidebar Menu ] end --> <!-- [ Header Topbar ] start -->
<header class="pc-header">
  <div class="header-wrapper"> <!-- [Mobile Media Block] start -->
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
<!-- [Mobile Media Block end] -->

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
                <li class="breadcrumb-item" aria-current="page">Ajoutez un Ticket</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
      <!-- [ breadcrumb ] end -->

      <!-- [ Main Content ] start -->
      <div class="row">
        <!-- [ sample-page ] start -->
        <div class="col-sm-12">
          <div class="card">
            <div class="card-header">
              <h2>Ajoutez un Ticket</h2>
            </div>
            <div class="card-body">

                  <!-- Messages d'erreur -->
                  <div id="error-alert" class="alert alert-danger alert-dismissible fade" role="alert" style="display: none;">
                    <strong>Erreurs détectées :</strong>
                    <ul class="mb-0 mt-2" id="error-list"></ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>

                  <form id="ticket-form" action="traitement-ticket.php" method="POST" enctype="multipart/form-data">
                    <div class="container">

                      <div class="row">
                        <div class="col-sm-6">
                          <div class="mb-3">
                            <label class="form-label">Nom et Prénoms</label>
                            <input type="text" name="nom" class="form-control" aria-describedby="emailHelp" placeholder="" required>
                          </div>
                        </div>
                        <div class="col-sm-6">
                          <div class="mb-3">
                            <label class="form-label">Adresse Mail</label>
                            <input type="email" name="email" class="form-control" aria-describedby="emailHelp" placeholder="" required>
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-sm-6">
                          <div class="mb-3">
                            <label class="form-label">Catégorie</label>
                            <select name="category_id" class="mb-3 form-select" required>
                              <option value="">-------</option>
                              <option value="">Chargement...</option>
                            </select>
                          </div>
                        </div>
                        <div class="col-sm-6">
                          <div class="mb-3">
                            <label class="form-label">Priorité</label>
                            <select name="priority_id" class="mb-3 form-select" required>
                              <option value="">-------</option>
                              <option value="">Chargement...</option>
                            </select>
                          </div>
                        </div>
                      </div>

                      <div class="mb-3">
                        <label class="form-label" for="subject">Objet</label>
                        <input type="text" name="subject" class="form-control" id="subject" placeholder="Entrez l'objet du ticket">
                      </div>

                      <div id="pc-quil-1" style="height: 250px"> </div>
                      <input type="hidden" name="description" id="ticket-description">
                      <br>
                      <div class="mb-3">
                        <label class="form-label">Pièces jointes (Optionnel)</label>
                        <input type="file" name="attachments[]" multiple class="form-control">
                      </div>

                      <div class="text-center m-t-20">
                        <button type="submit" class="btn btn-primary">Soumettre le Ticket</button>
                      </div>

                    </div>
                  </form>

                  <script>
                    (function () {
                      // Charger les catégories et priorités au chargement de la page
                      loadCategories();
                      loadPriorities();

                      function loadCategories() {
                        fetch('get-categories.php')
                          .then(function (res) { return res.json(); })
                          .then(function (data) {
                            if (data.success) {
                              var select = document.querySelector('select[name="category_id"]');
                              select.innerHTML = '<option value="">Sélectionnez une catégorie</option>';
                              data.categories.forEach(function (cat) {
                                var option = document.createElement('option');
                                option.value = cat.id;
                                option.textContent = cat.name;
                                select.appendChild(option);
                              });
                            }
                          });
                      }

                      function loadPriorities() {
                        fetch('get-priorities.php')
                          .then(function (res) { return res.json(); })
                          .then(function (data) {
                            if (data.success) {
                              var select = document.querySelector('select[name="priority_id"]');
                              select.innerHTML = '<option value="">Sélectionnez une priorité</option>';
                              data.priorities.forEach(function (pri) {
                                var option = document.createElement('option');
                                option.value = pri.id;
                                option.textContent = pri.name;
                                select.appendChild(option);
                              });
                            }
                          });
                      }

                      // Gestion de la soumission du formulaire
                      var form = document.getElementById('ticket-form');
                      var submitBtn = form.querySelector('button[type="submit"]');
                      var originalBtnText = submitBtn.innerHTML;

                      form.addEventListener('submit', function (e) {
                        e.preventDefault();
                        
                        // Désactiver le bouton et afficher le chargement
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Envoi en cours...';

                        var descNode = document.querySelector('#pc-quil-1 .ql-editor');
                        var description = descNode ? descNode.innerHTML : '';
                        document.getElementById('ticket-description').value = description;

                        var fd = new FormData(form);

                        fetch(form.action, {
                          method: 'POST',
                          body: fd
                        })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                          if (data.success) {
                            // Succès : redirection
                            if (data.data && data.data.redirect) {
                              window.location.href = data.data.redirect;
                            } else {
                              alert('Ticket créé avec succès');
                              form.reset();
                              if (descNode) descNode.innerHTML = '';
                            }
                          } else {
                            // Erreur : afficher les messages d'erreur
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalBtnText;

                            var errorAlert = document.getElementById('error-alert');
                            var errorList = document.getElementById('error-list');
                            
                            errorList.innerHTML = '';
                            
                            if (data.errors && data.errors.length > 0) {
                              // Afficher les erreurs de validation
                              data.errors.forEach(function (error) {
                                var li = document.createElement('li');
                                li.textContent = error;
                                errorList.appendChild(li);
                              });
                            } else if (data.message) {
                              // Afficher le message d'erreur général
                              var li = document.createElement('li');
                              li.textContent = data.message;
                              errorList.appendChild(li);
                            } else {
                              var li = document.createElement('li');
                              li.textContent = 'Une erreur est survenue lors de l\'envoi du ticket.';
                              errorList.appendChild(li);
                            }
                            
                            // Afficher l'alerte et scroller vers le haut
                            errorAlert.style.display = 'block';
                            errorAlert.classList.add('show');
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                          }
                        })
                        .catch(function (err) {
                          console.error(err);
                          submitBtn.disabled = false;
                          submitBtn.innerHTML = originalBtnText;
                          
                          var errorAlert = document.getElementById('error-alert');
                          var errorList = document.getElementById('error-list');
                          
                          errorList.innerHTML = '<li>Erreur réseau lors de l\'envoi du ticket.</li>';
                          errorAlert.style.display = 'block';
                          errorAlert.classList.add('show');
                          window.scrollTo({ top: 0, behavior: 'smooth' });
                        });
                      });
                    })();
                  </script>

            </div>
          </div>
        </div>
        <!-- [ sample-page ] end -->
      </div>
      <!-- [ Main Content ] end -->
    </div>
  </div>
  <!-- [ Main Content ] end -->
  <footer class="pc-footer">
    <div class="footer-wrapper container-fluid">
      <div class="row">
        <div class="col-sm my-1">
          <p class="m-0"
            >Ticket Développé par la DSI PFO-Construction | 2026.</p
          >
        </div>
      </div>
    </div>
  </footer> <!-- Required Js -->
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

    

     <!-- [Page Specific JS] start -->
    <script src="../assets/js/plugins/quill.min.js"></script>
    <script>
      (function () {
        var quill = new Quill('#pc-quil-1', {
          modules: {
            toolbar: [
              [
                {
                  header: [1, 2, false]
                }
              ],
              ['bold', 'italic', 'underline'],
              ['image', 'code-block']
            ]
          },
          placeholder: 'Entrez la description du ticket ici...',
          theme: 'snow'
        });
      })();
      (function () {
        var Delta = Quill.import('delta');
        var quill = new Quill('#pc-quil-2', {
          modules: {
            toolbar: true
          },
          placeholder: 'Type your text here...',
          theme: 'bubble'
        });
      })();
    </script>
    <!-- [Page Specific JS] end --> 
</body>
<!-- [Body] end -->

</html>

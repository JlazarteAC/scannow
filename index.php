<?php
  include("connection.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ScanNow</title>
  <script src="./js/popper.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="./css/bootstrap.css" >
    <script src="./js/bootstrap.js"></script>
    <script src="./js/jquery-3.7.1.min.js"></script>
</head>
<body>
<section class="background-radial-gradient overflow-hidden d-flex justify-content-center align-items-center vh-100">

  <div class="container px-4 py-5 px-md-5 text-center text-lg-start my-5">
    <div class="row gx-lg-5 align-items-center mb-5">
      <div class="col-lg-6 mb-5 mb-lg-0" style="z-index: 10">
      <img src="Image/AC.png" class="img-fluid" alt="Responsive image">
      </div>

      <div class="col-lg-6 mb-5 mb-lg-0 position-relative">
        <div class="card bg-glass">
          <div class="card-body px-4 py-5 px-md-5">
            <form name="form" action="login.php" method="POST" class="needs-validation" novalidate>
              <div class="form-floating mb-4">
                <input type="text" name="user" class="form-control" id="floatingInput" autocomplete="username" placeholder="Username" required>
                <label for="floatingInput">Username</label>
                <div class="invalid-feedback">
                  Please enter your username.
                </div>
              </div>
              <div class="form-floating mb-4">
                <input type="password" name="pass" class="form-control" id="floatingPassword" placeholder="Password" required>
                <label for="floatingPassword">Password</label>
                <div class="invalid-feedback">
                  Please enter your password.
                </div>
              </div>
              <div style="display: flex; justify-content: space-between;">
              <button type="submit" name="submit" class="btn btn-primary btn-block mb-4">
                Login
              </button> 
      
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
// Example starter JavaScript for disabling form submissions if there are invalid fields
(function () {
  'use strict'

  // Fetch all the forms we want to apply custom Bootstrap validation styles to
  var forms = document.querySelectorAll('.needs-validation')

  // Loop over them and prevent submission
  Array.prototype.slice.call(forms)
    .forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }

        form.classList.add('was-validated')
      }, false)
    })
})()
</script>
</body>
</html>
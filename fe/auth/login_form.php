<div class="row justify-content-center mb-4">
    <img src="fe/assets/img/logo_rounded.png" class="w-50" alt="Logo"/>
</div>

<?php if (isset($_GET['error'])): ?>
    <p class="text-danger text-center">Invalid email or password</p>
<?php endif; ?>

<form method="POST" action="be/auth/login.php">
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" required>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
    </div>
    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="remember" name="remember">
        <label class="form-check-label" for="remember">Remember me</label>
    </div>
    <button type="submit" class="btn w-100 custom-btn">Submit</button>
</form>

<p class="mt-3 mb-0 text-center">
    Not registered yet? 
    <button type="button" class="btn btn-link text-decoration-none p-0"
            data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#registerModal">
        Signup here!
    </button>
</p>

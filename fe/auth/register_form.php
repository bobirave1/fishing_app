<div class="row justify-content-center mb-3">
    <img src="fe/assets/img/logo_rounded.png" class="w-50" alt="FISHINGLORY Logo"/>
</div>

<form method="POST" action="be/auth/register.php"> 
    <div class="mb-3">
        <label for="regFullName" class="form-label">Full Name</label>
        <input type="text" class="form-control" id="regFullName" name="fullName" placeholder="Enter your full name" required>
    </div>

    <div class="mb-3">
        <label for="regEmail" class="form-label">Email</label>
        <input type="email" class="form-control" id="regEmail" name="email" placeholder="Enter email" required>
    </div>

    <div class="mb-3">
        <label for="regUsername" class="form-label">Username</label>
        <input type="text" class="form-control" id="regUsername" name="username" placeholder="Choose a username" required>
    </div>

    <div class="mb-3">
        <label for="regPassword" class="form-label">Password</label>
        <input type="password" class="form-control" id="regPassword" name="password" placeholder="Create a password" required>
    </div>

    <div class="mb-3">
        <label for="regConfirmPassword" class="form-label">Confirm Password</label>
        <input type="password" class="form-control" id="regConfirmPassword" name="confirmPassword" placeholder="Confirm your password" required>
    </div>

    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="regTerms" name="terms" required>
        <label for="regTerms" class="form-check-label small">
            I agree to the <a href="#" class="text-decoration-none">Terms & Conditions</a>
        </label>
    </div>

    <button type="submit" class="btn w-100 custom-btn">Register</button>
</form>

<p class="mt-3 mb-0 text-center small">
    Already have an account? 
    <button type="button" class="btn btn-link text-decoration-none p-0"
            data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#loginModal">
        Login here!
    </button>
</p>

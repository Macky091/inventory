<?php
/**
 * Register Page
 * Displays the registration form and handles new user creation.
 */

require_once __DIR__ . '/../includes/user.php';
require_once __DIR__ . '/../includes/functions.php';

// Already logged in → redirect
if (isLoggedIn()) {
    redirect('index.php?page=dashboard');
}

$errors = [];
$old    = []; // preserve form values on error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $old = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'username'  => trim($_POST['username']  ?? ''),
            'email'     => trim($_POST['email']     ?? ''),
        ];

        $password        = $_POST['password']        ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // ── Validation ────────────────────────────────────────
        if (empty($old['full_name']))           $errors[] = 'Full name is required.';
        if (empty($old['username']))             $errors[] = 'Username is required.';
        elseif (strlen($old['username']) < 3)    $errors[] = 'Username must be at least 3 characters.';
        elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $old['username']))
                                                 $errors[] = 'Username may only contain letters, numbers, and underscores.';
        if (empty($old['email']))                $errors[] = 'Email address is required.';
        elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL))
                                                 $errors[] = 'Please enter a valid email address.';
        if (empty($password))                    $errors[] = 'Password is required.';
        elseif (strlen($password) < 8)           $errors[] = 'Password must be at least 8 characters.';
        elseif (!preg_match('/[A-Z]/', $password))  $errors[] = 'Password must contain at least one uppercase letter.';
        elseif (!preg_match('/[0-9]/', $password))  $errors[] = 'Password must contain at least one number.';
        if ($password !== $passwordConfirm)      $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            $result = registerUser($old['username'], $old['email'], $password, $old['full_name']);
            if ($result['success']) {
                setFlash('success', 'Account created! Please sign in.');
                redirect('index.php?page=login');
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register — InvenTrack</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css" />
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>

<div class="auth-wrapper">

  <!-- Brand panel -->
  <div class="auth-brand">
    <div class="brand-logo">
      <div class="logo-icon">📦</div>
      <span class="logo-text">InvenTrack</span>
    </div>
    <div class="brand-tagline">
      Join <span>InvenTrack</span><br>Today.
    </div>
    <p class="brand-sub">
      Create an account and start managing your inventory with precision.
    </p>
  </div>

  <!-- Registration form -->
  <div class="auth-form-area">
    <div class="auth-card">

      <h2>Create Account</h2>
      <p class="subtitle">Fill in your details to get started.</p>

      <!-- Error alert -->
      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          <span class="alert-icon">❌</span>
          <div class="alert-body">
            <?php foreach ($errors as $e): ?>
              <div><?= sanitize($e) ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>" />

        <div class="form-group">
          <label><i class="fa-solid fa-id-card"></i> Full Name</label>
          <input type="text" name="full_name" class="form-control"
                 placeholder="Your full name"
                 value="<?= sanitize($old['full_name'] ?? '') ?>" required />
        </div>

        <div class="form-row">
          <div class="form-group">
            <label><i class="fa-solid fa-user"></i> Username</label>
            <input type="text" name="username" class="form-control"
                   placeholder="e.g. john_doe"
                   value="<?= sanitize($old['username'] ?? '') ?>" required />
          </div>
          <div class="form-group">
            <label><i class="fa-solid fa-envelope"></i> Email</label>
            <input type="email" name="email" class="form-control"
                   placeholder="you@example.com"
                   value="<?= sanitize($old['email'] ?? '') ?>" required />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label><i class="fa-solid fa-lock"></i> Password</label>
            <input type="password" name="password" class="form-control"
                   placeholder="Min 8 chars, 1 upper, 1 digit" required />
          </div>
          <div class="form-group">
            <label><i class="fa-solid fa-lock"></i> Confirm Password</label>
            <input type="password" name="password_confirm" class="form-control"
                   placeholder="Repeat password" required />
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-full" style="margin-top:.5rem;">
          <i class="fa-solid fa-user-plus"></i> Create Account
        </button>
      </form>

      <hr class="divider" />

      <p style="text-align:center; font-size:.875rem; color:var(--text-secondary);">
        Already have an account?
        <a href="<?= BASE_URL ?>/index.php?page=login">Sign in</a>
      </p>

    </div>
  </div>

</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>

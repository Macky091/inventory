<?php
/**
 * Login Page
 * Handles the login form display and POST processing.
 */

require_once __DIR__ . '/../includes/user.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect already-authenticated users
if (isLoggedIn()) {
    redirect('index.php?page=dashboard');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Basic input validation
        if (empty($username)) $errors[] = 'Username is required.';
        if (empty($password)) $errors[] = 'Password is required.';

        if (empty($errors)) {
            $result = loginUser($username, $password);
            if ($result['success']) {
                setFlash('success', 'Welcome back, ' . $_SESSION['user_full_name'] . '!');
                redirect('index.php?page=dashboard');
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
  <title>Login — Brancher</title>
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
      <span class="logo-text">Brancher</span>
    </div>
    <div class="brand-tagline">
      Smart <span>Inventory</span>,<br>Smarter Business
    </div>
    <p class="brand-sub">
      Track stock, manage products, and gain insights with ease. Your inventory, simplified.
    </p>
  </div>

  <!-- Login form -->
  <div class="auth-form-area">
    <div class="auth-card">

      <h2>Welcome back 👋</h2>
      <p class="subtitle">Sign in to your bacon and continue.</p>

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

      <!-- Flash message (e.g. after registration) -->
      <?php $flash = getFlash(); if ($flash): ?>
        <div class="alert alert-<?= sanitize($flash['type']) ?>">
          <span class="alert-icon">✅</span>
          <div class="alert-body"><?= sanitize($flash['message']) ?></div>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>" />

        <div class="form-group">
          <label for="username"><i class="fa-solid fa-user"></i> Username</label>
          <input
            type="text"
            id="username"
            name="username"
            class="form-control"
            placeholder="Enter your username"
            value="<?= sanitize($_POST['username'] ?? '') ?>"
            autocomplete="username"
            required
          />
        </div>

        <div class="form-group">
          <label for="password"><i class="fa-solid fa-lock"></i> Password</label>
          <input
            type="password"
            id="password"
            name="password"
            class="form-control"
            placeholder="Enter your password"
            autocomplete="current-password"
            required
          />
        </div>

        <button type="submit" class="btn btn-primary btn-full" style="margin-top:.5rem;">
          <i class="fa-solid fa-right-to-bracket"></i> Sign In
        </button>
      </form>

      <hr class="divider" />

      <p style="text-align:center; font-size:.875rem; color:var(--text-secondary);">
        Create a Branch now?
        <a href="<?= BASE_URL ?>/index.php?page=register">Create one</a>
      </p>

    </div>
  </div>

</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>

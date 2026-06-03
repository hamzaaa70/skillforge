<?php
// =============================================
//  login.php  —  Page 2: User Login
// =============================================
require_once 'includes/auth.php';
require_once 'includes/db.php';

redirect_if_logged_in();

$error   = '';
$success = '';

// ── Process login on POST ───────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $identifier = trim($_POST['identifier'] ?? '');  // username OR email
  $password   = $_POST['password']        ?? '';

  if (empty($identifier) || empty($password)) {
    $error = 'Please fill in all fields.';
  } else {
    // Look up user by username OR email
    $stmt = $conn->prepare(
      "SELECT id, full_name, username, password
       FROM users
       WHERE username = ? OR email = ?
       LIMIT 1"
    );
    $stmt->bind_param('ss', $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
      $user = $result->fetch_assoc();

      // Verify password against bcrypt hash
      if (password_verify($password, $user['password'])) {
        // ✅ Login success — set session
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];

        $stmt->close();
        header('Location: home.php');
        exit;
      } else {
        $error = 'Incorrect password. Please try again.';
      }
    } else {
      $error = 'No account found with that username or email.';
    }
    $stmt->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login — SkillForge</title>
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>
<div class="bg-dots"></div>

<div class="auth-page">
  <div class="auth-card">

    <!-- Logo -->
    <div class="auth-logo">
      <h1>⚡ SkillForge</h1>
      <p>Welcome back! Pick up where you left off.</p>
    </div>

    <div class="card">
      <h2 style="margin-bottom:6px;">Sign In</h2>
      <p class="text-muted text-small" style="margin-bottom:24px;">
        New here? <a href="signup.php">Create an account</a>
      </p>

      <!-- Error message from PHP -->
      <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- Login Form -->
      <form id="login-form" method="POST" action="login.php" novalidate>

        <!-- Username or Email -->
        <div class="form-group">
          <label for="identifier">Username or Email</label>
          <div class="input-wrapper">
            <span class="input-icon">👤</span>
            <input
              type="text" id="identifier" name="identifier"
              class="form-control"
              placeholder="Enter username or email"
              value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>"
              autocomplete="username"
            />
          </div>
          <span class="field-error" id="identifier-error"></span>
        </div>

        <!-- Password -->
        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrapper">
            <span class="input-icon">🔒</span>
            <input
              type="password" id="password" name="password"
              class="form-control"
              placeholder="Your password"
              autocomplete="current-password"
            />
            <button type="button" class="toggle-pw" data-target="password">👁</button>
          </div>
          <span class="field-error" id="password-error"></span>
        </div>

        <!-- Remember Me -->
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:24px;">
          <input type="checkbox" id="remember" name="remember"
            style="width:16px; height:16px; accent-color:var(--accent); cursor:pointer;" />
          <label for="remember" style="font-size:0.875rem; color:var(--text2); cursor:pointer; text-transform:none; font-weight:400;">
            Keep me signed in
          </label>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn btn-primary btn-full" id="login-btn">
          Sign In →
        </button>

      </form>

      <!-- Divider -->
      <div class="divider-text mt-2">or continue with</div>

      <!-- Demo account hint -->
      <div class="alert alert-info" style="margin-top:0;">
        🧪 <strong>Demo:</strong> username <code style="color:var(--accent);">ali_dev</code>
        / password <code style="color:var(--accent);">Test@1234</code>
      </div>

    </div><!-- /card -->
  </div>
</div>

<script src="js/validate.js"></script>
<script>
/* ── Login Form Validation ── */

const loginForm = document.getElementById('login-form');

loginForm.addEventListener('submit', function(e) {
  let valid = true;

  // Validate identifier
  const idVal = document.getElementById('identifier').value.trim();
  if (idVal === '') {
    Validator.showError('identifier', 'Please enter your username or email.');
    valid = false;
  } else {
    Validator.showSuccess('identifier');
  }

  // Validate password
  const pwVal = document.getElementById('password').value;
  if (pwVal === '') {
    Validator.showError('password', 'Password is required.');
    valid = false;
  } else {
    Validator.showSuccess('password');
  }

  if (!valid) {
    e.preventDefault();
    return;
  }

  // Loading state
  const btn = document.getElementById('login-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Signing in…';
});

// Clear error on type
['identifier', 'password'].forEach(id => {
  const el = document.getElementById(id);
  if (el) el.addEventListener('input', () => Validator.clearState(id));
});
</script>
</body>
</html>

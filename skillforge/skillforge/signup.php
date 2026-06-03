<?php
// =============================================
//  signup.php  —  Page 1: User Registration
// =============================================
require_once 'includes/auth.php';   // starts session
require_once 'includes/db.php';     // $conn available

redirect_if_logged_in();            // already logged in? go home

$errors  = [];
$success = '';

// ── Process form on POST ────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // 1. Collect & sanitize input
  $full_name  = trim($_POST['full_name']  ?? '');
  $username   = trim($_POST['username']   ?? '');
  $email      = trim($_POST['email']      ?? '');
  $password   = $_POST['password']        ?? '';
  $confirm_pw = $_POST['confirm_pw']      ?? '';
  $department = trim($_POST['department'] ?? '');

  // 2. Server-side validation  (JavaScript already checked client-side)
  if (empty($full_name))  $errors[] = 'Full name is required.';
  if (strlen($full_name) < 3) $errors[] = 'Full name must be at least 3 characters.';

  if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username))
    $errors[] = 'Username must be 3–20 characters (letters, numbers, _).';

  if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    $errors[] = 'Please enter a valid email address.';

  if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&_#]).{8,}$/', $password))
    $errors[] = 'Password must be 8+ chars with uppercase, lowercase, digit, and special character.';

  if ($password !== $confirm_pw)
    $errors[] = 'Passwords do not match.';

  // 3. Check for duplicate username / email
  if (empty($errors)) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
      // Find out which one clashes
      $chk = $conn->prepare("SELECT username, email FROM users WHERE username = ? OR email = ?");
      $chk->bind_param('ss', $username, $email);
      $chk->execute();
      $res = $chk->get_result()->fetch_assoc();
      if ($res['username'] === $username) $errors[] = 'That username is already taken.';
      if ($res['email']    === $email)    $errors[] = 'That email is already registered.';
      $chk->close();
    }
    $stmt->close();
  }

  // 4. Insert new user
  if (empty($errors)) {
    $hashed = password_hash($password, PASSWORD_BCRYPT);

    // Pick a random avatar color from a palette
    $colors = ['#6366f1','#7c3aed','#db2777','#0891b2','#059669','#d97706'];
    $color  = $colors[array_rand($colors)];

    $ins = $conn->prepare(
      "INSERT INTO users (full_name, username, email, password, department, avatar_color)
       VALUES (?, ?, ?, ?, ?, ?)"
    );
    $ins->bind_param('ssssss', $full_name, $username, $email, $hashed, $department, $color);

    if ($ins->execute()) {
      // Log user in immediately
      $_SESSION['user_id']   = $ins->insert_id;
      $_SESSION['username']  = $username;
      $_SESSION['full_name'] = $full_name;
      $ins->close();
      header('Location: home.php?welcome=1');
      exit;
    } else {
      $errors[] = 'Registration failed. Please try again.';
    }
    $ins->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign Up — SkillForge</title>
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>
<div class="bg-dots"></div>

<div class="auth-page">
  <div class="auth-card">

    <!-- Logo -->
    <div class="auth-logo">
      <h1>⚡ SkillForge</h1>
      <p>Track your skills. Build your future.</p>
    </div>

    <div class="card">
      <h2 style="margin-bottom:6px;">Create Account</h2>
      <p class="text-muted text-small" style="margin-bottom:24px;">
        Already have one? <a href="login.php">Sign in here</a>
      </p>

      <!-- PHP error messages -->
      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          ⚠️ <?= implode('<br>⚠️ ', array_map('htmlspecialchars', $errors)) ?>
        </div>
      <?php endif; ?>

      <!-- Signup Form -->
      <form id="signup-form" method="POST" action="signup.php" novalidate>

        <!-- Full Name -->
        <div class="form-group">
          <label for="full_name">Full Name</label>
          <div class="input-wrapper">
            <span class="input-icon">👤</span>
            <input
              type="text" id="full_name" name="full_name"
              class="form-control"
              placeholder="e.g. Ali Hassan"
              value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
              autocomplete="name"
            />
          </div>
          <span class="field-error" id="full_name-error"></span>
        </div>

        <!-- Username -->
        <div class="form-group">
          <label for="username">Username</label>
          <div class="input-wrapper">
            <span class="input-icon">@</span>
            <input
              type="text" id="username" name="username"
              class="form-control"
              placeholder="e.g. ali_dev"
              value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
              autocomplete="username"
            />
          </div>
          <span class="field-error" id="username-error"></span>
        </div>

        <!-- Email -->
        <div class="form-group">
          <label for="email">Email Address</label>
          <div class="input-wrapper">
            <span class="input-icon">✉️</span>
            <input
              type="email" id="email" name="email"
              class="form-control"
              placeholder="ali@example.com"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
              autocomplete="email"
            />
          </div>
          <span class="field-error" id="email-error"></span>
        </div>

        <!-- Department -->
        <div class="form-group">
          <label for="department">Department</label>
          <div class="input-wrapper">
            <span class="input-icon">🏫</span>
            <select id="department" name="department" class="form-control">
              <option value="">-- Select Department --</option>
              <option value="Computer Science"     <?= (($_POST['department'] ?? '') === 'Computer Science')     ? 'selected' : '' ?>>Computer Science</option>
              <option value="Software Engineering" <?= (($_POST['department'] ?? '') === 'Software Engineering') ? 'selected' : '' ?>>Software Engineering</option>
              <option value="Information Technology" <?= (($_POST['department'] ?? '') === 'Information Technology') ? 'selected' : '' ?>>Information Technology</option>
              <option value="Data Science"         <?= (($_POST['department'] ?? '') === 'Data Science')         ? 'selected' : '' ?>>Data Science</option>
              <option value="Cybersecurity"        <?= (($_POST['department'] ?? '') === 'Cybersecurity')        ? 'selected' : '' ?>>Cybersecurity</option>
              <option value="Other"                <?= (($_POST['department'] ?? '') === 'Other')                ? 'selected' : '' ?>>Other</option>
            </select>
          </div>
        </div>

        <!-- Password -->
        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrapper">
            <span class="input-icon">🔒</span>
            <input
              type="password" id="password" name="password"
              class="form-control"
              placeholder="Min 8 chars, A-Z, 0-9, special"
              autocomplete="new-password"
            />
            <button type="button" class="toggle-pw" data-target="password">👁</button>
          </div>
          <div id="pw-strength"></div>
          <span class="field-error" id="password-error"></span>
        </div>

        <!-- Confirm Password -->
        <div class="form-group">
          <label for="confirm_pw">Confirm Password</label>
          <div class="input-wrapper">
            <span class="input-icon">🔒</span>
            <input
              type="password" id="confirm_pw" name="confirm_pw"
              class="form-control"
              placeholder="Repeat your password"
              autocomplete="new-password"
            />
            <button type="button" class="toggle-pw" data-target="confirm_pw">👁</button>
          </div>
          <span class="field-error" id="confirm_pw-error"></span>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn btn-primary btn-full" id="submit-btn">
          Create My Account →
        </button>

      </form><!-- /signup-form -->
    </div><!-- /card -->

    <p class="text-center text-small text-muted mt-2">
      By signing up you agree to our <a href="#">Terms</a> &amp; <a href="#">Privacy Policy</a>
    </p>
  </div>
</div>

<script src="js/validate.js"></script>
<script>
/* ── Signup Form Validation (Client-side) ── */

const signupForm = document.getElementById('signup-form');

// Real-time validation on blur
document.getElementById('full_name').addEventListener('blur', function() {
  validateFullName(this.value);
});
document.getElementById('username').addEventListener('blur', function() {
  validateUsername(this.value);
});
document.getElementById('email').addEventListener('blur', function() {
  validateEmail(this.value);
});
document.getElementById('password').addEventListener('blur', function() {
  validatePassword(this.value);
});
document.getElementById('confirm_pw').addEventListener('blur', function() {
  validateConfirm(this.value);
});

// ── Individual field validators ─────────────

function validateFullName(val) {
  if (Validator.isEmpty(val))
    return Validator.showError('full_name', 'Full name is required.');
  if (!Validator.minLength(val, 3))
    return Validator.showError('full_name', 'Name must be at least 3 characters.');
  return Validator.showSuccess('full_name');
}

function validateUsername(val) {
  if (Validator.isEmpty(val))
    return Validator.showError('username', 'Username is required.');
  if (!Validator.isValidUsername(val))
    return Validator.showError('username', 'Only letters, numbers, underscore. 3–20 chars.');
  return Validator.showSuccess('username');
}

function validateEmail(val) {
  if (Validator.isEmpty(val))
    return Validator.showError('email', 'Email is required.');
  if (!Validator.isEmail(val))
    return Validator.showError('email', 'Enter a valid email address.');
  return Validator.showSuccess('email');
}

function validatePassword(val) {
  if (Validator.isEmpty(val))
    return Validator.showError('password', 'Password is required.');
  if (!Validator.isStrongPassword(val))
    return Validator.showError('password', 'Need 8+ chars, A-Z, a-z, 0-9 and a special char.');
  return Validator.showSuccess('password');
}

function validateConfirm(val) {
  const pw = document.getElementById('password').value;
  if (Validator.isEmpty(val))
    return Validator.showError('confirm_pw', 'Please confirm your password.');
  if (val !== pw)
    return Validator.showError('confirm_pw', 'Passwords do not match.');
  return Validator.showSuccess('confirm_pw');
}

// ── Full form validation on submit ──────────

signupForm.addEventListener('submit', function(e) {
  const fn  = validateFullName( document.getElementById('full_name').value );
  const un  = validateUsername( document.getElementById('username').value );
  const em  = validateEmail(    document.getElementById('email').value );
  const pw  = validatePassword( document.getElementById('password').value );
  const cpw = validateConfirm(  document.getElementById('confirm_pw').value );

  if (!(fn && un && em && pw && cpw)) {
    e.preventDefault(); // stop form from submitting if any field invalid
    return;
  }

  // Show loading state
  const btn = document.getElementById('submit-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Creating account…';
});
</script>
</body>
</html>

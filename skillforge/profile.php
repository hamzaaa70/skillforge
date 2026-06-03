<?php
// =============================================
//  profile.php  —  Page 4: View & Edit Profile
// =============================================
require_once 'includes/auth.php';
require_once 'includes/db.php';

require_login();

$user_id = $_SESSION['user_id'];
$success = '';
$errors  = [];

// ── Handle Profile Update ───────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

  // --- Update Profile Info ---
  if ($_POST['action'] === 'update_profile') {

    $full_name  = trim($_POST['full_name']  ?? '');
    $email      = trim($_POST['email']      ?? '');
    $department = trim($_POST['department'] ?? '');
    $bio        = trim($_POST['bio']        ?? '');

    // Validation
    if (strlen($full_name) < 3)
      $errors[] = 'Full name must be at least 3 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
      $errors[] = 'Enter a valid email address.';
    if (strlen($bio) > 500)
      $errors[] = 'Bio cannot exceed 500 characters.';

    // Check email uniqueness (exclude own account)
    if (empty($errors)) {
      $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
      $chk->bind_param('si', $email, $user_id);
      $chk->execute();
      $chk->store_result();
      if ($chk->num_rows > 0) $errors[] = 'That email is already used by another account.';
      $chk->close();
    }

    if (empty($errors)) {
      $upd = $conn->prepare(
        "UPDATE users SET full_name=?, email=?, department=?, bio=? WHERE id=?"
      );
      $upd->bind_param('ssssi', $full_name, $email, $department, $bio, $user_id);
      $upd->execute();
      $upd->close();

      // Update session
      $_SESSION['full_name'] = $full_name;
      $success = 'Profile updated successfully! ✅';
    }
  }

  // --- Change Password ---
  if ($_POST['action'] === 'change_password') {

    $current_pw = $_POST['current_pw'] ?? '';
    $new_pw     = $_POST['new_pw']     ?? '';
    $confirm_pw = $_POST['confirm_pw'] ?? '';

    // Fetch current hash
    $s = $conn->prepare("SELECT password FROM users WHERE id=?");
    $s->bind_param('i', $user_id);
    $s->execute();
    $row = $s->get_result()->fetch_assoc();
    $s->close();

    if (!password_verify($current_pw, $row['password']))
      $errors[] = 'Current password is incorrect.';

    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&_#]).{8,}$/', $new_pw))
      $errors[] = 'New password must have 8+ chars, uppercase, lowercase, digit and special character.';

    if ($new_pw !== $confirm_pw)
      $errors[] = 'New passwords do not match.';

    if (empty($errors)) {
      $hashed = password_hash($new_pw, PASSWORD_BCRYPT);
      $upd = $conn->prepare("UPDATE users SET password=? WHERE id=?");
      $upd->bind_param('si', $hashed, $user_id);
      $upd->execute();
      $upd->close();
      $success = 'Password changed successfully! 🔒';
    }
  }

  // --- Change Avatar Color ---
  if ($_POST['action'] === 'change_color') {
    $allowed = ['#6366f1','#7c3aed','#db2777','#0891b2','#059669','#d97706','#dc2626','#0d9488'];
    $color = $_POST['avatar_color'] ?? '#6366f1';
    if (in_array($color, $allowed)) {
      $upd = $conn->prepare("UPDATE users SET avatar_color=? WHERE id=?");
      $upd->bind_param('si', $color, $user_id);
      $upd->execute();
      $upd->close();
      $success = 'Avatar color updated! 🎨';
    }
  }
}

// ── Fetch fresh user data ───────────────────
$stmt = $conn->prepare(
  "SELECT full_name, username, email, department, bio, avatar_color, created_at
   FROM users WHERE id=?"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Skill summary
$s3 = $conn->prepare("SELECT level, COUNT(*) as cnt FROM skills WHERE user_id=? GROUP BY level");
$s3->bind_param('i', $user_id);
$s3->execute();
$lvl_res = $s3->get_result();
$skill_levels = [];
while ($r = $lvl_res->fetch_assoc()) {
  $skill_levels[$r['level']] = $r['cnt'];
}
$s3->close();

$total_skills = array_sum($skill_levels);
$initials = strtoupper(substr($user['full_name'], 0, 1));
if (strpos($user['full_name'], ' ') !== false) {
  $parts = explode(' ', $user['full_name']);
  $initials = strtoupper($parts[0][0] . end($parts)[0]);
}
$avatar_colors = ['#6366f1','#7c3aed','#db2777','#0891b2','#059669','#d97706','#dc2626','#0d9488'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profile — SkillForge</title>
  <link rel="stylesheet" href="css/style.css" />
  <style>
    .main-content { padding: 36px 0 60px; }

    /* Profile header card */
    .profile-hero {
      background: linear-gradient(135deg, rgba(99,102,241,0.12), rgba(139,92,246,0.08));
      border: 1px solid rgba(99,102,241,0.2);
      border-radius: var(--radius-lg);
      padding: 36px;
      display: flex;
      align-items: center;
      gap: 28px;
      margin-bottom: 28px;
      flex-wrap: wrap;
    }
    .profile-hero .hero-info h2 {
      font-size: 1.6rem;
      margin-bottom: 4px;
    }
    .profile-hero .hero-actions {
      margin-left: auto;
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    /* Color picker */
    .color-picker {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-top: 8px;
    }
    .color-swatch {
      width: 32px; height: 32px;
      border-radius: 50%;
      border: 3px solid transparent;
      cursor: pointer;
      transition: all 0.2s;
    }
    .color-swatch:hover, .color-swatch.selected {
      transform: scale(1.2);
      border-color: white;
      box-shadow: 0 0 0 2px var(--accent);
    }

    /* Tabs */
    .tab-list {
      display: flex;
      gap: 4px;
      border-bottom: 1px solid var(--border);
      margin-bottom: 28px;
      overflow-x: auto;
    }
    .tab-btn {
      padding: 10px 20px;
      background: none;
      border: none;
      border-bottom: 2px solid transparent;
      color: var(--text3);
      font-family: var(--font-body);
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      white-space: nowrap;
      transition: all var(--transition);
      margin-bottom: -1px;
    }
    .tab-btn.active {
      color: var(--accent);
      border-bottom-color: var(--accent);
    }
    .tab-btn:hover { color: var(--text); }

    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    /* Skill summary pills */
    .level-pills {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 12px;
    }
    .level-pill {
      padding: 8px 18px;
      border-radius: 30px;
      font-size: 0.85rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    /* Form 2-col grid */
    .form-grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0 20px;
    }
    @media (max-width: 600px) {
      .form-grid-2 { grid-template-columns: 1fr; }
      .profile-hero { flex-direction: column; text-align: center; }
      .profile-hero .hero-actions { margin-left: 0; }
    }

    /* Character counter */
    .char-count {
      font-size: 0.75rem;
      color: var(--text3);
      text-align: right;
      margin-top: 4px;
    }
  </style>
</head>
<body>

<!-- ── Navbar ─────────────────────────────── -->
<nav class="navbar">
  <div class="navbar-inner">
    <a href="home.php" class="navbar-brand">⚡ SkillForge</a>
    <ul class="navbar-nav" id="nav-menu">
      <li><a href="home.php">🏠 Dashboard</a></li>
      <li><a href="profile.php" class="active">👤 Profile</a></li>
      <li><a href="logout.php" class="btn btn-outline btn-sm" style="color:var(--red);border-color:rgba(239,68,68,0.3);">Sign Out</a></li>
    </ul>
    <button class="navbar-menu-btn" id="menu-btn">☰</button>
  </div>
</nav>

<main class="main-content">
  <div class="container">

    <!-- Success / Error flash -->
    <?php if ($success): ?>
      <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        ⚠️ <?= implode('<br>⚠️ ', array_map('htmlspecialchars', $errors)) ?>
      </div>
    <?php endif; ?>

    <!-- Profile Hero -->
    <div class="profile-hero">
      <div
        class="avatar avatar-xl"
        id="live-avatar"
        style="background:<?= htmlspecialchars($user['avatar_color']) ?>;">
        <?= htmlspecialchars($initials) ?>
      </div>
      <div class="hero-info">
        <h2><?= htmlspecialchars($user['full_name']) ?></h2>
        <p class="text-muted">@<?= htmlspecialchars($user['username']) ?></p>
        <?php if ($user['department']): ?>
          <span class="badge badge-purple" style="margin-top:6px;">
            🏫 <?= htmlspecialchars($user['department']) ?>
          </span>
        <?php endif; ?>
        <p class="text-small text-muted" style="margin-top:8px;">
          Member since <?= date('F Y', strtotime($user['created_at'])) ?>
        </p>
      </div>
      <div class="hero-actions">
        <span style="font-weight:700; font-size:1.5rem; color:var(--accent);">
          <?= $total_skills ?>
        </span>
        <span class="text-muted text-small" style="line-height:1.8;">skills tracked</span>
      </div>
    </div>

    <!-- Tabs -->
    <div class="tab-list" role="tablist">
      <button class="tab-btn active" onclick="switchTab('info', this)"  >📋 My Info</button>
      <button class="tab-btn"        onclick="switchTab('skills', this)" >📊 Skill Summary</button>
      <button class="tab-btn"        onclick="switchTab('password', this)">🔒 Password</button>
      <button class="tab-btn"        onclick="switchTab('avatar', this)" >🎨 Appearance</button>
    </div>

    <!-- ─── Tab 1: Edit Profile Info ──────── -->
    <div class="tab-panel active card" id="tab-info">
      <h3 style="margin-bottom:20px;">Edit Profile Information</h3>

      <form method="POST" action="profile.php" id="profile-form" novalidate>
        <input type="hidden" name="action" value="update_profile">

        <div class="form-grid-2">
          <div class="form-group">
            <label for="full_name">Full Name</label>
            <div class="input-wrapper">
              <span class="input-icon">👤</span>
              <input type="text" id="full_name" name="full_name"
                class="form-control"
                value="<?= htmlspecialchars($user['full_name']) ?>"
                placeholder="Your full name" required />
            </div>
            <span class="field-error" id="full_name-error"></span>
          </div>

          <div class="form-group">
            <label for="email">Email Address</label>
            <div class="input-wrapper">
              <span class="input-icon">✉️</span>
              <input type="email" id="email" name="email"
                class="form-control"
                value="<?= htmlspecialchars($user['email']) ?>"
                placeholder="email@example.com" required />
            </div>
            <span class="field-error" id="email-error"></span>
          </div>
        </div>

        <div class="form-group">
          <label for="username-display">Username (cannot change)</label>
          <div class="input-wrapper">
            <span class="input-icon">@</span>
            <input type="text" id="username-display"
              class="form-control"
              value="<?= htmlspecialchars($user['username']) ?>"
              disabled
              style="opacity:0.5; cursor:not-allowed;" />
          </div>
        </div>

        <div class="form-group">
          <label for="department">Department</label>
          <select id="department" name="department" class="form-control">
            <option value="">-- Select --</option>
            <?php
            $depts = ['Computer Science','Software Engineering','Information Technology','Data Science','Cybersecurity','Other'];
            foreach ($depts as $d): ?>
              <option value="<?= $d ?>" <?= $user['department'] === $d ? 'selected' : '' ?>>
                <?= $d ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="bio">Bio <span style="font-weight:400;text-transform:none;">(optional)</span></label>
          <textarea id="bio" name="bio"
            class="form-control"
            placeholder="Tell us about yourself…"
            maxlength="500"
            oninput="updateCharCount(this, 'bio-count')"
          ><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
          <div class="char-count">
            <span id="bio-count"><?= strlen($user['bio'] ?? '') ?></span>/500
          </div>
          <span class="field-error" id="bio-error"></span>
        </div>

        <button type="submit" class="btn btn-primary" id="save-profile-btn">
          💾 Save Changes
        </button>
      </form>
    </div><!-- /tab-info -->

    <!-- ─── Tab 2: Skill Summary ───────────── -->
    <div class="tab-panel card" id="tab-skills">
      <h3 style="margin-bottom:16px;">📊 Skill Level Breakdown</h3>

      <?php if ($total_skills === 0): ?>
        <p class="text-muted">No skills added yet. <a href="home.php">Go to Dashboard</a> to add some.</p>
      <?php else: ?>
        <?php
        $level_data = [
          'Beginner'     => ['emoji'=>'🌱', 'color'=>'#f59e0b', 'class'=>'badge-yellow'],
          'Intermediate' => ['emoji'=>'⚡', 'color'=>'#6366f1', 'class'=>'badge-purple'],
          'Advanced'     => ['emoji'=>'🚀', 'color'=>'#10b981', 'class'=>'badge-green'],
          'Expert'       => ['emoji'=>'🏆', 'color'=>'#ef4444', 'class'=>'badge-red'],
        ];
        foreach ($level_data as $lvl => $meta):
          $cnt = $skill_levels[$lvl] ?? 0;
          if ($cnt === 0) continue;
          $pct = round(($cnt / $total_skills) * 100);
        ?>
          <div style="margin-bottom:18px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
              <span style="font-weight:600;">
                <?= $meta['emoji'] ?> <?= $lvl ?>
              </span>
              <span class="text-muted text-small"><?= $cnt ?> skill<?= $cnt>1?'s':'' ?> · <?= $pct ?>%</span>
            </div>
            <div class="progress-bar-wrap">
              <div class="progress-bar-fill"
                style="width:<?= $pct ?>%; background:linear-gradient(90deg, <?= $meta['color'] ?>, <?= $meta['color'] ?>aa);">
              </div>
            </div>
          </div>
        <?php endforeach; ?>

        <div class="level-pills">
          <div class="level-pill" style="background:rgba(16,185,129,0.1); color:#34d399;">
            ✅ Total: <?= $total_skills ?> skills
          </div>
        </div>
      <?php endif; ?>
    </div><!-- /tab-skills -->

    <!-- ─── Tab 3: Change Password ─────────── -->
    <div class="tab-panel card" id="tab-password">
      <h3 style="margin-bottom:20px;">🔒 Change Password</h3>

      <form method="POST" action="profile.php" id="pw-form" novalidate>
        <input type="hidden" name="action" value="change_password">

        <div class="form-group">
          <label for="current_pw">Current Password</label>
          <div class="input-wrapper">
            <span class="input-icon">🔑</span>
            <input type="password" id="current_pw" name="current_pw"
              class="form-control" placeholder="Your current password" />
            <button type="button" class="toggle-pw" data-target="current_pw">👁</button>
          </div>
          <span class="field-error" id="current_pw-error"></span>
        </div>

        <div class="form-group">
          <label for="new_pw">New Password</label>
          <div class="input-wrapper">
            <span class="input-icon">🔒</span>
            <input type="password" id="new_pw" name="new_pw"
              class="form-control" placeholder="Min 8 chars, A-Z, 0-9, special" />
            <button type="button" class="toggle-pw" data-target="new_pw">👁</button>
          </div>
          <div id="pw-strength"></div>
          <span class="field-error" id="new_pw-error"></span>
        </div>

        <div class="form-group">
          <label for="confirm_pw">Confirm New Password</label>
          <div class="input-wrapper">
            <span class="input-icon">🔒</span>
            <input type="password" id="confirm_pw" name="confirm_pw"
              class="form-control" placeholder="Repeat new password" />
            <button type="button" class="toggle-pw" data-target="confirm_pw">👁</button>
          </div>
          <span class="field-error" id="confirm_pw-error"></span>
        </div>

        <button type="submit" class="btn btn-primary">🔐 Update Password</button>
      </form>
    </div><!-- /tab-password -->

    <!-- ─── Tab 4: Avatar Color ────────────── -->
    <div class="tab-panel card" id="tab-avatar">
      <h3 style="margin-bottom:8px;">🎨 Avatar Color</h3>
      <p class="text-muted text-small" style="margin-bottom:20px;">
        Pick a color for your avatar badge.
      </p>

      <form method="POST" action="profile.php" id="color-form">
        <input type="hidden" name="action" value="change_color">
        <input type="hidden" name="avatar_color" id="color-input"
          value="<?= htmlspecialchars($user['avatar_color']) ?>">

        <div class="color-picker">
          <?php foreach ($avatar_colors as $c): ?>
            <div
              class="color-swatch <?= $user['avatar_color'] === $c ? 'selected' : '' ?>"
              style="background:<?= $c ?>;"
              data-color="<?= $c ?>"
              onclick="selectColor('<?= $c ?>', this)"
              title="<?= $c ?>"
            ></div>
          <?php endforeach; ?>
        </div>

        <div style="margin-top:20px; display:flex; align-items:center; gap:16px;">
          <div class="avatar avatar-lg" id="color-preview"
            style="background:<?= htmlspecialchars($user['avatar_color']) ?>;">
            <?= htmlspecialchars($initials) ?>
          </div>
          <button type="submit" class="btn btn-primary btn-sm">Apply Color</button>
        </div>
      </form>
    </div><!-- /tab-avatar -->

  </div>
</main>

<script src="js/validate.js"></script>
<script>
/* ── Tab switching ── */
function switchTab(name, btn) {
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + name).classList.add('active');
  btn.classList.add('active');
}

/* ── Character counter for bio ── */
function updateCharCount(el, counterId) {
  document.getElementById(counterId).textContent = el.value.length;
}

/* ── Color picker ── */
function selectColor(color, el) {
  document.querySelectorAll('.color-swatch').forEach(s => s.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('color-input').value = color;
  document.getElementById('color-preview').style.background = color;
  document.getElementById('live-avatar').style.background  = color;
}

/* ── Profile form validation ── */
document.getElementById('profile-form').addEventListener('submit', function(e) {
  let ok = true;

  const name = document.getElementById('full_name').value.trim();
  if (name.length < 3) {
    Validator.showError('full_name', 'Name must be at least 3 characters.');
    ok = false;
  } else {
    Validator.showSuccess('full_name');
  }

  const email = document.getElementById('email').value.trim();
  if (!Validator.isEmail(email)) {
    Validator.showError('email', 'Enter a valid email address.');
    ok = false;
  } else {
    Validator.showSuccess('email');
  }

  const bio = document.getElementById('bio').value;
  if (bio.length > 500) {
    Validator.showError('bio', 'Bio cannot exceed 500 characters.');
    ok = false;
  }

  if (!ok) {
    e.preventDefault();
    // Switch to info tab if validation fails
    switchTab('info', document.querySelector('.tab-btn'));
    return;
  }

  const btn = document.getElementById('save-profile-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Saving…';
});

/* ── Password form validation ── */
document.getElementById('pw-form').addEventListener('submit', function(e) {
  let ok = true;

  const cur = document.getElementById('current_pw').value;
  if (!cur) { Validator.showError('current_pw', 'Enter your current password.'); ok = false; }
  else        Validator.showSuccess('current_pw');

  const nw = document.getElementById('new_pw').value;
  if (!Validator.isStrongPassword(nw)) {
    Validator.showError('new_pw', 'Need 8+ chars, A-Z, a-z, 0-9 and a special char.');
    ok = false;
  } else Validator.showSuccess('new_pw');

  const cf = document.getElementById('confirm_pw').value;
  if (cf !== nw) { Validator.showError('confirm_pw', 'Passwords do not match.'); ok = false; }
  else            Validator.showSuccess('confirm_pw');

  if (!ok) e.preventDefault();
});

/* ── Live password strength on profile page ── */
const newPwEl = document.getElementById('new_pw');
if (newPwEl) {
  newPwEl.addEventListener('input', function() {
    Validator.renderStrengthMeter(this.value, 'pw-strength');
  });
}
</script>
</body>
</html>

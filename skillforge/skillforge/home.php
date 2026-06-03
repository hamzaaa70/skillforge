<?php
// =============================================
//  home.php  —  Page 3: Dashboard / Home
// =============================================
require_once 'includes/auth.php';
require_once 'includes/db.php';

require_login(); // redirect to login if not logged in

$user_id   = $_SESSION['user_id'];
$welcome   = isset($_GET['welcome']);

// ── Fetch logged-in user's data ─────────────
$stmt = $conn->prepare(
  "SELECT full_name, username, email, department, bio, avatar_color, created_at
   FROM users WHERE id = ?"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ── Fetch user's skills ─────────────────────
$s2 = $conn->prepare("SELECT * FROM skills WHERE user_id = ? ORDER BY id DESC");
$s2->bind_param('i', $user_id);
$s2->execute();
$skills_result = $s2->get_result();
$skills = [];
while ($row = $skills_result->fetch_assoc()) {
  $skills[] = $row;
}
$s2->close();

// ── Handle Add Skill (AJAX-style POST) ──────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

  // ADD SKILL
  if ($_POST['action'] === 'add_skill') {
    $skill_name = trim($_POST['skill_name'] ?? '');
    $level      = $_POST['level']      ?? 'Beginner';
    $category   = trim($_POST['category'] ?? 'General');
    $valid_levels = ['Beginner','Intermediate','Advanced','Expert'];

    if (!empty($skill_name) && in_array($level, $valid_levels)) {
      $ins = $conn->prepare(
        "INSERT INTO skills (user_id, skill_name, level, category) VALUES (?,?,?,?)"
      );
      $ins->bind_param('isss', $user_id, $skill_name, $level, $category);
      $ins->execute();
      $ins->close();
    }
    header('Location: home.php');
    exit;
  }

  // DELETE SKILL
  if ($_POST['action'] === 'delete_skill') {
    $skill_id = intval($_POST['skill_id'] ?? 0);
    $del = $conn->prepare("DELETE FROM skills WHERE id = ? AND user_id = ?");
    $del->bind_param('ii', $skill_id, $user_id);
    $del->execute();
    $del->close();
    header('Location: home.php');
    exit;
  }
}

// ── Helpers ─────────────────────────────────
function levelToPercent($level) {
  return match($level) {
    'Beginner'     => 25,
    'Intermediate' => 55,
    'Advanced'     => 80,
    'Expert'       => 100,
    default        => 25
  };
}
function levelToBadge($level) {
  return match($level) {
    'Beginner'     => 'badge-yellow',
    'Intermediate' => 'badge-purple',
    'Advanced'     => 'badge-green',
    'Expert'       => 'badge-red',
    default        => 'badge-yellow'
  };
}

$initials = strtoupper(substr($user['full_name'], 0, 1));
if (strpos($user['full_name'], ' ') !== false) {
  $parts = explode(' ', $user['full_name']);
  $initials = strtoupper($parts[0][0] . end($parts)[0]);
}
$skill_count    = count($skills);
$expert_count   = count(array_filter($skills, fn($s) => $s['level'] === 'Expert'));
$member_since   = date('M Y', strtotime($user['created_at']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard — SkillForge</title>
  <link rel="stylesheet" href="css/style.css" />
  <style>
    /* ── Home-specific styles ── */
    .main-content { padding: 36px 0 60px; }

    /* Stats row */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
      margin-bottom: 32px;
    }
    .stat-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 22px;
      text-align: center;
    }
    .stat-card .stat-num {
      font-family: var(--font-head);
      font-size: 2.4rem;
      font-weight: 800;
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .stat-card .stat-label {
      font-size: 0.8rem;
      color: var(--text3);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-top: 4px;
    }

    /* Skills section */
    .section-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 18px;
    }

    .skill-card {
      background: var(--bg3);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 16px 18px;
      margin-bottom: 12px;
      transition: all var(--transition);
    }
    .skill-card:hover {
      border-color: var(--accent);
      transform: translateX(3px);
    }
    .skill-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 8px;
    }
    .skill-name { font-weight: 600; font-size: 0.95rem; }
    .skill-meta {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* Add skill modal */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.7);
      backdrop-filter: blur(6px);
      z-index: 200;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      opacity: 0;
      pointer-events: none;
      transition: opacity var(--transition);
    }
    .modal-overlay.open {
      opacity: 1;
      pointer-events: all;
    }
    .modal {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 32px;
      width: 100%;
      max-width: 440px;
      transform: translateY(20px);
      transition: transform var(--transition);
    }
    .modal-overlay.open .modal { transform: translateY(0); }
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
    }
    .modal-close {
      background: none; border: none;
      color: var(--text2); font-size: 1.3rem;
      cursor: pointer; line-height: 1;
    }
    .modal-close:hover { color: var(--text); }

    /* Welcome banner */
    .welcome-banner {
      background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(139,92,246,0.1));
      border: 1px solid rgba(99,102,241,0.3);
      border-radius: var(--radius-lg);
      padding: 24px;
      margin-bottom: 28px;
      display: flex;
      align-items: center;
      gap: 18px;
    }

    /* Empty state */
    .empty-state {
      text-align: center;
      padding: 48px 20px;
      color: var(--text3);
    }
    .empty-state .empty-icon { font-size: 3rem; margin-bottom: 12px; }

    /* Responsive */
    @media (max-width: 640px) {
      .stats-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 400px) {
      .stats-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<!-- ── Navbar ─────────────────────────────── -->
<nav class="navbar">
  <div class="navbar-inner">
    <a href="home.php" class="navbar-brand">⚡ SkillForge</a>

    <ul class="navbar-nav" id="nav-menu">
      <li><a href="home.php" class="active">🏠 Dashboard</a></li>
      <li><a href="profile.php">👤 Profile</a></li>
      <li><a href="logout.php" class="btn btn-outline btn-sm" style="color:var(--red);border-color:rgba(239,68,68,0.3);">Sign Out</a></li>
    </ul>
    <button class="navbar-menu-btn" id="menu-btn">☰</button>
  </div>
</nav>

<!-- ── Main Content ───────────────────────── -->
<main class="main-content">
  <div class="container">

    <!-- Welcome Banner (only on first login) -->
    <?php if ($welcome): ?>
    <div class="welcome-banner" id="welcome-banner">
      <div
        class="avatar avatar-lg"
        style="background:<?= htmlspecialchars($user['avatar_color']) ?>;">
        <?= htmlspecialchars($initials) ?>
      </div>
      <div>
        <h2 style="font-size:1.3rem;">Welcome, <?= htmlspecialchars($user['full_name']) ?>! 🎉</h2>
        <p class="text-muted text-small">Your SkillForge journey starts now. Add your first skill below!</p>
      </div>
      <button onclick="document.getElementById('welcome-banner').style.display='none'"
        style="margin-left:auto; background:none; border:none; color:var(--text3); font-size:1.2rem; cursor:pointer;">✕</button>
    </div>
    <?php endif; ?>

    <!-- Page heading -->
    <div class="flex items-center gap-2" style="margin-bottom:28px;">
      <div
        class="avatar"
        style="background:<?= htmlspecialchars($user['avatar_color']) ?>;">
        <?= htmlspecialchars($initials) ?>
      </div>
      <div>
        <h1 style="font-size:1.6rem; margin:0;">
          <?= htmlspecialchars($user['full_name']) ?>
        </h1>
        <p class="text-muted text-small">
          @<?= htmlspecialchars($user['username']) ?>
          <?php if ($user['department']): ?>
            · <?= htmlspecialchars($user['department']) ?>
          <?php endif; ?>
        </p>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-num"><?= $skill_count ?></div>
        <div class="stat-label">Total Skills</div>
      </div>
      <div class="stat-card">
        <div class="stat-num"><?= $expert_count ?></div>
        <div class="stat-label">Expert Level</div>
      </div>
      <div class="stat-card">
        <div class="stat-num"><?= $member_since ?></div>
        <div class="stat-label">Member Since</div>
      </div>
    </div>

    <!-- Skills Section -->
    <div class="card">
      <div class="section-header">
        <h2 style="font-size:1.3rem;">🛠️ My Skills</h2>
        <button class="btn btn-primary btn-sm" onclick="openModal()">
          + Add Skill
        </button>
      </div>

      <?php if (empty($skills)): ?>
        <div class="empty-state">
          <div class="empty-icon">🌱</div>
          <h3>No skills yet!</h3>
          <p class="text-small mt-1">Click <strong>+ Add Skill</strong> to start building your profile.</p>
        </div>
      <?php else: ?>
        <?php foreach ($skills as $skill): ?>
        <div class="skill-card">
          <div class="skill-row">
            <div>
              <span class="skill-name"><?= htmlspecialchars($skill['skill_name']) ?></span>
              <span class="text-muted text-xs" style="margin-left:8px;">
                <?= htmlspecialchars($skill['category']) ?>
              </span>
            </div>
            <div class="skill-meta">
              <span class="badge <?= levelToBadge($skill['level']) ?>">
                <?= htmlspecialchars($skill['level']) ?>
              </span>
              <!-- Delete form -->
              <form method="POST" action="home.php" style="margin:0;"
                onsubmit="return confirm('Remove this skill?')">
                <input type="hidden" name="action"   value="delete_skill">
                <input type="hidden" name="skill_id" value="<?= $skill['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm" style="padding:4px 10px;">✕</button>
              </form>
            </div>
          </div>
          <!-- Progress bar -->
          <div class="progress-bar-wrap">
            <div class="progress-bar-fill"
              style="width:<?= levelToPercent($skill['level']) ?>%;"></div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Profile quick link -->
    <div class="card" style="margin-top:20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
      <div>
        <h3>📝 Keep your profile updated</h3>
        <p class="text-muted text-small mt-1">Add your bio, department, and personal details.</p>
      </div>
      <a href="profile.php" class="btn btn-outline">Edit Profile</a>
    </div>

  </div>
</main>

<!-- ── Add Skill Modal ─────────────────────── -->
<div class="modal-overlay" id="skill-modal" onclick="closeModalOutside(event)">
  <div class="modal">
    <div class="modal-header">
      <h3>➕ Add a New Skill</h3>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>

    <form method="POST" action="home.php" id="add-skill-form">
      <input type="hidden" name="action" value="add_skill">

      <div class="form-group">
        <label for="skill_name">Skill Name</label>
        <input type="text" id="skill_name" name="skill_name"
          class="form-control" placeholder="e.g. JavaScript, PHP, MySQL…"
          required maxlength="100" />
        <span class="field-error" id="skill_name-error"></span>
      </div>

      <div class="form-group">
        <label for="category">Category</label>
        <select id="category" name="category" class="form-control">
          <option value="Programming">Programming</option>
          <option value="Database">Database</option>
          <option value="Design">Design</option>
          <option value="Networking">Networking</option>
          <option value="Tools">Tools & DevOps</option>
          <option value="Soft Skills">Soft Skills</option>
          <option value="General">General</option>
        </select>
      </div>

      <div class="form-group">
        <label for="level">Proficiency Level</label>
        <select id="level" name="level" class="form-control">
          <option value="Beginner">🌱 Beginner</option>
          <option value="Intermediate">⚡ Intermediate</option>
          <option value="Advanced">🚀 Advanced</option>
          <option value="Expert">🏆 Expert</option>
        </select>
      </div>

      <div style="display:flex; gap:12px; margin-top:24px;">
        <button type="button" class="btn btn-outline w-full" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary w-full">Add Skill</button>
      </div>
    </form>
  </div>
</div>

<script src="js/validate.js"></script>
<script>
function openModal() {
  document.getElementById('skill-modal').classList.add('open');
  document.getElementById('skill_name').focus();
}
function closeModal() {
  document.getElementById('skill-modal').classList.remove('open');
}
function closeModalOutside(e) {
  if (e.target === document.getElementById('skill-modal')) closeModal();
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// Validate skill form before submit
document.getElementById('add-skill-form').addEventListener('submit', function(e) {
  const name = document.getElementById('skill_name').value.trim();
  if (!name) {
    e.preventDefault();
    Validator.showError('skill_name', 'Skill name cannot be empty.');
  }
});
</script>
</body>
</html>

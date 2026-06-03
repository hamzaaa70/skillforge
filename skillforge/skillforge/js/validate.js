/* =============================================
   js/validate.js  —  Form Validation Library
   Reusable validation functions for all forms
   ============================================= */

const Validator = {

  // ── Core validators ──────────────────────────

  isEmpty: (val) => val.trim() === '',

  isEmail: (val) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val.trim()),

  isStrongPassword: (val) => {
    // At least 8 chars, 1 uppercase, 1 lowercase, 1 digit, 1 special char
    return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&_#])[A-Za-z\d@$!%*?&_#]{8,}$/.test(val);
  },

  isValidUsername: (val) => /^[a-zA-Z0-9_]{3,20}$/.test(val.trim()),

  minLength: (val, min) => val.trim().length >= min,

  maxLength: (val, max) => val.trim().length <= max,

  // ── UI helpers ───────────────────────────────

  // Mark a field as having an error
  showError: (fieldId, message) => {
    const field   = document.getElementById(fieldId);
    const errEl   = document.getElementById(fieldId + '-error');
    if (!field) return;
    field.classList.remove('success');
    field.classList.add('error');
    if (errEl) {
      errEl.textContent = message;
      errEl.classList.add('show');
    }
    return false;
  },

  // Mark a field as valid
  showSuccess: (fieldId) => {
    const field = document.getElementById(fieldId);
    const errEl = document.getElementById(fieldId + '-error');
    if (!field) return;
    field.classList.remove('error');
    field.classList.add('success');
    if (errEl) errEl.classList.remove('show');
    return true;
  },

  // Clear a field's state
  clearState: (fieldId) => {
    const field = document.getElementById(fieldId);
    const errEl = document.getElementById(fieldId + '-error');
    if (field) { field.classList.remove('error', 'success'); }
    if (errEl) errEl.classList.remove('show');
  },

  // ── Password strength meter ──────────────────

  getPasswordStrength: (password) => {
    let score = 0;
    const checks = {
      length:    password.length >= 8,
      lowercase: /[a-z]/.test(password),
      uppercase: /[A-Z]/.test(password),
      digit:     /\d/.test(password),
      special:   /[@$!%*?&_#]/.test(password),
    };
    Object.values(checks).forEach(v => { if (v) score++; });
    return { score, checks };
  },

  renderStrengthMeter: (password, containerId) => {
    const wrap = document.getElementById(containerId);
    if (!wrap) return;
    const { score } = Validator.getPasswordStrength(password);
    const labels = ['', 'Very Weak', 'Weak', 'Fair', 'Strong', 'Very Strong'];
    const colors = ['', '#ef4444', '#f97316', '#f59e0b', '#10b981', '#6366f1'];
    wrap.innerHTML = `
      <div style="display:flex; gap:5px; margin-top:8px;">
        ${[1,2,3,4,5].map(i =>
          `<div style="
            flex:1; height:4px; border-radius:4px;
            background:${i <= score ? colors[score] : 'var(--border)'};
            transition: background 0.3s;
          "></div>`
        ).join('')}
      </div>
      <p style="font-size:0.75rem; color:${colors[score]}; margin-top:4px;">
        ${password.length > 0 ? labels[score] : ''}
      </p>`;
  }
};

/* ── Real-time validation attachments ──────── */

// Attach live validation to signup form when DOM is ready
document.addEventListener('DOMContentLoaded', () => {

  // Live validation for individual fields on 'input' event
  const liveValidate = (id, fn) => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', () => fn(el.value));
  };

  // Password strength meter
  liveValidate('password', (val) => {
    Validator.renderStrengthMeter(val, 'pw-strength');
  });

  // Toggle password visibility
  document.querySelectorAll('.toggle-pw').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.dataset.target;
      const input    = document.getElementById(targetId);
      if (!input) return;
      const isText   = input.type === 'text';
      input.type     = isText ? 'password' : 'text';
      btn.textContent= isText ? '👁' : '🙈';
    });
  });

  // Mobile nav toggle
  const menuBtn = document.getElementById('menu-btn');
  const navMenu = document.getElementById('nav-menu');
  if (menuBtn && navMenu) {
    menuBtn.addEventListener('click', () => {
      navMenu.classList.toggle('open');
    });
  }
});

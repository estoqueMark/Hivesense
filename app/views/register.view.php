<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — HiveSense</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= ROOT ?>/public/assets/css/register.css">
   
</head>
<body>

<div class="brand-panel">
    <div class="brand-content">
        <div class="brand-icon"><img src="<?= ROOT ?>/public/assets/img/cordillera-apiculture-logo.png" 
        alt="Cordillera Regional Apiculture Center"></div>
      
        <h1 class="brand-title">Join HiveSense</h1>
        <div class="brand-divider"></div>
        <p class="brand-subtitle">Create your account to start monitoring hives for the Cordillera Regional Apiculture Center.</p>

        <div class="brand-steps">
            <div class="brand-step">
                <div class="step-num">1</div>
                <div class="step-text">Fill in your details below</div>
            </div>
            <div class="brand-step">
                <div class="step-num">2</div>
                <div class="step-text">Your account starts as a Viewer</div>
            </div>
            <div class="brand-step">
                <div class="step-num">3</div>
                <div class="step-text">An admin can promote you to full access</div>
            </div>
        </div>
    </div>
</div>

<div class="form-panel">
    <div class="form-container">

        <a href="<?= ROOT ?>/" class="back-home">
            <i class="fas fa-arrow-left"></i> Back to CRAC Home
        </a>

        <div class="form-eyebrow">Get started</div>
        <h2 class="form-title">Create your account</h2>
        <p class="form-desc">All fields marked <span style="color:var(--green);font-weight:700;">*</span> are required.</p>

        <?php if (!empty($error)): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= ROOT ?>/register/submit" autocomplete="on" id="regForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <div class="field">
                <label for="full_name">Full Name</label>
                <div class="input-wrap">
                    <input type="text" id="full_name" name="full_name"
                           placeholder="e.g. Juan Dela Cruz"
                           value="<?= htmlspecialchars($old['full_name'] ?? '') ?>"
                           autocomplete="name">
                    <i class="fas fa-id-card fi"></i>
                </div>
            </div>

            <div class="field-row">
                <div class="field">
                    <label for="username">Username <span class="req">*</span></label>
                    <div class="input-wrap">
                        <input type="text" id="username" name="username"
                               placeholder="e.g. jdoe"
                               value="<?= htmlspecialchars($old['username'] ?? '') ?>"
                               autocomplete="username" required
                               pattern="[a-zA-Z0-9_]{3,30}">
                        <i class="fas fa-at fi"></i>
                    </div>
                    <div class="field-hint">3–30 chars, letters/numbers/_</div>
                </div>

                <div class="field">
                    <label for="email">Email <span class="req">*</span></label>
                    <div class="input-wrap">
                        <input type="email" id="email" name="email"
                               placeholder="you@email.com"
                               value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                               autocomplete="email" required>
                        <i class="fas fa-envelope fi"></i>
                    </div>
                </div>
            </div>

            <div class="field">
                <label for="password">Password <span class="req">*</span></label>
                <div class="input-wrap">
                    <input type="password" id="password" name="password"
                           placeholder="Min. 8 characters"
                           autocomplete="new-password" required
                           oninput="checkStrength(this.value)">
                    <i class="fas fa-lock fi"></i>
                    <button type="button" class="toggle-pw" onclick="togglePw('password','pwIcon1')">
                        <i class="fas fa-eye" id="pwIcon1"></i>
                    </button>
                </div>
                <div class="pw-strength">
                    <div class="pw-strength-bar"><div class="pw-strength-fill" id="strengthFill"></div></div>
                    <div class="pw-strength-label" id="strengthLabel">Enter a password</div>
                </div>
            </div>

            <div class="field">
                <label for="confirm_password">Confirm Password <span class="req">*</span></label>
                <div class="input-wrap">
                    <input type="password" id="confirm_password" name="confirm_password"
                           placeholder="Repeat your password"
                           autocomplete="new-password" required
                           oninput="checkMatch()">
                    <i class="fas fa-lock fi"></i>
                    <button type="button" class="toggle-pw" onclick="togglePw('confirm_password','pwIcon2')">
                        <i class="fas fa-eye" id="pwIcon2"></i>
                    </button>
                </div>
                <div class="field-hint" id="matchHint"></div>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                <i class="fas fa-user-plus"></i> <span id="submitBtnText">Create Account</span>
            </button>

            <p class="terms-note">
                By registering, you agree that your account is for CRAC / HiveSense use only.<br>
                New accounts are created as <strong>Viewer</strong> — an admin can grant further access.
            </p>
        </form>

        <div class="form-footer" style="margin-top:20px;">
            Already have an account? <a href="<?= ROOT ?>/login">Sign in</a>
        </div>

    </div>
</div>

<script>
function togglePw(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
}

function checkStrength(val) {
    const fill  = document.getElementById('strengthFill');
    const label = document.getElementById('strengthLabel');
    let score = 0;
    if (val.length >= 8)                        score++;
    if (/[A-Z]/.test(val))                      score++;
    if (/[0-9]/.test(val))                      score++;
    if (/[^A-Za-z0-9]/.test(val))               score++;

    const levels = [
        { w: '0%',   color: '#ccc',    text: 'Enter a password' },
        { w: '25%',  color: '#D94F4F', text: 'Weak' },
        { w: '50%',  color: '#F5A623', text: 'Fair' },
        { w: '75%',  color: '#3D9E4E', text: 'Good' },
        { w: '100%', color: '#2D7A3A', text: 'Strong' },
    ];
    const lvl = val.length === 0 ? levels[0] : levels[Math.min(score, 4)];
    fill.style.width      = lvl.w;
    fill.style.background = lvl.color;
    label.textContent     = lvl.text;
    label.style.color     = lvl.color;
}

function checkMatch() {
    const pw      = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    const hint    = document.getElementById('matchHint');
    if (!confirm) { hint.textContent = ''; return; }
    if (pw === confirm) {
        hint.textContent = '✓ Passwords match';
        hint.style.color = '#27AE60';
    } else {
        hint.textContent = '✗ Passwords do not match';
        hint.style.color = '#D94F4F';
    }
}

document.getElementById('regForm').addEventListener('submit', function() {
    const btn  = document.getElementById('submitBtn');
    const text = document.getElementById('submitBtnText');
    // Let the browser start the actual form submission first,
    // then update the UI — disabling too early can block submission.
    setTimeout(function() {
        btn.disabled = true;
        text.textContent = 'Creating your account...';
    }, 0);
});
</script>
</body>
</html>

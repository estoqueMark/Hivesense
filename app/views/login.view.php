<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — HiveSense</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= ROOT ?>/public/assets/css/login.css">

</head>
<body>

<div class="brand-panel">
    <div class="brand-content">
        <div class="brand-icon"><img src="<?= ROOT ?>/public/assets/img/cordillera-apiculture-logo.png" alt="Cordillera Regional Apiculture Center"></div>
        <h1 class="brand-title">HiveSense</h1>
        <div class="brand-divider"></div>
        <p class="brand-subtitle">Smart hive monitoring for the Cordillera Regional Apiculture Center.</p>
        <div class="brand-badges">
            <span class="badge"><i class="fas fa-thermometer-half"></i> Temperature</span>
            <span class="badge"><i class="fas fa-tint"></i> Humidity</span>
            <span class="badge"><i class="fas fa-calendar-alt"></i> Inspections</span>
        </div>
    </div>
</div>

<div class="form-panel">
    <div class="form-container">

        <a href="<?= ROOT ?>/" class="back-home">
            <i class="fas fa-arrow-left"></i> Back to CRAC Home
        </a>

        <div class="form-eyebrow">Welcome back</div>
        <h2 class="form-title">Sign in to HiveSense</h2>
        <p class="form-desc">Enter your credentials to access the dashboard.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= ROOT ?>/login/submit" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <div class="field">
                <label for="login">Username or Email</label>
                <div class="input-wrap">
                    <input type="text" id="login" name="login"
                           placeholder="your username or email"
                           value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
                           autocomplete="username" required>
                    <i class="fas fa-user fi"></i>
                </div>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <input type="password" id="password" name="password"
                           placeholder="••••••••"
                           autocomplete="current-password" required>
                    <i class="fas fa-lock fi"></i>
                    <button type="button" class="toggle-pw" onclick="togglePw('password','pwIcon')">
                        <i class="fas fa-eye" id="pwIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>

        <div class="divider"><span>or</span></div>

        <a href="<?= ROOT ?>/register" class="btn-register">
            <i class="fas fa-user-plus"></i> Create an Account
        </a>

        <p style="text-align:center;font-size:0.76rem;color:var(--text-dim);margin-top:18px;">
            By signing in, you agree to HiveSense's
            <a href="<?= ROOT ?>/terms" style="color:var(--green);font-weight:700;text-decoration:none;">Terms &amp; Conditions</a>.
        </p>

    </div>
</div>

<script>
function togglePw(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
</body>
</html>

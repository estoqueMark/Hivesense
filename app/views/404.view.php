<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | HiveSense</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
      <!-- link to css -->
    <link rel="stylesheet" href="<?= ROOT ?>/public/assets/css/404.css">
   
</head>
<body>
    <div class="error-container">
        <span class="error-icon">🐝</span>
        <div class="error-code">404</div>
        <h1 class="error-title">Page Not Found</h1>
        <p class="error-message">The page you're looking for doesn't exist or has been moved. The bees couldn't find it either.</p>
        <a href="<?= ROOT ?>/dashboard" class="home-link">
            ← Back to Dashboard
        </a>
    </div>

    <!-- Animated bees -->
    <div class="bee" style="top: 15%; left: 8%;  --tx: 900px; --ty: 600px; animation-delay: 0s;  animation-duration: 10s;">🐝</div>
    <div class="bee" style="top: 75%; left: 90%; --tx:-900px; --ty:-400px; animation-delay: 3s;  animation-duration: 12s;">🐝</div>
    <div class="bee" style="top: 50%; left: 3%;  --tx: 1000px;--ty:-200px; animation-delay: 6s;  animation-duration: 9s; ">🐝</div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions — HiveSense</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= ROOT ?>/public/assets/css/terms.css">
</head>
<body>

<div class="terms-page">
    <div class="terms-header">
        <a href="<?= ROOT ?>/login" class="back-home">
            <i class="fas fa-arrow-left"></i> Back to Sign In
        </a>
        <div class="terms-eyebrow">HiveSense</div>
        <h1 class="terms-title">Terms &amp; Conditions</h1>
        <p class="terms-updated">Last updated: <?= date('F j, Y') ?></p>
    </div>

    <div class="terms-body">

        <section>
            <h2>1. About HiveSense</h2>
            <p>HiveSense is a beehive monitoring and management system developed for the Cordillera Regional Apiculture Center (CRAC), a partnership between Don Mariano Marcos Memorial State University (DMMMSU) and Benguet State University (BSU). It is provided as part of an academic capstone project and is intended for use by CRAC staff, apiarists, and authorized viewers.</p>
        </section>

        <section>
            <h2>2. Accounts</h2>
            <p>You are responsible for keeping your login credentials confidential and for all activity that occurs under your account. New self-registered accounts are created with Viewer access by default; elevated roles (Apiarist, Admin) are granted only by an existing administrator. Notify an administrator immediately if you believe your account has been accessed without authorization.</p>
        </section>

        <section>
            <h2>3. Acceptable Use</h2>
            <p>You agree to use HiveSense only for its intended purpose — monitoring and managing hive data for CRAC. You agree not to:</p>
            <ul>
                <li>Attempt to access accounts, hives, or data you are not authorized to view or manage</li>
                <li>Interfere with or disrupt the system, its sensors, or its underlying infrastructure</li>
                <li>Submit false, tampered, or misleading sensor data through the ingest endpoint</li>
                <li>Use the system for any purpose outside CRAC's apiculture research and monitoring activities</li>
            </ul>
        </section>

        <section>
            <h2>4. Data Collected</h2>
            <p>HiveSense collects and stores:</p>
            <ul>
                <li><strong>Sensor data</strong> — temperature, humidity, CO₂, and food-level readings transmitted from hive-mounted ESP32 devices</li>
                <li><strong>Inspection records</strong> — manually entered colony health, queen, and treatment notes logged by apiarists</li>
                <li><strong>Account information</strong> — username, email, full name, and role, used solely for authentication and access control</li>
            </ul>
            <p>Sensor and inspection data is used for hive health monitoring, research, and reporting purposes within CRAC. It is not sold or shared with third parties outside the scope of this project.</p>
        </section>

        <section>
            <h2>5. Data Accuracy</h2>
            <p>Sensor readings, including food-level measurements, are provided by hardware proxies and should be treated as indicative rather than exact. HiveSense is a monitoring aid and does not replace direct physical hive inspection by a qualified apiarist.</p>
        </section>

        <section>
            <h2>6. Intellectual Property</h2>
            <p>The HiveSense source code, design, and documentation are the original work of its developer(s), produced as part of an academic capstone requirement. All rights to the underlying software are reserved by the developer(s) and CRAC unless otherwise agreed in writing.</p>
        </section>

        <section>
            <h2>7. Availability</h2>
            <p>HiveSense is provided on an as-is, best-effort basis as an academic project. Uptime, data retention, and feature availability are not guaranteed, and the system may be modified, suspended, or discontinued at any time, particularly during the capstone development and defense period.</p>
        </section>

        <section>
            <h2>8. Changes to These Terms</h2>
            <p>These terms may be updated as the project evolves. Continued use of HiveSense after changes are posted constitutes acceptance of the revised terms.</p>
        </section>

        <section>
            <h2>9. Contact</h2>
            <p>Questions about these terms or about HiveSense can be directed to the CRAC administrator or the development team.</p>
        </section>

    </div>
</div>

</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HiveSense - Smart Hive Monitoring</title>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
      <!-- link to css -->
    <link rel="stylesheet" href="<?= ROOT ?>/public/assets/css/header.css">
</head>
<body>

<button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()" aria-label="Open menu">
    <i class="fas fa-bars" id="sidebarToggleIcon"></i>
</button>
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeSidebar()"></div>
<script src="<?= ROOT ?>/public/assets/js/header.js" defer></script>

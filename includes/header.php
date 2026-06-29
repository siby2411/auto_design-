<?php
// includes/header.php
$pageTitle = $pageTitle ?? 'Omega Tech Auto';
$currentPage = $currentPage ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> — Omega Tech Auto</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<!-- CORRECTION: Utiliser des chemins absolus depuis la racine -->
<link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-logo">
      <span class="brand-icon">⟁</span>
    </div>
    <div class="brand-text">
      <span class="brand-name">OMEGA</span>
      <span class="brand-sub">TECH AUTO</span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Principal</div>
    <a href="/index.php" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
      <i class="fa-solid fa-chart-line"></i>
      <span>Tableau de bord</span>
    </a>
    <a href="/galerie.php" class="nav-item <?= $currentPage === 'galerie' ? 'active' : '' ?>">
      <i class="fa-solid fa-car"></i>
      <span>Galerie Véhicules</span>
    </a>
    
    <div class="nav-section-label">Gestion</div>
    <a href="/vehicles.php" class="nav-item <?= $currentPage === 'vehicles' ? 'active' : '' ?>">
      <i class="fa-solid fa-list"></i>
      <span>Inventaire</span>
    </a>
    <a href="/rentals.php" class="nav-item <?= $currentPage === 'rentals' ? 'active' : '' ?>">
      <i class="fa-solid fa-key"></i>
      <span>Locations</span>
    </a>
    <a href="/sales.php" class="nav-item <?= $currentPage === 'sales' ? 'active' : '' ?>">
      <i class="fa-solid fa-handshake"></i>
      <span>Ventes</span>
    </a>
    <a href="/clients.php" class="nav-item <?= $currentPage === 'clients' ? 'active' : '' ?>">
      <i class="fa-solid fa-users"></i>
      <span>Clients</span>
    </a>
    
    <div class="nav-section-label">Documents</div>
    <a href="/invoices.php" class="nav-item <?= $currentPage === 'invoices' ? 'active' : '' ?>">
      <i class="fa-solid fa-file-invoice"></i>
      <span>Factures</span>
    </a>
    
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <div class="nav-section-label">Admin</div>
    <a href="/users.php" class="nav-item <?= $currentPage === 'users' ? 'active' : '' ?>">
      <i class="fa-solid fa-user-gear"></i>
      <span>Utilisateurs</span>
    </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar"><?= strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)) ?></div>
      <div class="user-meta">
        <span class="user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Utilisateur') ?></span>
        <span class="user-role"><?= ucfirst($_SESSION['role'] ?? '') ?></span>
      </div>
    </div>
    <a href="/logout.php" class="logout-btn" title="Déconnexion">
      <i class="fa-solid fa-right-from-bracket"></i>
    </a>
  </div>
</aside>

<!-- OVERLAY mobile -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- MAIN CONTENT -->
<div class="main-wrapper">

  <!-- TOPBAR -->
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
      </button>
      <div class="breadcrumb-area">
        <span class="breadcrumb-title"><?= htmlspecialchars($pageTitle) ?></span>
      </div>
    </div>
    <div class="topbar-right">
      <div class="topbar-date">
        <i class="fa-regular fa-calendar"></i>
        <?= date('d/m/Y') ?>
      </div>
      <?php 
      if (function_exists('getFlash')) {
        $flash = getFlash(); 
      }
      ?>
    </div>
  </header>

  <?php if (isset($flash) && $flash): ?>
  <div class="flash-message flash-<?= $flash['type'] ?>" id="flashMsg">
    <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
    <?= htmlspecialchars($flash['msg']) ?>
    <button onclick="document.getElementById('flashMsg').remove()"><i class="fa-solid fa-xmark"></i></button>
  </div>
  <?php endif; ?>

  <main class="page-content">

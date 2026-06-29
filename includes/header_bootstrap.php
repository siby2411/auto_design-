<?php
// includes/header_bootstrap.php - Version Bootstrap
$pageTitle = $pageTitle ?? 'Omega Tech Auto';
$currentPage = $currentPage ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> — Omega Tech Auto</title>
<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --gold: #C9A84C;
  --gold-light: #E8C86A;
  --gold-pale: #FBF5E6;
  --dark: #0A0A0A;
}
body {
  font-family: 'Inter', sans-serif;
  background: #F8F8FA;
}
.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  width: 260px;
  height: 100vh;
  background: var(--dark);
  color: #fff;
  z-index: 1000;
  overflow-y: auto;
  transition: transform 0.3s ease;
}
.sidebar-brand {
  padding: 24px 20px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
  display: flex;
  align-items: center;
  gap: 12px;
}
.brand-logo {
  width: 42px;
  height: 42px;
  background: var(--gold);
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  color: var(--dark);
}
.brand-name {
  font-family: 'Space Grotesk', sans-serif;
  font-size: 15px;
  font-weight: 700;
  letter-spacing: 0.12em;
}
.brand-sub {
  font-size: 10px;
  font-weight: 500;
  color: var(--gold);
  letter-spacing: 0.2em;
  text-transform: uppercase;
}
.nav-section-label {
  padding: 12px 20px 4px;
  font-size: 10px;
  font-weight: 600;
  color: #9898A8;
  text-transform: uppercase;
  letter-spacing: 0.15em;
}
.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 20px;
  color: #C8C8D8;
  text-decoration: none;
  font-size: 14px;
  transition: all 0.2s;
  border-left: 3px solid transparent;
}
.nav-item:hover {
  color: #fff;
  background: rgba(255,255,255,0.04);
}
.nav-item.active {
  color: var(--gold);
  background: rgba(201,168,76,0.08);
  border-left-color: var(--gold);
}
.sidebar-footer {
  padding: 16px 20px;
  border-top: 1px solid rgba(255,255,255,0.06);
  display: flex;
  align-items: center;
  gap: 10px;
}
.user-avatar {
  width: 34px;
  height: 34px;
  background: var(--gold);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 700;
  color: var(--dark);
}
.user-name {
  font-size: 13px;
  font-weight: 500;
}
.user-role {
  font-size: 11px;
  color: #9898A8;
  text-transform: capitalize;
}
.logout-btn {
  color: #9898A8;
  text-decoration: none;
  padding: 4px 8px;
  border-radius: 4px;
  transition: all 0.2s;
}
.logout-btn:hover {
  color: #DC2626;
  background: rgba(220,38,38,0.1);
}
.main-wrapper {
  margin-left: 260px;
  min-height: 100vh;
}
.topbar {
  position: sticky;
  top: 0;
  z-index: 50;
  height: 64px;
  background: #fff;
  border-bottom: 1px solid #E2E2EA;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 28px;
}
.menu-toggle {
  display: none;
  background: none;
  border: none;
  font-size: 18px;
  color: #3A3A48;
}
.page-content {
  padding: 28px;
}
@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
  }
  .sidebar.open {
    transform: translateX(0);
  }
  .main-wrapper {
    margin-left: 0;
  }
  .menu-toggle {
    display: block;
  }
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-logo">⟁</div>
    <div>
      <div class="brand-name">OMEGA</div>
      <div class="brand-sub">TECH AUTO</div>
    </div>
  </div>
  <nav>
    <div class="nav-section-label">Principal</div>
    <a href="/index.php" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
      <i class="fa-solid fa-chart-line"></i> Tableau de bord
    </a>
    <a href="/galerie.php" class="nav-item <?= $currentPage === 'galerie' ? 'active' : '' ?>">
      <i class="fa-solid fa-car"></i> Galerie Véhicules
    </a>
    <div class="nav-section-label">Gestion</div>
    <a href="/vehicles.php" class="nav-item <?= $currentPage === 'vehicles' ? 'active' : '' ?>">
      <i class="fa-solid fa-list"></i> Inventaire
    </a>
    <a href="/rentals.php" class="nav-item <?= $currentPage === 'rentals' ? 'active' : '' ?>">
      <i class="fa-solid fa-key"></i> Locations
    </a>
    <a href="/sales.php" class="nav-item <?= $currentPage === 'sales' ? 'active' : '' ?>">
      <i class="fa-solid fa-handshake"></i> Ventes
    </a>
    <a href="/clients.php" class="nav-item <?= $currentPage === 'clients' ? 'active' : '' ?>">
      <i class="fa-solid fa-users"></i> Clients
    </a>
    <div class="nav-section-label">Documents</div>
    <a href="/invoices.php" class="nav-item <?= $currentPage === 'invoices' ? 'active' : '' ?>">
      <i class="fa-solid fa-file-invoice"></i> Factures
    </a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-avatar"><?= strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)) ?></div>
    <div>
      <div class="user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Utilisateur') ?></div>
      <div class="user-role"><?= ucfirst($_SESSION['role'] ?? '') ?></div>
    </div>
    <a href="/logout.php" class="logout-btn ms-auto"><i class="fa-solid fa-right-from-bracket"></i></a>
  </div>
</aside>

<!-- MAIN -->
<div class="main-wrapper">
  <header class="topbar">
    <div>
      <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
        <i class="fa-solid fa-bars"></i>
      </button>
      <span class="fw-bold"><?= htmlspecialchars($pageTitle) ?></span>
    </div>
    <div>
      <i class="fa-regular fa-calendar"></i> <?= date('d/m/Y') ?>
    </div>
  </header>
  <main class="page-content">

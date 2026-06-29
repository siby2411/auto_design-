<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$db = getDB();

// Stats globales
$stats = [];
$stats['vehicles_total']    = $db->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
$stats['vehicles_dispo']    = $db->query("SELECT COUNT(*) FROM vehicles WHERE status='disponible'")->fetchColumn();
$stats['locations_actives'] = $db->query("SELECT COUNT(*) FROM rentals WHERE status='en_cours'")->fetchColumn();
$stats['ventes_mois']       = $db->query("SELECT COUNT(*) FROM sales WHERE MONTH(sale_date)=MONTH(NOW()) AND YEAR(sale_date)=YEAR(NOW())")->fetchColumn();
$stats['ca_mois']           = $db->query("SELECT COALESCE(SUM(final_price),0) FROM sales WHERE MONTH(sale_date)=MONTH(NOW()) AND status='complete'")->fetchColumn();
$stats['revenus_location']  = $db->query("SELECT COALESCE(SUM(total_amount),0) FROM rentals WHERE status='termine' AND MONTH(created_at)=MONTH(NOW())")->fetchColumn();

// Derniers véhicules
$vehiclesRecents = $db->query("SELECT v.*, vc.name as cat_name FROM vehicles v LEFT JOIN vehicle_categories vc ON v.category_id=vc.id ORDER BY v.created_at DESC LIMIT 6")->fetchAll();

// Dernières locations
$locationsRecentes = $db->query("SELECT r.*, v.brand, v.model, c.full_name as client_name FROM rentals r JOIN vehicles v ON r.vehicle_id=v.id JOIN clients c ON r.client_id=c.id ORDER BY r.created_at DESC LIMIT 5")->fetchAll();

// Répartition statuts
$statsStatus = $db->query("SELECT status, COUNT(*) as cnt FROM vehicles GROUP BY status")->fetchAll();

$pageTitle = 'Tableau de bord';
$currentPage = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- BANNER -->
<div class="omega-banner">
  <div class="banner-icon">⟁</div>
  <div class="banner-content">
    <div class="banner-title"><span>Omega Tech</span> Auto — Tableau de bord</div>
    <div class="banner-desc">Vue d'ensemble de votre activité · <?= date('l d F Y') ?></div>
  </div>
</div>

<!-- STATS GRID -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon gold"><i class="fa-solid fa-car"></i></div>
    <div class="stat-content">
      <div class="stat-value"><?= $stats['vehicles_total'] ?></div>
      <div class="stat-label">Véhicules total</div>
      <div class="stat-change up"><i class="fa-solid fa-circle"></i> <?= $stats['vehicles_dispo'] ?> disponibles</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon info"><i class="fa-solid fa-key"></i></div>
    <div class="stat-content">
      <div class="stat-value"><?= $stats['locations_actives'] ?></div>
      <div class="stat-label">Locations actives</div>
      <div class="stat-change up">En cours ce mois</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon success"><i class="fa-solid fa-handshake"></i></div>
    <div class="stat-content">
      <div class="stat-value"><?= $stats['ventes_mois'] ?></div>
      <div class="stat-label">Ventes ce mois</div>
      <div class="stat-change up"><?= formatPrice((float)$stats['ca_mois']) ?></div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon warning"><i class="fa-solid fa-wallet"></i></div>
    <div class="stat-content">
      <div class="stat-value"><?= number_format((float)$stats['ca_mois'] + (float)$stats['revenus_location'], 0, ',', ' ') ?></div>
      <div class="stat-label">Revenus FCFA (mois)</div>
      <div class="stat-change up">Ventes + Locations</div>
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start">

  <!-- LOCATIONS RÉCENTES -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Locations récentes</span>
      <a href="/rentals.php" class="btn btn-outline btn-sm">Voir tout</a>
    </div>
    <?php if ($locationsRecentes): ?>
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>Référence</th>
            <th>Véhicule</th>
            <th>Client</th>
            <th>Période</th>
            <th>Statut</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($locationsRecentes as $loc): ?>
          <tr>
            <td><span class="table-ref"><?= sanitize($loc['reference']) ?></span></td>
            <td>
              <div class="table-name"><?= sanitize($loc['brand']) ?> <?= sanitize($loc['model']) ?></div>
            </td>
            <td><?= sanitize($loc['client_name']) ?></td>
            <td>
              <div style="font-size:13px"><?= date('d/m/Y', strtotime($loc['start_date'])) ?></div>
              <div class="table-sub">→ <?= date('d/m/Y', strtotime($loc['end_date'])) ?></div>
            </td>
            <td><?= statusBadge($loc['status']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
      <div class="empty-state-icon"><i class="fa-solid fa-key"></i></div>
      <div class="empty-state-title">Aucune location</div>
    </div>
    <?php endif; ?>
  </div>

  <!-- STATUS OVERVIEW + QUICK ACTIONS -->
  <div style="display:flex;flex-direction:column;gap:20px">

    <!-- Statuts véhicules -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">Statut flotte</span>
      </div>
      <div class="card-body" style="padding-bottom:16px">
        <?php
        $statusLabels = ['disponible'=>'Disponible','loue'=>'Loué','vendu'=>'Vendu','maintenance'=>'Maintenance'];
        $statusColors = ['disponible'=>'#16A34A','loue'=>'#2563EB','vendu'=>'#374151','maintenance'=>'#DC2626'];
        $total = max(1, array_sum(array_column($statsStatus,'cnt')));
        foreach ($statsStatus as $s):
          $pct = round($s['cnt'] / $total * 100);
          $label = $statusLabels[$s['status']] ?? $s['status'];
          $color = $statusColors[$s['status']] ?? '#ccc';
        ?>
        <div style="margin-bottom:16px">
          <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px">
            <span style="font-weight:500;color:#3A3A48"><?= $label ?></span>
            <span style="color:#9898A8"><?= $s['cnt'] ?> (<?= $pct ?>%)</span>
          </div>
          <div style="height:6px;background:#F0F0F4;border-radius:100px;overflow:hidden">
            <div style="height:100%;width:<?= $pct ?>%;background:<?= $color ?>;border-radius:100px;transition:width .6s ease"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Accès rapide -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">Accès rapide</span>
      </div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
        <a href="/vehicles.php?action=new" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nouveau véhicule</a>
        <a href="/rentals.php?action=new" class="btn btn-dark"><i class="fa-solid fa-key"></i> Nouvelle location</a>
        <a href="/sales.php?action=new" class="btn btn-outline"><i class="fa-solid fa-handshake"></i> Nouvelle vente</a>
        <a href="/clients.php?action=new" class="btn btn-outline"><i class="fa-solid fa-user-plus"></i> Nouveau client</a>
        <a href="/galerie.php" class="btn btn-outline"><i class="fa-solid fa-images"></i> Galerie véhicules</a>
      </div>
    </div>
  </div>
</div>

<!-- VÉHICULES RÉCENTS -->
<div style="margin-top:28px">
  <div class="page-header">
    <div>
      <div class="page-title">Derniers véhicules</div>
      <div class="page-subtitle">Ajouts récents à votre inventaire</div>
    </div>
    <a href="/galerie.php" class="btn btn-outline"><i class="fa-solid fa-grid-2"></i> Galerie complète</a>
  </div>

  <div class="gallery-grid">
    <?php foreach ($vehiclesRecents as $v):
      $statusColors = ['disponible'=>'badge-success','loue'=>'badge-info','vendu'=>'badge-dark','maintenance'=>'badge-danger'];
    ?>
    <div class="vehicle-card" data-type="<?= $v['type'] ?>" data-url="/vehicles.php?action=view&id=<?= $v['id'] ?>">
      <div class="vehicle-card-image">
        <?php if ($v['main_image']): ?>
          <img src="/uploads/vehicles/<?= sanitize($v['main_image']) ?>" alt="<?= sanitize($v['brand'].' '.$v['model']) ?>">
        <?php else: ?>
          <div class="no-image">🚗</div>
        <?php endif; ?>
        <div class="vehicle-card-badges">
          <?= statusBadge($v['status']) ?>
        </div>
        <?php if ($v['rental_price_day']): ?>
        <div class="vehicle-card-price"><?= number_format($v['rental_price_day'],0,',',' ') ?> / jour</div>
        <?php endif; ?>
      </div>
      <div class="vehicle-card-body">
        <div class="vehicle-card-brand"><?= sanitize($v['brand']) ?></div>
        <div class="vehicle-card-name"><?= sanitize($v['model']) ?></div>
        <div class="vehicle-card-year"><?= $v['year'] ?> · <?= sanitize($v['cat_name'] ?? '') ?></div>
        <div class="vehicle-specs">
          <div class="spec-item"><i class="fa-solid fa-gas-pump"></i> <?= ucfirst($v['fuel_type']) ?></div>
          <div class="spec-item"><i class="fa-solid fa-gears"></i> <?= ucfirst($v['transmission']) ?></div>
          <div class="spec-item"><i class="fa-solid fa-users"></i> <?= $v['seats'] ?> places</div>
          <div class="spec-item"><i class="fa-solid fa-road"></i> <?= number_format($v['mileage'],0,',',' ') ?> km</div>
        </div>
      </div>
      <div class="vehicle-card-footer">
        <?php if ($v['sale_price']): ?>
        <a href="/sales.php?action=new&vid=<?= $v['id'] ?>" class="btn btn-primary btn-sm" onclick="event.stopPropagation()">
          <i class="fa-solid fa-handshake"></i> Vendre
        </a>
        <?php endif; ?>
        <?php if ($v['rental_price_day'] && $v['status'] === 'disponible'): ?>
        <a href="/rentals.php?action=new&vid=<?= $v['id'] ?>" class="btn btn-dark btn-sm" onclick="event.stopPropagation()">
          <i class="fa-solid fa-key"></i> Louer
        </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

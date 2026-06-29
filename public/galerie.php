<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$db = getDB();
$filterType = $_GET['type'] ?? 'all';
$filterCat  = (int)($_GET['cat'] ?? 0);
$search     = trim($_GET['q'] ?? '');

$where  = '1=1';
$params = [];
if ($filterType !== 'all') {
    $where .= ' AND (v.type = ? OR v.type = \'les_deux\')';
    $params[] = $filterType;
}
if ($filterCat) { $where .= ' AND v.category_id=?'; $params[] = $filterCat; }
if ($search) {
    $where .= ' AND (v.brand LIKE ? OR v.model LIKE ? OR v.color LIKE ?)';
    $s = "%$search%";
    array_push($params, $s, $s, $s);
}
$where .= " AND v.status != 'vendu'";

$st = $db->prepare("SELECT v.*, vc.name as cat_name FROM vehicles v 
    LEFT JOIN vehicle_categories vc ON v.category_id=vc.id 
    WHERE $where ORDER BY v.status='disponible' DESC, v.created_at DESC");
$st->execute($params);
$vehicles = $st->fetchAll();

$categories = $db->query("SELECT * FROM vehicle_categories ORDER BY name")->fetchAll();

// Counts
$cntAll   = $db->query("SELECT COUNT(*) FROM vehicles WHERE status!='vendu'")->fetchColumn();
$cntVente = $db->query("SELECT COUNT(*) FROM vehicles WHERE (type='vente' OR type='les_deux') AND status!='vendu'")->fetchColumn();
$cntLoc   = $db->query("SELECT COUNT(*) FROM vehicles WHERE (type='location' OR type='les_deux') AND status!='vendu'")->fetchColumn();

$pageTitle   = 'Galerie Véhicules';
$currentPage = 'galerie';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- BANNER OMEGA -->
<div class="omega-banner" style="margin-bottom:28px">
  <div class="banner-icon" style="font-size:32px">🚗</div>
  <div class="banner-content">
    <div class="banner-title"><span>Omega Tech Auto</span> — Notre Sélection</div>
    <div class="banner-desc"><?= count($vehicles) ?> véhicule<?= count($vehicles)>1?'s':'' ?> · Flotte disponible à la vente et à la location</div>
  </div>
  <a href="/vehicles.php?action=new" class="btn btn-primary" style="margin-left:auto;flex-shrink:0">
    <i class="fa-solid fa-plus"></i> Ajouter
  </a>
</div>

<!-- SERIES TABS -->
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px">
  <div class="series-tabs">
    <a href="?type=all" class="series-tab <?= $filterType==='all'?'active':'' ?>">
      Tous <span style="opacity:.5"><?= $cntAll ?></span>
    </a>
    <a href="?type=vente" class="series-tab <?= $filterType==='vente'?'active':'' ?>">
      Vente <span style="opacity:.5"><?= $cntVente ?></span>
    </a>
    <a href="?type=location" class="series-tab <?= $filterType==='location'?'active':'' ?>">
      Location <span style="opacity:.5"><?= $cntLoc ?></span>
    </a>
    <?php foreach ($categories as $cat): ?>
    <a href="?cat=<?= $cat['id'] ?>" class="series-tab <?= $filterCat==$cat['id']?'active':'' ?>">
      <?= sanitize($cat['name']) ?>
    </a>
    <?php endforeach; ?>
  </div>

  <form method="get" style="display:flex;gap:8px;align-items:center">
    <?php if ($filterType !== 'all'): ?><input type="hidden" name="type" value="<?= $filterType ?>"><?php endif; ?>
    <div class="search-input-wrap" style="min-width:220px">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" name="q" placeholder="Rechercher…" value="<?= sanitize($search) ?>">
    </div>
    <button type="submit" class="btn btn-dark btn-sm"><i class="fa-solid fa-search"></i></button>
    <?php if ($search || $filterCat): ?>
    <a href="/galerie.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i></a>
    <?php endif; ?>
  </form>
</div>

<!-- GALLERY GRID -->
<?php if ($vehicles): ?>
<div class="gallery-grid">
  <?php foreach ($vehicles as $v):
    $typeLabels = ['vente'=>'Vente','location'=>'Location','les_deux'=>'V+L'];
  ?>
  <div class="vehicle-card" data-type="<?= $v['type'] ?>" data-url="/vehicles.php?action=view&id=<?= $v['id'] ?>">
    <div class="vehicle-card-image">
      <?php if ($v['main_image']): ?>
        <img src="/uploads/vehicles/<?= sanitize($v['main_image']) ?>" 
             alt="<?= sanitize($v['brand'].' '.$v['model']) ?>"
             loading="lazy">
      <?php else: ?>
        <div class="no-image">🚗</div>
      <?php endif; ?>

      <div class="vehicle-card-badges">
        <?= statusBadge($v['status']) ?>
        <span class="badge <?= $v['type']==='les_deux'?'badge-gold':($v['type']==='vente'?'badge-dark':'badge-info') ?>">
          <?= $typeLabels[$v['type']] ?? $v['type'] ?>
        </span>
      </div>

      <?php if ($v['rental_price_day']): ?>
      <div class="vehicle-card-price"><?= number_format($v['rental_price_day'],0,',',' ') ?> / j</div>
      <?php endif; ?>
    </div>

    <div class="vehicle-card-body">
      <div class="vehicle-card-brand"><?= sanitize($v['brand']) ?></div>
      <div class="vehicle-card-name"><?= sanitize($v['model']) ?></div>
      <div class="vehicle-card-year"><?= $v['year'] ?> · <?= sanitize($v['color']) ?></div>

      <div class="vehicle-specs">
        <div class="spec-item"><i class="fa-solid fa-gas-pump"></i><?= ucfirst($v['fuel_type']) ?></div>
        <div class="spec-item"><i class="fa-solid fa-gears"></i><?= ucfirst($v['transmission']) ?></div>
        <div class="spec-item"><i class="fa-solid fa-users"></i><?= $v['seats'] ?> places</div>
        <div class="spec-item"><i class="fa-solid fa-road"></i><?= number_format($v['mileage'],0,',',' ') ?> km</div>
      </div>

      <?php if ($v['sale_price']): ?>
      <div style="margin-top:14px;padding:10px;background:var(--gold-pale);border-radius:8px;text-align:center">
        <div style="font-size:11px;color:var(--gray-500);font-weight:600;text-transform:uppercase;letter-spacing:.08em">Prix de vente</div>
        <div style="font-family:'Space Grotesk',sans-serif;font-size:18px;font-weight:700;color:var(--black);margin-top:2px"><?= formatPrice((float)$v['sale_price']) ?></div>
      </div>
      <?php endif; ?>
    </div>

    <div class="vehicle-card-footer">
      <a href="/vehicles.php?action=view&id=<?= $v['id'] ?>" class="btn btn-outline btn-sm" onclick="event.stopPropagation()">
        <i class="fa-solid fa-eye"></i> Détails
      </a>
      <?php if ($v['status'] === 'disponible'): ?>
        <?php if ($v['type'] !== 'location'): ?>
        <a href="/sales.php?action=new&vid=<?= $v['id'] ?>" class="btn btn-dark btn-sm" onclick="event.stopPropagation()">
          <i class="fa-solid fa-handshake"></i> Vendre
        </a>
        <?php endif; ?>
        <?php if ($v['type'] !== 'vente' && $v['rental_price_day']): ?>
        <a href="/rentals.php?action=new&vid=<?= $v['id'] ?>" class="btn btn-primary btn-sm" onclick="event.stopPropagation()">
          <i class="fa-solid fa-key"></i> Louer
        </a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php else: ?>
<div class="empty-state">
  <div class="empty-state-icon"><i class="fa-solid fa-car"></i></div>
  <div class="empty-state-title">Aucun véhicule trouvé</div>
  <div class="empty-state-desc">Ajustez vos filtres ou ajoutez des véhicules à votre inventaire.</div>
  <a href="/vehicles.php?action=new" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Ajouter un véhicule</a>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

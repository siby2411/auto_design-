<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// ─── DELETE ───────────────────────────────
if ($action === 'delete' && $id && isAdmin()) {
    $db->prepare("DELETE FROM vehicles WHERE id=?")->execute([$id]);
    flash('success', 'Véhicule supprimé.');
    header('Location: /vehicles.php'); exit;
}

// ─── SAVE (create/update) ─────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'reference'        => trim($_POST['reference'] ?? ''),
        'brand'            => trim($_POST['brand'] ?? ''),
        'model'            => trim($_POST['model'] ?? ''),
        'year'             => (int)($_POST['year'] ?? date('Y')),
        'color'            => trim($_POST['color'] ?? ''),
        'fuel_type'        => $_POST['fuel_type'] ?? 'essence',
        'transmission'     => $_POST['transmission'] ?? 'manuelle',
        'mileage'          => (int)($_POST['mileage'] ?? 0),
        'seats'            => (int)($_POST['seats'] ?? 5),
        'doors'            => (int)($_POST['doors'] ?? 4),
        'power_hp'         => (int)($_POST['power_hp'] ?? 0) ?: null,
        'category_id'      => (int)($_POST['category_id'] ?? 0) ?: null,
        'sale_price'       => (float)($_POST['sale_price'] ?? 0) ?: null,
        'rental_price_day' => (float)($_POST['rental_price_day'] ?? 0) ?: null,
        'status'           => $_POST['status'] ?? 'disponible',
        'type'             => $_POST['type'] ?? 'les_deux',
        'description'      => trim($_POST['description'] ?? ''),
        'features'         => trim($_POST['features'] ?? ''),
        'user_id'          => $_SESSION['user_id'],
    ];

    // Upload image
    $imgName = null;
    if (!empty($_FILES['main_image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
            $imgName = uniqid('veh_') . '.' . $ext;
            $dest = UPLOAD_DIR . $imgName;
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
            move_uploaded_file($_FILES['main_image']['tmp_name'], $dest);
        }
    }

    if ($id) {
        // UPDATE
        $sql = "UPDATE vehicles SET brand=?,model=?,year=?,color=?,fuel_type=?,transmission=?,
                mileage=?,seats=?,doors=?,power_hp=?,category_id=?,sale_price=?,
                rental_price_day=?,status=?,type=?,description=?,features=?" .
               ($imgName ? ",main_image=?" : "") .
               " WHERE id=?";
        $params = [$data['brand'],$data['model'],$data['year'],$data['color'],$data['fuel_type'],
                   $data['transmission'],$data['mileage'],$data['seats'],$data['doors'],$data['power_hp'],
                   $data['category_id'],$data['sale_price'],$data['rental_price_day'],$data['status'],
                   $data['type'],$data['description'],$data['features']];
        if ($imgName) $params[] = $imgName;
        $params[] = $id;
        $db->prepare($sql)->execute($params);
        flash('success', 'Véhicule mis à jour.');
    } else {
        // INSERT
        $ref = $data['reference'] ?: generateRef('OTA');
        $sql = "INSERT INTO vehicles (reference,brand,model,year,color,fuel_type,transmission,
                mileage,seats,doors,power_hp,category_id,sale_price,rental_price_day,
                status,type,description,features,main_image,user_id)
                VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $db->prepare($sql)->execute([$ref,$data['brand'],$data['model'],$data['year'],
            $data['color'],$data['fuel_type'],$data['transmission'],$data['mileage'],
            $data['seats'],$data['doors'],$data['power_hp'],$data['category_id'],
            $data['sale_price'],$data['rental_price_day'],$data['status'],$data['type'],
            $data['description'],$data['features'],$imgName,$data['user_id']]);
        flash('success', 'Véhicule créé avec succès.');
    }
    header('Location: /vehicles.php'); exit;
}

// ─── LOAD ONE ─────────────────────────────
$vehicle = null;
if ($id && in_array($action, ['edit','view'])) {
    $st = $db->prepare("SELECT v.*, vc.name as cat_name FROM vehicles v LEFT JOIN vehicle_categories vc ON v.category_id=vc.id WHERE v.id=?");
    $st->execute([$id]);
    $vehicle = $st->fetch();
    if (!$vehicle) { flash('error','Véhicule introuvable.'); header('Location: /vehicles.php'); exit; }
}

// ─── LIST ─────────────────────────────────
$filterStatus = $_GET['status'] ?? '';
$filterType   = $_GET['type'] ?? '';
$search       = trim($_GET['q'] ?? '');
$page         = max(1, (int)($_GET['p'] ?? 1));
$perPage      = 15;

$where = '1=1';
$params = [];
if ($filterStatus) { $where .= ' AND v.status=?'; $params[] = $filterStatus; }
if ($filterType)   { $where .= ' AND v.type=?';   $params[] = $filterType; }
if ($search) {
    $where .= ' AND (v.brand LIKE ? OR v.model LIKE ? OR v.reference LIKE ? OR v.color LIKE ?)';
    $s = "%$search%";
    array_push($params, $s, $s, $s, $s);
}

$total = $db->prepare("SELECT COUNT(*) FROM vehicles v WHERE $where");
$total->execute($params);
$totalRows = $total->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$offset = ($page - 1) * $perPage;

$vehicles = $db->prepare("SELECT v.*, vc.name as cat_name FROM vehicles v 
    LEFT JOIN vehicle_categories vc ON v.category_id=vc.id 
    WHERE $where ORDER BY v.created_at DESC LIMIT $perPage OFFSET $offset");
$vehicles->execute($params);
$vehicles = $vehicles->fetchAll();

$categories = $db->query("SELECT * FROM vehicle_categories ORDER BY name")->fetchAll();

$pageTitle = $action === 'new' ? 'Nouveau véhicule' : ($action === 'edit' ? 'Modifier véhicule' : ($action === 'view' ? ($vehicle['brand'].' '.$vehicle['model']) : 'Inventaire'));
$currentPage = 'vehicles';
require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($action === 'list'): ?>
<!-- LISTE -->
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title">Inventaire véhicules</div>
    <div class="page-subtitle"><?= $totalRows ?> véhicule<?= $totalRows > 1 ? 's' : '' ?></div>
  </div>
  <div class="d-flex gap-8">
    <a href="/galerie.php" class="btn btn-outline"><i class="fa-solid fa-grid-2"></i> Galerie</a>
    <a href="/vehicles.php?action=new" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nouveau</a>
  </div>
</div>

<!-- FILTRES -->
<form method="get" class="filter-bar">
  <input type="hidden" name="action" value="list">
  <div class="search-input-wrap">
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="text" name="q" placeholder="Rechercher marque, modèle, référence…" value="<?= sanitize($search) ?>">
  </div>
  <select name="status" class="filter-select">
    <option value="">Tous statuts</option>
    <option value="disponible" <?= $filterStatus==='disponible'?'selected':'' ?>>Disponible</option>
    <option value="loue"       <?= $filterStatus==='loue'?'selected':'' ?>>Loué</option>
    <option value="vendu"      <?= $filterStatus==='vendu'?'selected':'' ?>>Vendu</option>
    <option value="maintenance"<?= $filterStatus==='maintenance'?'selected':'' ?>>Maintenance</option>
  </select>
  <select name="type" class="filter-select">
    <option value="">Tous types</option>
    <option value="vente"    <?= $filterType==='vente'?'selected':'' ?>>Vente</option>
    <option value="location" <?= $filterType==='location'?'selected':'' ?>>Location</option>
    <option value="les_deux" <?= $filterType==='les_deux'?'selected':'' ?>>Vente & Location</option>
  </select>
  <button type="submit" class="btn btn-dark btn-sm"><i class="fa-solid fa-filter"></i> Filtrer</button>
  <?php if ($search || $filterStatus || $filterType): ?>
  <a href="/vehicles.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i></a>
  <?php endif; ?>
</form>

<!-- TABLE -->
<div class="card">
  <?php if ($vehicles): ?>
  <div class="table-wrapper">
    <table class="data-table" id="vehicleTable">
      <thead>
        <tr>
          <th>Réf.</th>
          <th>Véhicule</th>
          <th>Année</th>
          <th>Catégorie</th>
          <th>Carburant</th>
          <th>Prix vente</th>
          <th>Location/jour</th>
          <th>Statut</th>
          <th>Type</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($vehicles as $v): ?>
        <tr>
          <td><span class="table-ref"><?= sanitize($v['reference']) ?></span></td>
          <td>
            <div style="display:flex;align-items:center;gap:12px">
              <?php if ($v['main_image']): ?>
              <img src="/uploads/vehicles/<?= sanitize($v['main_image']) ?>" 
                   style="width:44px;height:34px;object-fit:cover;border-radius:6px;border:1px solid #E2E2EA">
              <?php else: ?>
              <div style="width:44px;height:34px;background:#F0F0F4;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:18px">🚗</div>
              <?php endif; ?>
              <div>
                <div class="table-name"><?= sanitize($v['brand']) ?> <?= sanitize($v['model']) ?></div>
                <div class="table-sub"><?= sanitize($v['color']) ?></div>
              </div>
            </div>
          </td>
          <td><?= $v['year'] ?></td>
          <td><?= sanitize($v['cat_name'] ?? '—') ?></td>
          <td><?= ucfirst($v['fuel_type']) ?></td>
          <td style="font-weight:600"><?= $v['sale_price'] ? formatPrice((float)$v['sale_price']) : '—' ?></td>
          <td><?= $v['rental_price_day'] ? formatPrice((float)$v['rental_price_day']) : '—' ?></td>
          <td><?= statusBadge($v['status']) ?></td>
          <td>
            <span class="badge <?= $v['type']==='les_deux'?'badge-gold':($v['type']==='vente'?'badge-dark':'badge-info') ?>">
              <?= $v['type']==='les_deux'?'V + L':ucfirst($v['type']) ?>
            </span>
          </td>
          <td>
            <div class="actions-cell">
              <a href="/vehicles.php?action=view&id=<?= $v['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Voir"><i class="fa-solid fa-eye"></i></a>
              <a href="/vehicles.php?action=edit&id=<?= $v['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Modifier"><i class="fa-solid fa-pen"></i></a>
              <?php if (isAdmin()): ?>
              <form method="post" action="/vehicles.php?action=delete&id=<?= $v['id'] ?>" style="display:inline">
                <button type="submit" class="btn btn-outline btn-sm btn-icon" title="Supprimer"
                        onclick="return confirm('Supprimer ce véhicule ?')">
                  <i class="fa-solid fa-trash" style="color:var(--danger)"></i>
                </button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- PAGINATION -->
  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
    <a href="?p=<?= $p ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($filterStatus) ?>&type=<?= urlencode($filterType) ?>"
       class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <div class="empty-state">
    <div class="empty-state-icon"><i class="fa-solid fa-car"></i></div>
    <div class="empty-state-title">Aucun véhicule trouvé</div>
    <div class="empty-state-desc">Modifiez vos filtres ou ajoutez un nouveau véhicule.</div>
    <a href="/vehicles.php?action=new" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Ajouter</a>
  </div>
  <?php endif; ?>
</div>

<?php elseif ($action === 'view' && $vehicle): ?>
<!-- CANVAS DÉTAIL VÉHICULE -->
<div class="page-header">
  <div>
    <a href="/vehicles.php" style="color:var(--gray-400);text-decoration:none;font-size:13px">
      <i class="fa-solid fa-arrow-left"></i> Retour inventaire
    </a>
    <div class="page-title" style="margin-top:6px"><?= sanitize($vehicle['brand'].' '.$vehicle['model']) ?></div>
  </div>
  <div class="d-flex gap-8">
    <a href="/vehicles.php?action=edit&id=<?= $vehicle['id'] ?>" class="btn btn-outline"><i class="fa-solid fa-pen"></i> Modifier</a>
    <?php if ($vehicle['type'] !== 'location' && $vehicle['status'] === 'disponible'): ?>
    <a href="/sales.php?action=new&vid=<?= $vehicle['id'] ?>" class="btn btn-dark"><i class="fa-solid fa-handshake"></i> Vendre</a>
    <?php endif; ?>
    <?php if ($vehicle['type'] !== 'vente' && $vehicle['status'] === 'disponible'): ?>
    <a href="/rentals.php?action=new&vid=<?= $vehicle['id'] ?>" class="btn btn-primary"><i class="fa-solid fa-key"></i> Louer</a>
    <?php endif; ?>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start">

  <!-- CANVAS PRINCIPAL -->
  <div class="vehicle-canvas">
    <div class="vehicle-canvas-hero">
      <div class="canvas-brand-strip">
        <span>OMEGA TECH AUTO · <?= sanitize($vehicle['brand']) ?></span>
        <span class="canvas-ref"><?= sanitize($vehicle['reference']) ?></span>
      </div>
      <?php if ($vehicle['main_image']): ?>
        <img src="/uploads/vehicles/<?= sanitize($vehicle['main_image']) ?>" 
             alt="<?= sanitize($vehicle['brand'].' '.$vehicle['model']) ?>">
      <?php else: ?>
        <div style="font-size:120px;opacity:.1">🚗</div>
      <?php endif; ?>
    </div>

    <div class="vehicle-canvas-info">
      <div class="vehicle-card-brand"><?= sanitize($vehicle['brand']) ?></div>
      <div class="canvas-title"><?= sanitize($vehicle['model']) ?></div>
      <div class="canvas-subtitle"><?= $vehicle['year'] ?> · <?= sanitize($vehicle['color']) ?> · <?= sanitize($vehicle['cat_name'] ?? '') ?></div>

      <div class="canvas-price-row">
        <?php if ($vehicle['sale_price']): ?>
        <div class="canvas-price-item">
          <div class="canvas-price-label">Prix de vente</div>
          <div class="canvas-price-value gold"><?= formatPrice((float)$vehicle['sale_price']) ?></div>
        </div>
        <?php endif; ?>
        <?php if ($vehicle['rental_price_day']): ?>
        <div class="canvas-price-item">
          <div class="canvas-price-label">Location / jour</div>
          <div class="canvas-price-value"><?= formatPrice((float)$vehicle['rental_price_day']) ?></div>
        </div>
        <?php endif; ?>
      </div>

      <?php if ($vehicle['description']): ?>
      <p style="margin-top:16px;font-size:14px;color:var(--gray-500);line-height:1.7"><?= sanitize($vehicle['description']) ?></p>
      <?php endif; ?>

      <?php if ($vehicle['features']): ?>
      <div class="canvas-features">
        <?php foreach (explode(',', $vehicle['features']) as $feat): ?>
        <span class="canvas-feature-tag"><i class="fa-solid fa-check" style="color:var(--gold);font-size:10px"></i><?= sanitize(trim($feat)) ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- INFOS TECHNIQUES + STATUT -->
  <div style="display:flex;flex-direction:column;gap:16px">

    <div class="card">
      <div class="card-header"><span class="card-title">Statut</span></div>
      <div class="card-body">
        <div style="font-size:28px;text-align:center;margin-bottom:8px"><?= statusBadge($vehicle['status']) ?></div>
        <div style="text-align:center;font-size:13px;color:var(--gray-400)">
          Type: <strong><?= $vehicle['type'] === 'les_deux' ? 'Vente & Location' : ucfirst($vehicle['type']) ?></strong>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title">Fiche technique</span></div>
      <div class="card-body" style="padding-top:16px">
        <?php
        $specs = [
          ['fa-gas-pump','Carburant', ucfirst($vehicle['fuel_type'])],
          ['fa-gears','Transmission', ucfirst($vehicle['transmission'])],
          ['fa-road','Kilométrage', number_format($vehicle['mileage'],0,',',' ').' km'],
          ['fa-users','Places', $vehicle['seats']],
          ['fa-door-closed','Portes', $vehicle['doors']],
          ['fa-bolt','Puissance', $vehicle['power_hp'] ? $vehicle['power_hp'].' ch' : '—'],
        ];
        foreach ($specs as [$icon, $label, $val]):
        ?>
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--gray-100);font-size:13px">
          <span style="color:var(--gray-400);display:flex;align-items:center;gap:8px">
            <i class="fa-solid <?= $icon ?>" style="width:14px;color:var(--gray-300)"></i><?= $label ?>
          </span>
          <span style="font-weight:500;color:var(--gray-900)"><?= $val ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title">Historique</span></div>
      <div class="card-body" style="font-size:13px;color:var(--gray-400)">
        <div>Créé le <?= date('d/m/Y', strtotime($vehicle['created_at'])) ?></div>
        <div style="margin-top:6px">Réf: <?= sanitize($vehicle['reference']) ?></div>
      </div>
    </div>

  </div>
</div>

<?php elseif (in_array($action, ['new','edit'])): ?>
<!-- FORMULAIRE -->
<div class="page-header">
  <div>
    <a href="/vehicles.php" style="color:var(--gray-400);text-decoration:none;font-size:13px">
      <i class="fa-solid fa-arrow-left"></i> Retour
    </a>
    <div class="page-title" style="margin-top:6px"><?= $action==='new'?'Nouveau véhicule':'Modifier: '.sanitize($vehicle['brand'].' '.$vehicle['model']) ?></div>
  </div>
</div>

<div class="card">
<div class="card-body">
<form method="post" enctype="multipart/form-data">

<div class="form-grid">
  <div class="form-section-title">Identité du véhicule</div>

  <div class="form-group">
    <label class="form-label">Référence</label>
    <input type="text" name="reference" class="form-control" 
           value="<?= sanitize($vehicle['reference'] ?? '') ?>"
           placeholder="Auto-générée si vide" <?= $action==='edit'?'readonly':'' ?>>
  </div>

  <div class="form-group">
    <label class="form-label">Marque <span class="req">*</span></label>
    <input type="text" name="brand" class="form-control" required
           value="<?= sanitize($vehicle['brand'] ?? '') ?>" placeholder="Toyota, Mercedes…">
  </div>

  <div class="form-group">
    <label class="form-label">Modèle <span class="req">*</span></label>
    <input type="text" name="model" class="form-control" required
           value="<?= sanitize($vehicle['model'] ?? '') ?>" placeholder="Land Cruiser, Classe C…">
  </div>

  <div class="form-group">
    <label class="form-label">Année</label>
    <input type="number" name="year" class="form-control" min="1990" max="<?= date('Y')+1 ?>"
           value="<?= $vehicle['year'] ?? date('Y') ?>">
  </div>

  <div class="form-group">
    <label class="form-label">Couleur</label>
    <input type="text" name="color" class="form-control"
           value="<?= sanitize($vehicle['color'] ?? '') ?>" placeholder="Blanc Nacré, Noir…">
  </div>

  <div class="form-group">
    <label class="form-label">Catégorie</label>
    <select name="category_id" class="form-select">
      <option value="">— Choisir —</option>
      <?php foreach ($categories as $cat): ?>
      <option value="<?= $cat['id'] ?>" <?= ($vehicle['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
        <?= sanitize($cat['name']) ?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-section-title">Caractéristiques techniques</div>

  <div class="form-group">
    <label class="form-label">Carburant</label>
    <select name="fuel_type" class="form-select">
      <?php foreach (['essence','diesel','hybride','electrique'] as $f): ?>
      <option value="<?= $f ?>" <?= ($vehicle['fuel_type'] ?? '') === $f ? 'selected' : '' ?>>
        <?= ucfirst($f) ?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-group">
    <label class="form-label">Transmission</label>
    <select name="transmission" class="form-select">
      <option value="manuelle"    <?= ($vehicle['transmission'] ?? '') === 'manuelle'    ? 'selected' : '' ?>>Manuelle</option>
      <option value="automatique" <?= ($vehicle['transmission'] ?? '') === 'automatique' ? 'selected' : '' ?>>Automatique</option>
    </select>
  </div>

  <div class="form-group">
    <label class="form-label">Kilométrage (km)</label>
    <input type="number" name="mileage" class="form-control" min="0"
           value="<?= $vehicle['mileage'] ?? 0 ?>">
  </div>

  <div class="form-group">
    <label class="form-label">Nombre de places</label>
    <input type="number" name="seats" class="form-control" min="2" max="20"
           value="<?= $vehicle['seats'] ?? 5 ?>">
  </div>

  <div class="form-group">
    <label class="form-label">Portes</label>
    <input type="number" name="doors" class="form-control" min="2" max="6"
           value="<?= $vehicle['doors'] ?? 4 ?>">
  </div>

  <div class="form-group">
    <label class="form-label">Puissance (ch)</label>
    <input type="number" name="power_hp" class="form-control" min="0"
           value="<?= $vehicle['power_hp'] ?? '' ?>" placeholder="Ex: 150">
  </div>

  <div class="form-section-title">Commercial</div>

  <div class="form-group">
    <label class="form-label">Type de transaction</label>
    <select name="type" class="form-select">
      <option value="les_deux" <?= ($vehicle['type'] ?? '') === 'les_deux' ? 'selected' : '' ?>>Vente et Location</option>
      <option value="vente"    <?= ($vehicle['type'] ?? '') === 'vente'    ? 'selected' : '' ?>>Vente uniquement</option>
      <option value="location" <?= ($vehicle['type'] ?? '') === 'location' ? 'selected' : '' ?>>Location uniquement</option>
    </select>
  </div>

  <div class="form-group">
    <label class="form-label">Statut</label>
    <select name="status" class="form-select">
      <?php foreach (['disponible','loue','vendu','maintenance'] as $s): ?>
      <option value="<?= $s ?>" <?= ($vehicle['status'] ?? 'disponible') === $s ? 'selected' : '' ?>>
        <?= ['disponible'=>'Disponible','loue'=>'Loué','vendu'=>'Vendu','maintenance'=>'Maintenance'][$s] ?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-group">
    <label class="form-label">Prix de vente (FCFA)</label>
    <input type="number" name="sale_price" class="form-control" min="0" step="1000"
           value="<?= $vehicle['sale_price'] ?? '' ?>" placeholder="Ex: 25000000">
  </div>

  <div class="form-group">
    <label class="form-label">Prix location / jour (FCFA)</label>
    <input type="number" name="rental_price_day" class="form-control" min="0" step="500"
           value="<?= $vehicle['rental_price_day'] ?? '' ?>" placeholder="Ex: 45000">
  </div>

  <div class="form-group col-span-full">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="3" 
              placeholder="Décrivez ce véhicule…"><?= sanitize($vehicle['description'] ?? '') ?></textarea>
  </div>

  <div class="form-group col-span-full">
    <label class="form-label">Équipements <span class="form-hint">(séparés par des virgules)</span></label>
    <input type="text" name="features" class="form-control"
           value="<?= sanitize($vehicle['features'] ?? '') ?>"
           placeholder="GPS, Climatisation, Cuir, Caméra de recul…">
    <span class="form-hint">Ex: GPS, Climatisation, Cuir, Toit ouvrant, Caméra recul</span>
  </div>

  <div class="form-section-title">Photo principale</div>

  <div class="form-group col-span-full">
    <label class="form-label">Image du véhicule</label>
    <?php if (!empty($vehicle['main_image'])): ?>
    <div style="margin-bottom:12px">
      <img id="imgPreview" src="/uploads/vehicles/<?= sanitize($vehicle['main_image']) ?>" 
           style="max-height:200px;border-radius:10px;border:1px solid var(--gray-200);object-fit:contain;background:var(--gray-50);padding:8px">
    </div>
    <?php else: ?>
    <img id="imgPreview" style="display:none;max-height:200px;border-radius:10px;border:1px solid var(--gray-200);margin-bottom:12px">
    <?php endif; ?>
    <input type="file" name="main_image" accept="image/jpeg,image/png,image/webp"
           onchange="previewImage(this,'imgPreview')" class="form-control"
           style="padding:10px">
    <span class="form-hint">JPEG, PNG ou WEBP · Max 5 Mo</span>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">
      <i class="fa-solid fa-floppy-disk"></i>
      <?= $action === 'new' ? 'Créer le véhicule' : 'Enregistrer les modifications' ?>
    </button>
    <a href="/vehicles.php" class="btn btn-outline">Annuler</a>
  </div>
</div>

</form>
</div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

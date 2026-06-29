<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// ─── DELETE ───────────────────────────────
if ($action === 'delete' && $id && isAdmin()) {
    $db->prepare("DELETE FROM rentals WHERE id=?")->execute([$id]);
    flash('success', 'Location supprimée.');
    header('Location: /rentals.php'); exit;
}

// ─── SAVE ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'vehicle_id'  => (int)($_POST['vehicle_id'] ?? 0),
        'client_id'   => (int)($_POST['client_id'] ?? 0),
        'agent_id'    => $_SESSION['user_id'],
        'start_date'  => $_POST['start_date'] ?? date('Y-m-d'),
        'end_date'    => $_POST['end_date'] ?? date('Y-m-d', strtotime('+7 days')),
        'daily_rate'  => (float)($_POST['daily_rate'] ?? 0),
        'total_amount'=> (float)($_POST['total_amount'] ?? 0),
        'deposit'     => (float)($_POST['deposit'] ?? 0),
        'status'      => $_POST['status'] ?? 'en_cours',
        'notes'       => trim($_POST['notes'] ?? ''),
    ];

    if ($id) {
        $sql = "UPDATE rentals SET vehicle_id=?, client_id=?, agent_id=?, start_date=?,
                end_date=?, daily_rate=?, total_amount=?, deposit=?, status=?, notes=?
                WHERE id=?";
        $db->prepare($sql)->execute([$data['vehicle_id'], $data['client_id'], $data['agent_id'],
            $data['start_date'], $data['end_date'], $data['daily_rate'], $data['total_amount'],
            $data['deposit'], $data['status'], $data['notes'], $id]);
        flash('success', 'Location mise à jour.');
    } else {
        $ref = generateRef('LOC');
        $sql = "INSERT INTO rentals (reference, vehicle_id, client_id, agent_id, start_date,
                end_date, daily_rate, total_amount, deposit, status, notes)
                VALUES(?,?,?,?,?,?,?,?,?,?,?)";
        $db->prepare($sql)->execute([$ref, $data['vehicle_id'], $data['client_id'], $data['agent_id'],
            $data['start_date'], $data['end_date'], $data['daily_rate'], $data['total_amount'],
            $data['deposit'], $data['status'], $data['notes']]);
        // Update vehicle status
        $db->prepare("UPDATE vehicles SET status='loue' WHERE id=?")->execute([$data['vehicle_id']]);
        flash('success', 'Location enregistrée avec succès.');
    }
    header('Location: /rentals.php'); exit;
}

// ─── LOAD ONE ─────────────────────────────
$rental = null;
if ($id && in_array($action, ['edit','view','return'])) {
    $st = $db->prepare("SELECT r.*, v.brand, v.model, v.reference as vehicle_ref, c.full_name as client_name,
                         u.full_name as agent_name, v.rental_price_day as default_rate
                         FROM rentals r 
                         JOIN vehicles v ON r.vehicle_id=v.id 
                         JOIN clients c ON r.client_id=c.id 
                         LEFT JOIN users u ON r.agent_id=u.id
                         WHERE r.id=?");
    $st->execute([$id]);
    $rental = $st->fetch();
    if (!$rental) { flash('error','Location introuvable.'); header('Location: /rentals.php'); exit; }
}

// ─── RETURN ───────────────────────────────
if ($action === 'return' && $rental) {
    $db->prepare("UPDATE rentals SET return_date=NOW(), status='termine' WHERE id=?")->execute([$id]);
    $db->prepare("UPDATE vehicles SET status='disponible' WHERE id=?")->execute([$rental['vehicle_id']]);
    flash('success', 'Véhicule retourné avec succès.');
    header('Location: /rentals.php'); exit;
}

// ─── LIST ─────────────────────────────────
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 15;

$where = '1=1';
$params = [];
if ($search) {
    $where .= ' AND (r.reference LIKE ? OR v.brand LIKE ? OR v.model LIKE ? OR c.full_name LIKE ?)';
    $s = "%$search%";
    array_push($params, $s, $s, $s, $s);
}

$total = $db->prepare("SELECT COUNT(*) FROM rentals r JOIN vehicles v ON r.vehicle_id=v.id JOIN clients c ON r.client_id=c.id WHERE $where");
$total->execute($params);
$totalRows = $total->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$offset = ($page - 1) * $perPage;

$rentals = $db->prepare("SELECT r.*, v.brand, v.model, v.reference as vehicle_ref, c.full_name as client_name, u.full_name as agent_name
                         FROM rentals r 
                         JOIN vehicles v ON r.vehicle_id=v.id 
                         JOIN clients c ON r.client_id=c.id 
                         LEFT JOIN users u ON r.agent_id=u.id
                         WHERE $where ORDER BY r.created_at DESC LIMIT $perPage OFFSET $offset");
$rentals->execute($params);
$rentals = $rentals->fetchAll();

$vehicles = $db->query("SELECT id, brand, model, reference, rental_price_day, status FROM vehicles WHERE status='disponible' ORDER BY brand")->fetchAll();
$clients = $db->query("SELECT id, full_name, email, phone FROM clients ORDER BY full_name")->fetchAll();
$preVid = (int)($_GET['vid'] ?? 0);

$pageTitle = $action === 'new' ? 'Nouvelle location' : ($action === 'edit' ? 'Modifier location' : 'Locations');
$currentPage = 'rentals';
require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($action === 'list'): ?>
<!-- LISTE -->
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title">Locations</div>
    <div class="page-subtitle"><?= $totalRows ?> location<?= $totalRows > 1 ? 's' : '' ?></div>
  </div>
  <a href="/rentals.php?action=new" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nouvelle location</a>
</div>

<!-- FILTRES -->
<form method="get" class="filter-bar">
  <div class="search-input-wrap">
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="text" name="q" placeholder="Rechercher référence, véhicule, client…" value="<?= sanitize($search) ?>">
  </div>
  <button type="submit" class="btn btn-dark btn-sm"><i class="fa-solid fa-search"></i> Rechercher</button>
  <?php if ($search): ?>
  <a href="/rentals.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i></a>
  <?php endif; ?>
</form>

<!-- TABLE -->
<div class="card">
  <?php if ($rentals): ?>
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr>
          <th>Réf.</th>
          <th>Véhicule</th>
          <th>Client</th>
          <th>Période</th>
          <th>Montant</th>
          <th>Statut</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rentals as $r): ?>
        <tr>
          <td><span class="table-ref"><?= sanitize($r['reference']) ?></span></td>
          <td>
            <div class="table-name"><?= sanitize($r['brand']) ?> <?= sanitize($r['model']) ?></div>
            <div class="table-sub"><?= sanitize($r['vehicle_ref']) ?></div>
          </td>
          <td><?= sanitize($r['client_name']) ?></td>
          <td>
            <div style="font-size:13px"><?= date('d/m/Y', strtotime($r['start_date'])) ?></div>
            <div class="table-sub">→ <?= date('d/m/Y', strtotime($r['end_date'])) ?></div>
            <?php if ($r['return_date']): ?>
            <div class="table-sub" style="color:var(--success)">Retour: <?= date('d/m/Y', strtotime($r['return_date'])) ?></div>
            <?php endif; ?>
          </td>
          <td style="font-weight:600"><?= formatPrice((float)$r['total_amount']) ?></td>
          <td><?= statusBadge($r['status']) ?></td>
          <td>
            <div class="actions-cell">
              <?php if ($r['status'] === 'en_cours'): ?>
              <a href="/rentals.php?action=return&id=<?= $r['id'] ?>" 
                 class="btn btn-success btn-sm" 
                 onclick="return confirm('Confirmer le retour de ce véhicule ?')">
                <i class="fa-solid fa-undo"></i> Retour
              </a>
              <?php endif; ?>
              <?php if (isAdmin()): ?>
              <form method="post" action="/rentals.php?action=delete&id=<?= $r['id'] ?>" style="display:inline">
                <button type="submit" class="btn btn-outline btn-sm btn-icon" title="Supprimer"
                        onclick="return confirm('Supprimer cette location ?')">
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

  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
    <a href="?p=<?= $p ?>&q=<?= urlencode($search) ?>" class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <div class="empty-state">
    <div class="empty-state-icon"><i class="fa-solid fa-key"></i></div>
    <div class="empty-state-title">Aucune location</div>
    <div class="empty-state-desc">Enregistrez votre première location.</div>
    <a href="/rentals.php?action=new" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nouvelle location</a>
  </div>
  <?php endif; ?>
</div>

<?php elseif (in_array($action, ['new','edit'])): ?>
<!-- FORMULAIRE -->
<div class="page-header">
  <div>
    <a href="/rentals.php" style="color:var(--gray-400);text-decoration:none;font-size:13px">
      <i class="fa-solid fa-arrow-left"></i> Retour
    </a>
    <div class="page-title" style="margin-top:6px"><?= $action==='new' ? 'Nouvelle location' : 'Modifier la location' ?></div>
  </div>
</div>

<div class="card">
<div class="card-body">
<form method="post" id="rentalForm">

<div class="form-grid">
  <div class="form-group">
    <label class="form-label">Véhicule <span class="req">*</span></label>
    <select name="vehicle_id" class="form-select" required id="vehicleSelect">
      <option value="">— Choisir —</option>
      <?php foreach ($vehicles as $v): ?>
      <option value="<?= $v['id'] ?>" data-rate="<?= $v['rental_price_day'] ?>" 
              <?= ($rental['vehicle_id'] ?? $preVid) == $v['id'] ? 'selected' : '' ?>>
        <?= sanitize($v['brand'].' '.$v['model'].' ('.$v['reference'].')') ?>
        <?= $v['rental_price_day'] ? ' - '.formatPrice((float)$v['rental_price_day']).'/jour' : '' ?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-group">
    <label class="form-label">Client <span class="req">*</span></label>
    <select name="client_id" class="form-select" required>
      <option value="">— Choisir —</option>
      <?php foreach ($clients as $c): ?>
      <option value="<?= $c['id'] ?>" <?= ($rental['client_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
        <?= sanitize($c['full_name']) ?> (<?= sanitize($c['email'] ?? $c['phone'] ?? '') ?>)
      </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-group">
    <label class="form-label">Date de début <span class="req">*</span></label>
    <input type="date" name="start_date" class="form-control" required
           value="<?= $rental['start_date'] ?? date('Y-m-d') ?>" id="start_date"
           onchange="calcTotal()">
  </div>

  <div class="form-group">
    <label class="form-label">Date de fin <span class="req">*</span></label>
    <input type="date" name="end_date" class="form-control" required
           value="<?= $rental['end_date'] ?? date('Y-m-d', strtotime('+7 days')) ?>" id="end_date"
           onchange="calcTotal()">
  </div>

  <div class="form-group">
    <label class="form-label">Tarif journalier (FCFA) <span class="req">*</span></label>
    <input type="number" name="daily_rate" class="form-control" min="0" step="500" required
           value="<?= $rental['daily_rate'] ?? '' ?>" id="daily_rate"
           onchange="calcTotal()" oninput="calcTotal()"
           placeholder="Ex: 45000">
  </div>

  <div class="form-group" style="background:var(--gold-pale);padding:12px;border-radius:10px">
    <label class="form-label" style="font-weight:600">Montant total (FCFA)</label>
    <input type="number" name="total_amount" class="form-control" style="background:var(--white);font-weight:700;font-size:18px"
           value="<?= $rental['total_amount'] ?? '' ?>" id="total_amount" readonly>
    <span class="form-hint" id="total_display">Calculé automatiquement</span>
  </div>

  <div class="form-group">
    <label class="form-label">Caution (FCFA)</label>
    <input type="number" name="deposit" class="form-control" min="0" step="1000"
           value="<?= $rental['deposit'] ?? 0 ?>" placeholder="Ex: 50000">
  </div>

  <div class="form-group">
    <label class="form-label">Statut</label>
    <select name="status" class="form-select">
      <option value="en_cours" <?= ($rental['status'] ?? '') === 'en_cours' ? 'selected' : '' ?>>En cours</option>
      <option value="termine" <?= ($rental['status'] ?? '') === 'termine' ? 'selected' : '' ?>>Terminé</option>
      <option value="annule" <?= ($rental['status'] ?? '') === 'annule' ? 'selected' : '' ?>>Annulé</option>
    </select>
  </div>

  <div class="form-group col-span-full">
    <label class="form-label">Notes</label>
    <textarea name="notes" class="form-control" rows="3" placeholder="Informations complémentaires…"><?= sanitize($rental['notes'] ?? '') ?></textarea>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">
      <i class="fa-solid fa-floppy-disk"></i>
      <?= $action === 'new' ? 'Enregistrer la location' : 'Mettre à jour' ?>
    </button>
    <a href="/rentals.php" class="btn btn-outline">Annuler</a>
  </div>
</div>

<script>
function calcTotal() {
  const start = document.getElementById('start_date');
  const end = document.getElementById('end_date');
  const rate = document.getElementById('daily_rate');
  const total = document.getElementById('total_amount');
  const display = document.getElementById('total_display');
  
  if (!start || !end || !rate || !total) return;

  const d1 = new Date(start.value);
  const d2 = new Date(end.value);
  const diffTime = d2.getTime() - d1.getTime();
  const days = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  
  if (days > 0 && rate.value > 0) {
    const amount = days * parseFloat(rate.value);
    total.value = amount;
    display.textContent = amount.toLocaleString('fr-FR') + ' FCFA (' + days + ' jour' + (days > 1 ? 's' : '') + ')';
  } else {
    total.value = 0;
    display.textContent = '—';
  }
}

// Auto-fill rate when vehicle selected
document.getElementById('vehicleSelect')?.addEventListener('change', function() {
  const selected = this.options[this.selectedIndex];
  const rate = selected.dataset.rate;
  if (rate) {
    document.getElementById('daily_rate').value = rate;
    calcTotal();
  }
});

// Initial calculation
window.onload = function() {
  calcTotal();
};
</script>

</form>
</div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

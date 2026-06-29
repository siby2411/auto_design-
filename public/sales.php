<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// ─── DELETE ───────────────────────────────
if ($action === 'delete' && $id && isAdmin()) {
    $db->prepare("DELETE FROM sales WHERE id=?")->execute([$id]);
    flash('success', 'Vente supprimée.');
    header('Location: /sales.php'); exit;
}

// ─── SAVE ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'vehicle_id'      => (int)($_POST['vehicle_id'] ?? 0),
        'client_id'       => (int)($_POST['client_id'] ?? 0),
        'agent_id'        => $_SESSION['user_id'],
        'sale_date'       => $_POST['sale_date'] ?? date('Y-m-d'),
        'sale_price'      => (float)($_POST['sale_price'] ?? 0),
        'discount'        => (float)($_POST['discount'] ?? 0),
        'final_price'     => (float)($_POST['final_price'] ?? 0),
        'payment_method'  => $_POST['payment_method'] ?? 'especes',
        'status'          => $_POST['status'] ?? 'complete',
        'notes'           => trim($_POST['notes'] ?? ''),
    ];

    if ($data['final_price'] == 0) {
        $data['final_price'] = $data['sale_price'] - $data['discount'];
    }

    if ($id) {
        $sql = "UPDATE sales SET vehicle_id=?, client_id=?, agent_id=?, sale_date=?,
                sale_price=?, discount=?, final_price=?, payment_method=?, status=?, notes=?
                WHERE id=?";
        $db->prepare($sql)->execute([$data['vehicle_id'], $data['client_id'], $data['agent_id'],
            $data['sale_date'], $data['sale_price'], $data['discount'], $data['final_price'],
            $data['payment_method'], $data['status'], $data['notes'], $id]);
        flash('success', 'Vente mise à jour.');
    } else {
        $ref = generateRef('VEN');
        $sql = "INSERT INTO sales (reference, vehicle_id, client_id, agent_id, sale_date,
                sale_price, discount, final_price, payment_method, status, notes)
                VALUES(?,?,?,?,?,?,?,?,?,?,?)";
        $db->prepare($sql)->execute([$ref, $data['vehicle_id'], $data['client_id'], $data['agent_id'],
            $data['sale_date'], $data['sale_price'], $data['discount'], $data['final_price'],
            $data['payment_method'], $data['status'], $data['notes']]);
        // Update vehicle status
        $db->prepare("UPDATE vehicles SET status='vendu' WHERE id=?")->execute([$data['vehicle_id']]);
        flash('success', 'Vente enregistrée avec succès.');
    }
    header('Location: /sales.php'); exit;
}

// ─── LOAD ONE ─────────────────────────────
$sale = null;
if ($id && in_array($action, ['edit','view'])) {
    $st = $db->prepare("SELECT s.*, v.brand, v.model, v.reference as vehicle_ref, c.full_name as client_name 
                         FROM sales s 
                         JOIN vehicles v ON s.vehicle_id=v.id 
                         JOIN clients c ON s.client_id=c.id 
                         WHERE s.id=?");
    $st->execute([$id]);
    $sale = $st->fetch();
    if (!$sale) { flash('error','Vente introuvable.'); header('Location: /sales.php'); exit; }
}

// ─── LIST ─────────────────────────────────
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 15;

$where = '1=1';
$params = [];
if ($search) {
    $where .= ' AND (s.reference LIKE ? OR v.brand LIKE ? OR v.model LIKE ? OR c.full_name LIKE ?)';
    $s = "%$search%";
    array_push($params, $s, $s, $s, $s);
}

$total = $db->prepare("SELECT COUNT(*) FROM sales s JOIN vehicles v ON s.vehicle_id=v.id JOIN clients c ON s.client_id=c.id WHERE $where");
$total->execute($params);
$totalRows = $total->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$offset = ($page - 1) * $perPage;

$sales = $db->prepare("SELECT s.*, v.brand, v.model, v.reference as vehicle_ref, c.full_name as client_name, u.full_name as agent_name
                        FROM sales s 
                        JOIN vehicles v ON s.vehicle_id=v.id 
                        JOIN clients c ON s.client_id=c.id 
                        LEFT JOIN users u ON s.agent_id=u.id
                        WHERE $where ORDER BY s.created_at DESC LIMIT $perPage OFFSET $offset");
$sales->execute($params);
$sales = $sales->fetchAll();

$vehicles = $db->query("SELECT id, brand, model, reference, sale_price, status FROM vehicles WHERE status='disponible' OR status='en_cours' ORDER BY brand")->fetchAll();
$clients = $db->query("SELECT id, full_name, email, phone FROM clients ORDER BY full_name")->fetchAll();
$preVid = (int)($_GET['vid'] ?? 0);

$pageTitle = $action === 'new' ? 'Nouvelle vente' : ($action === 'edit' ? 'Modifier vente' : ($action === 'view' ? 'Détail vente' : 'Ventes'));
$currentPage = 'sales';
require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($action === 'list'): ?>
<!-- LISTE -->
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title">Ventes</div>
    <div class="page-subtitle"><?= $totalRows ?> vente<?= $totalRows > 1 ? 's' : '' ?></div>
  </div>
  <a href="/sales.php?action=new" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nouvelle vente</a>
</div>

<!-- FILTRES -->
<form method="get" class="filter-bar">
  <div class="search-input-wrap">
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="text" name="q" placeholder="Rechercher référence, véhicule, client…" value="<?= sanitize($search) ?>">
  </div>
  <button type="submit" class="btn btn-dark btn-sm"><i class="fa-solid fa-search"></i> Rechercher</button>
  <?php if ($search): ?>
  <a href="/sales.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i></a>
  <?php endif; ?>
</form>

<!-- TABLE -->
<div class="card">
  <?php if ($sales): ?>
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr>
          <th>Réf.</th>
          <th>Véhicule</th>
          <th>Client</th>
          <th>Agent</th>
          <th>Date</th>
          <th>Montant</th>
          <th>Statut</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sales as $s): ?>
        <tr>
          <td><span class="table-ref"><?= sanitize($s['reference']) ?></span></td>
          <td>
            <div class="table-name"><?= sanitize($s['brand']) ?> <?= sanitize($s['model']) ?></div>
            <div class="table-sub"><?= sanitize($s['vehicle_ref']) ?></div>
          </td>
          <td><?= sanitize($s['client_name']) ?></td>
          <td><?= sanitize($s['agent_name'] ?? '—') ?></td>
          <td><?= date('d/m/Y', strtotime($s['sale_date'])) ?></td>
          <td style="font-weight:600"><?= formatPrice((float)$s['final_price']) ?></td>
          <td><?= statusBadge($s['status']) ?></td>
          <td>
            <div class="actions-cell">
              <a href="/sales.php?action=view&id=<?= $s['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Voir"><i class="fa-solid fa-eye"></i></a>
              <?php if (isAdmin()): ?>
              <form method="post" action="/sales.php?action=delete&id=<?= $s['id'] ?>" style="display:inline">
                <button type="submit" class="btn btn-outline btn-sm btn-icon" title="Supprimer"
                        onclick="return confirm('Supprimer cette vente ?')">
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
    <div class="empty-state-icon"><i class="fa-solid fa-handshake"></i></div>
    <div class="empty-state-title">Aucune vente</div>
    <div class="empty-state-desc">Enregistrez votre première vente.</div>
    <a href="/sales.php?action=new" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nouvelle vente</a>
  </div>
  <?php endif; ?>
</div>

<?php elseif (in_array($action, ['new','edit'])): ?>
<!-- FORMULAIRE -->
<div class="page-header">
  <div>
    <a href="/sales.php" style="color:var(--gray-400);text-decoration:none;font-size:13px">
      <i class="fa-solid fa-arrow-left"></i> Retour
    </a>
    <div class="page-title" style="margin-top:6px"><?= $action==='new' ? 'Nouvelle vente' : 'Modifier la vente' ?></div>
  </div>
</div>

<div class="card">
<div class="card-body">
<form method="post">

<div class="form-grid">
  <div class="form-group">
    <label class="form-label">Véhicule <span class="req">*</span></label>
    <select name="vehicle_id" class="form-select" required>
      <option value="">— Choisir —</option>
      <?php foreach ($vehicles as $v): ?>
      <option value="<?= $v['id'] ?>" <?= ($sale['vehicle_id'] ?? $preVid) == $v['id'] ? 'selected' : '' ?>>
        <?= sanitize($v['brand'].' '.$v['model'].' ('.$v['reference'].')') ?>
        <?= $v['sale_price'] ? ' - '.formatPrice((float)$v['sale_price']) : '' ?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-group">
    <label class="form-label">Client <span class="req">*</span></label>
    <select name="client_id" class="form-select" required>
      <option value="">— Choisir —</option>
      <?php foreach ($clients as $c): ?>
      <option value="<?= $c['id'] ?>" <?= ($sale['client_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
        <?= sanitize($c['full_name']) ?> (<?= sanitize($c['email'] ?? $c['phone'] ?? '') ?>)
      </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-group">
    <label class="form-label">Date de vente</label>
    <input type="date" name="sale_date" class="form-control" 
           value="<?= $sale['sale_date'] ?? date('Y-m-d') ?>">
  </div>

  <div class="form-group">
    <label class="form-label">Prix de vente (FCFA) <span class="req">*</span></label>
    <input type="number" name="sale_price" class="form-control" min="0" step="1000" required
           value="<?= $sale['sale_price'] ?? '' ?>" placeholder="Ex: 25000000" id="sale_price"
           onchange="updateFinal()" oninput="updateFinal()">
  </div>

  <div class="form-group">
    <label class="form-label">Remise (FCFA)</label>
    <input type="number" name="discount" class="form-control" min="0" step="1000"
           value="<?= $sale['discount'] ?? 0 ?>" id="discount"
           onchange="updateFinal()" oninput="updateFinal()">
  </div>

  <div class="form-group" style="background:var(--gold-pale);padding:12px;border-radius:10px">
    <label class="form-label" style="font-weight:600">Prix final (FCFA)</label>
    <input type="number" name="final_price" class="form-control" style="background:var(--white);font-weight:700;font-size:18px"
           value="<?= $sale['final_price'] ?? '' ?>" id="final_price" readonly>
    <span class="form-hint">Calculé automatiquement</span>
  </div>

  <div class="form-group">
    <label class="form-label">Mode de paiement</label>
    <select name="payment_method" class="form-select">
      <option value="especes" <?= ($sale['payment_method'] ?? '') === 'especes' ? 'selected' : '' ?>>Espèces</option>
      <option value="virement" <?= ($sale['payment_method'] ?? '') === 'virement' ? 'selected' : '' ?>>Virement</option>
      <option value="cheque" <?= ($sale['payment_method'] ?? '') === 'cheque' ? 'selected' : '' ?>>Chèque</option>
      <option value="credit" <?= ($sale['payment_method'] ?? '') === 'credit' ? 'selected' : '' ?>>Crédit</option>
    </select>
  </div>

  <div class="form-group">
    <label class="form-label">Statut</label>
    <select name="status" class="form-select">
      <option value="complete" <?= ($sale['status'] ?? '') === 'complete' ? 'selected' : '' ?>>Complète</option>
      <option value="en_cours" <?= ($sale['status'] ?? '') === 'en_cours' ? 'selected' : '' ?>>En cours</option>
      <option value="annule" <?= ($sale['status'] ?? '') === 'annule' ? 'selected' : '' ?>>Annulée</option>
    </select>
  </div>

  <div class="form-group col-span-full">
    <label class="form-label">Notes</label>
    <textarea name="notes" class="form-control" rows="3" placeholder="Informations complémentaires…"><?= sanitize($sale['notes'] ?? '') ?></textarea>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">
      <i class="fa-solid fa-floppy-disk"></i>
      <?= $action === 'new' ? 'Enregistrer la vente' : 'Mettre à jour' ?>
    </button>
    <a href="/sales.php" class="btn btn-outline">Annuler</a>
  </div>
</div>

<script>
function updateFinal() {
  const price = parseFloat(document.getElementById('sale_price').value) || 0;
  const discount = parseFloat(document.getElementById('discount').value) || 0;
  document.getElementById('final_price').value = Math.max(0, price - discount);
}
</script>

</form>
</div>
</div>

<?php elseif ($action === 'view' && $sale): ?>
<!-- DÉTAIL -->
<div class="page-header">
  <div>
    <a href="/sales.php" style="color:var(--gray-400);text-decoration:none;font-size:13px">
      <i class="fa-solid fa-arrow-left"></i> Retour
    </a>
    <div class="page-title" style="margin-top:6px">Vente #<?= sanitize($sale['reference']) ?></div>
  </div>
  <div class="d-flex gap-8">
    <a href="#" class="btn btn-outline" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimer</a>
    <?php if (isAdmin()): ?>
    <a href="/sales.php?action=edit&id=<?= $sale['id'] ?>" class="btn btn-outline"><i class="fa-solid fa-pen"></i> Modifier</a>
    <?php endif; ?>
  </div>
</div>

<div class="invoice-page">
  <div class="invoice-header">
    <div>
      <div class="invoice-brand-name"><span>OMEGA</span> TECH AUTO</div>
      <div class="invoice-brand-sub">Gestion automobile · Vente</div>
    </div>
    <div style="text-align:right">
      <div class="invoice-number-label">Facture n°</div>
      <div class="invoice-number"><?= sanitize($sale['reference']) ?></div>
      <div style="font-size:12px;color:var(--gray-400);margin-top:4px">Date: <?= date('d/m/Y', strtotime($sale['sale_date'])) ?></div>
    </div>
  </div>

  <div class="invoice-body">
    <div class="invoice-parties">
      <div>
        <div class="invoice-party-label">Client</div>
        <div class="invoice-party-name"><?= sanitize($sale['client_name']) ?></div>
      </div>
      <div>
        <div class="invoice-party-label">Véhicule</div>
        <div class="invoice-party-name"><?= sanitize($sale['brand']) ?> <?= sanitize($sale['model']) ?></div>
        <div class="invoice-party-info">Réf: <?= sanitize($sale['vehicle_ref']) ?></div>
      </div>
    </div>

    <table class="invoice-table">
      <thead>
        <tr>
          <th>Description</th>
          <th style="text-align:right">Montant</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><?= sanitize($sale['brand']) ?> <?= sanitize($sale['model']) ?> · Vente</td>
          <td style="text-align:right"><?= formatPrice((float)$sale['sale_price']) ?></td>
        </tr>
        <?php if ($sale['discount'] > 0): ?>
        <tr>
          <td>Remise</td>
          <td style="text-align:right">- <?= formatPrice((float)$sale['discount']) ?></td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="invoice-totals">
      <div class="invoice-total-row grand">
        <span>Total TTC</span>
        <span><?= formatPrice((float)$sale['final_price']) ?></span>
      </div>
      <div style="font-size:12px;color:var(--gray-400);margin-top:8px;text-align:right">
        Paiement: <?= ucfirst($sale['payment_method']) ?> · Statut: <?= ucfirst($sale['status']) ?>
      </div>
    </div>

    <?php if ($sale['notes']): ?>
    <div style="margin-top:24px;padding-top:16px;border-top:1px solid var(--gray-100)">
      <div style="font-size:12px;color:var(--gray-400)">Notes</div>
      <div style="font-size:13px;margin-top:4px"><?= sanitize($sale['notes']) ?></div>
    </div>
    <?php endif; ?>
  </div>

  <div class="invoice-footer-bar">
    <span>Omega Tech Auto · Gestion automobile</span>
    <span>Merci pour votre confiance</span>
  </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

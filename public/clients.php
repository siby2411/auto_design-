<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// ─── DELETE ───────────────────────────────
if ($action === 'delete' && $id && isAdmin()) {
    $db->prepare("DELETE FROM clients WHERE id=?")->execute([$id]);
    flash('success', 'Client supprimé.');
    header('Location: /clients.php'); exit;
}

// ─── SAVE ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'email'     => trim($_POST['email'] ?? ''),
        'phone'     => trim($_POST['phone'] ?? ''),
        'address'   => trim($_POST['address'] ?? ''),
        'id_number' => trim($_POST['id_number'] ?? ''),
    ];

    if ($id) {
        $sql = "UPDATE clients SET full_name=?, email=?, phone=?, address=?, id_number=? WHERE id=?";
        $db->prepare($sql)->execute([$data['full_name'], $data['email'], $data['phone'], 
            $data['address'], $data['id_number'], $id]);
        flash('success', 'Client mis à jour.');
    } else {
        $sql = "INSERT INTO clients (full_name, email, phone, address, id_number) VALUES(?,?,?,?,?)";
        $db->prepare($sql)->execute([$data['full_name'], $data['email'], $data['phone'], 
            $data['address'], $data['id_number']]);
        flash('success', 'Client créé avec succès.');
    }
    header('Location: /clients.php'); exit;
}

// ─── LOAD ONE ─────────────────────────────
$client = null;
if ($id && in_array($action, ['edit','view'])) {
    $st = $db->prepare("SELECT * FROM clients WHERE id=?");
    $st->execute([$id]);
    $client = $st->fetch();
    if (!$client) { flash('error','Client introuvable.'); header('Location: /clients.php'); exit; }
}

// ─── LIST ─────────────────────────────────
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 15;

$where = '1=1';
$params = [];
if ($search) {
    $where .= ' AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR id_number LIKE ?)';
    $s = "%$search%";
    array_push($params, $s, $s, $s, $s);
}

$total = $db->prepare("SELECT COUNT(*) FROM clients WHERE $where");
$total->execute($params);
$totalRows = $total->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$offset = ($page - 1) * $perPage;

$clients = $db->prepare("SELECT * FROM clients WHERE $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$clients->execute($params);
$clients = $clients->fetchAll();

$pageTitle = $action === 'new' ? 'Nouveau client' : ($action === 'edit' ? 'Modifier client' : 'Clients');
$currentPage = 'clients';
require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($action === 'list'): ?>
<!-- LISTE -->
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title">Clients</div>
    <div class="page-subtitle"><?= $totalRows ?> client<?= $totalRows > 1 ? 's' : '' ?></div>
  </div>
  <a href="/clients.php?action=new" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nouveau client</a>
</div>

<!-- FILTRES -->
<form method="get" class="filter-bar">
  <div class="search-input-wrap">
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="text" name="q" placeholder="Rechercher nom, email, téléphone…" value="<?= sanitize($search) ?>">
  </div>
  <button type="submit" class="btn btn-dark btn-sm"><i class="fa-solid fa-search"></i> Rechercher</button>
  <?php if ($search): ?>
  <a href="/clients.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i></a>
  <?php endif; ?>
</form>

<!-- TABLE -->
<div class="card">
  <?php if ($clients): ?>
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr>
          <th>Nom complet</th>
          <th>Email</th>
          <th>Téléphone</th>
          <th>Adresse</th>
          <th>Pièce d'identité</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($clients as $c): ?>
        <tr>
          <td><div class="table-name"><?= sanitize($c['full_name']) ?></div></td>
          <td><?= sanitize($c['email'] ?? '—') ?></td>
          <td><?= sanitize($c['phone'] ?? '—') ?></td>
          <td><?= sanitize($c['address'] ?? '—') ?></td>
          <td><?= sanitize($c['id_number'] ?? '—') ?></td>
          <td>
            <div class="actions-cell">
              <a href="/clients.php?action=edit&id=<?= $c['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Modifier"><i class="fa-solid fa-pen"></i></a>
              <?php if (isAdmin()): ?>
              <form method="post" action="/clients.php?action=delete&id=<?= $c['id'] ?>" style="display:inline">
                <button type="submit" class="btn btn-outline btn-sm btn-icon" title="Supprimer"
                        onclick="return confirm('Supprimer ce client ?')">
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
    <div class="empty-state-icon"><i class="fa-solid fa-users"></i></div>
    <div class="empty-state-title">Aucun client</div>
    <div class="empty-state-desc">Ajoutez vos premiers clients.</div>
    <a href="/clients.php?action=new" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nouveau client</a>
  </div>
  <?php endif; ?>
</div>

<?php elseif (in_array($action, ['new','edit'])): ?>
<!-- FORMULAIRE -->
<div class="page-header">
  <div>
    <a href="/clients.php" style="color:var(--gray-400);text-decoration:none;font-size:13px">
      <i class="fa-solid fa-arrow-left"></i> Retour
    </a>
    <div class="page-title" style="margin-top:6px"><?= $action==='new' ? 'Nouveau client' : 'Modifier: '.sanitize($client['full_name']) ?></div>
  </div>
</div>

<div class="card">
<div class="card-body">
<form method="post">

<div class="form-grid">
  <div class="form-group">
    <label class="form-label">Nom complet <span class="req">*</span></label>
    <input type="text" name="full_name" class="form-control" required
           value="<?= sanitize($client['full_name'] ?? '') ?>" placeholder="Ex: Amadou Ba">
  </div>

  <div class="form-group">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control"
           value="<?= sanitize($client['email'] ?? '') ?>" placeholder="client@email.com">
  </div>

  <div class="form-group">
    <label class="form-label">Téléphone</label>
    <input type="text" name="phone" class="form-control"
           value="<?= sanitize($client['phone'] ?? '') ?>" placeholder="+221 77 123 45 67">
  </div>

  <div class="form-group">
    <label class="form-label">Pièce d'identité</label>
    <input type="text" name="id_number" class="form-control"
           value="<?= sanitize($client['id_number'] ?? '') ?>" placeholder="CNI, Passeport…">
  </div>

  <div class="form-group col-span-full">
    <label class="form-label">Adresse</label>
    <textarea name="address" class="form-control" rows="2" placeholder="Adresse complète…"><?= sanitize($client['address'] ?? '') ?></textarea>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">
      <i class="fa-solid fa-floppy-disk"></i>
      <?= $action === 'new' ? 'Créer le client' : 'Enregistrer les modifications' ?>
    </button>
    <a href="/clients.php" class="btn btn-outline">Annuler</a>
  </div>
</div>

</form>
</div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

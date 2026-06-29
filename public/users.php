<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

// Vérifier que l'utilisateur est admin
if (!isAdmin()) {
    flash('error', 'Accès non autorisé.');
    header('Location: /index.php');
    exit;
}

$db = getDB();
$pageTitle = 'Utilisateurs';
$currentPage = 'users';

// Récupérer tous les utilisateurs
$users = $db->query("SELECT id, username, full_name, role, email, phone, created_at FROM users ORDER BY created_at DESC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <div class="page-title">Gestion des utilisateurs</div>
        <div class="page-subtitle"><?= count($users) ?> utilisateur<?= count($users) > 1 ? 's' : '' ?></div>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nom d'utilisateur</th>
                    <th>Nom complet</th>
                    <th>Rôle</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Créé le</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><span class="table-ref"><?= htmlspecialchars($u['username']) ?></span></td>
                    <td><?= htmlspecialchars($u['full_name'] ?? '—') ?></td>
                    <td>
                        <span class="badge <?= $u['role'] === 'admin' ? 'badge-gold' : 'badge-info' ?>">
                            <?= ucfirst($u['role']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                    <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

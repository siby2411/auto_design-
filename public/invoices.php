<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$db = getDB();
$pageTitle = 'Factures';
$currentPage = 'invoices';

// Récupérer les ventes
$sales = $db->query("SELECT s.*, v.brand, v.model, c.full_name as client_name 
                      FROM sales s 
                      JOIN vehicles v ON s.vehicle_id=v.id 
                      JOIN clients c ON s.client_id=c.id 
                      ORDER BY s.created_at DESC LIMIT 20")->fetchAll();

// Récupérer les locations
$rentals = $db->query("SELECT r.*, v.brand, v.model, c.full_name as client_name 
                        FROM rentals r 
                        JOIN vehicles v ON r.vehicle_id=v.id 
                        JOIN clients c ON r.client_id=c.id 
                        ORDER BY r.created_at DESC LIMIT 20")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <div class="page-title">Factures</div>
        <div class="page-subtitle">Historique des transactions</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
    <!-- Ventes -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa-solid fa-handshake text-gold"></i> Ventes</span>
            <span class="badge badge-dark"><?= count($sales) ?></span>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Réf.</th>
                        <th>Client</th>
                        <th>Véhicule</th>
                        <th>Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $s): ?>
                    <tr>
                        <td><span class="table-ref"><?= htmlspecialchars($s['reference']) ?></span></td>
                        <td><?= htmlspecialchars($s['client_name']) ?></td>
                        <td><?= htmlspecialchars($s['brand'] . ' ' . $s['model']) ?></td>
                        <td style="font-weight:600"><?= formatPrice((float)$s['final_price']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($sales)): ?>
                    <tr><td colspan="4" class="text-center text-muted">Aucune vente</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Locations -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa-solid fa-key text-gold"></i> Locations</span>
            <span class="badge badge-dark"><?= count($rentals) ?></span>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Réf.</th>
                        <th>Client</th>
                        <th>Véhicule</th>
                        <th>Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rentals as $r): ?>
                    <tr>
                        <td><span class="table-ref"><?= htmlspecialchars($r['reference']) ?></span></td>
                        <td><?= htmlspecialchars($r['client_name']) ?></td>
                        <td><?= htmlspecialchars($r['brand'] . ' ' . $r['model']) ?></td>
                        <td style="font-weight:600"><?= formatPrice((float)$r['total_amount']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rentals)): ?>
                    <tr><td colspan="4" class="text-center text-muted">Aucune location</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

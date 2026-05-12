<?php
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

$api_stats = $pdo->query("SELECT * FROM api_usage ORDER BY last_used DESC")->fetchAll();
?>

<header class="fade-in">
    <div class="welcome">
        <h1>System Settings</h1>
        <p>Manage API rotation and system configuration.</p>
    </div>
</header>

<div class="card fade-in">
    <h2 style="font-size: 1.25rem; margin-bottom: 1.5rem;">Gemini API Key Rotation</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>API Key (Masked)</th>
                    <th>Requests</th>
                    <th>Last Used</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($api_stats as $stat): ?>
                <tr>
                    <td><code><?php echo substr($stat['api_key'], 0, 8) . '...' . substr($stat['api_key'], -4); ?></code></td>
                    <td><?php echo $stat['request_count']; ?></td>
                    <td><?php echo $stat['last_used']; ?></td>
                    <td>
                        <span class="badge badge-success">Active</span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card fade-in" style="margin-top: 2rem;">
    <h2 style="font-size: 1.25rem; margin-bottom: 1.5rem;">System Information</h2>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <div>
            <p style="color: var(--text-muted); margin-bottom: 0.5rem;">Image Processing Library</p>
            <p><?php echo class_exists('Imagick') ? 'Imagick (High Performance)' : 'GD Library (Standard)'; ?></p>
        </div>
        <div>
            <p style="color: var(--text-muted); margin-bottom: 0.5rem;">Database Status</p>
            <p style="color: var(--success);"><i class="fas fa-check-circle"></i> Connected</p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

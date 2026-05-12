<?php
require_once 'includes/db.php';
include 'includes/header.php';

// Get some stats
// Get some stats
$total_records = $pdo->query("SELECT COUNT(*) FROM pawn_records")->fetchColumn();
$total_customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$pending_verifications = $pdo->query("SELECT COUNT(*) FROM pawn_records WHERE verification_status = 'pending'")->fetchColumn();
?>

<header class="fade-in">
    <div class="welcome">
        <h1>Dashboard Overview</h1>
        <p>Welcome back to Rupasinghe Trust Pawn Management System.</p>
    </div>
    <div class="actions">
        <a href="scan.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Scan
        </a>
    </div>
</header>

<div class="stats-grid fade-in">
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background: #eef2ff; color: #4f46e5;">
                <i class="fas fa-file-invoice"></i>
            </div>
            <span style="font-size: 0.75rem; font-weight: 600; color: #10b981;">+12% Total</span>
        </div>
        <div class="stat-info">
            <h3><?php echo number_format($total_records); ?></h3>
            <p>Total Records</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background: #fdf2f8; color: #db2777;">
                <i class="fas fa-users"></i>
            </div>
            <span style="font-size: 0.75rem; font-weight: 600; color: #6366f1;">Active Users</span>
        </div>
        <div class="stat-info">
            <h3><?php echo number_format($total_customers); ?></h3>
            <p>Total Customers</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background: #fffbeb; color: #d97706;">
                <i class="fas fa-clock"></i>
            </div>
            <span class="badge badge-warning" style="font-size: 0.65rem;">Action Needed</span>
        </div>
        <div class="stat-info">
            <h3><?php echo $pending_verifications; ?></h3>
            <p>Pending Review</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background: #ecfeff; color: #0891b2;">
                <i class="fas fa-microchip"></i>
            </div>
            <span style="font-size: 0.75rem; font-weight: 600; color: #10b981;">Online</span>
        </div>
        <div class="stat-info">
            <h3>99.8%</h3>
            <p>System Accuracy</p>
        </div>
    </div>
</div>

<div class="card fade-in" style="animation-delay: 0.2s;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
        <h2 style="font-size: 1.25rem;">Recent Scans</h2>
        
        <!-- Branch Filter -->
        <form method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
            <label for="branch" style="font-size: 0.875rem; color: var(--text-muted);">Filter by Branch:</label>
            <select name="branch" id="branch" onchange="this.form.submit()" style="padding: 0.5rem; border-radius: 8px; background: var(--bg-dark); color: var(--text-main); border: 1px solid var(--glass-border); outline: none;">
                <option value="">All Branches</option>
                <?php foreach (BRANCHES as $branch): ?>
                    <option value="<?php echo $branch; ?>" <?php echo isset($_GET['branch']) && $_GET['branch'] === $branch ? 'selected' : ''; ?>>
                        <?php echo $branch; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <a href="records.php" style="color: var(--primary); text-decoration: none; font-size: 0.875rem;">View All</a>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>IR / R No</th>
                    <th>Customer Name</th>
                    <th>Branch</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $branch_filter = isset($_GET['branch']) && $_GET['branch'] !== '' ? $_GET['branch'] : null;
                $sql = "
                    SELECT p.*, c.full_name 
                    FROM pawn_records p 
                    LEFT JOIN customers c ON p.customer_id = c.id 
                ";
                
                if ($branch_filter) {
                    $sql .= " WHERE p.branch_location = :branch ";
                }
                
                $sql .= " ORDER BY p.created_at DESC LIMIT 10";
                
                $stmt = $pdo->prepare($sql);
                if ($branch_filter) {
                    $stmt->execute(['branch' => $branch_filter]);
                } else {
                    $stmt->execute();
                }
                
                while ($row = $stmt->fetch()):
                ?>
                <tr>
                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                    <td><?php echo $row['ir_no'] ?: $row['r_no']; ?></td>
                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['branch_location']); ?></td>
                    <td>
                        <span class="badge badge-<?php 
                            echo $row['verification_status'] == 'verified' ? 'success' : ($row['verification_status'] == 'flagged' ? 'danger' : 'warning'); 
                        ?>">
                            <?php echo $row['verification_status']; ?>
                        </span>
                    </td>
                    <td>
                        <a href="view_record.php?id=<?php echo $row['id']; ?>" style="color: var(--text-muted);"><i class="fas fa-eye"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($total_records == 0): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 3rem;">No records found. Start by scanning a bill.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

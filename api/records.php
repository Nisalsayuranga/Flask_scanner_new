<?php
require_once 'includes/db.php';
include 'includes/header.php';

// Get filter values
$search = $_GET['search'] ?? '';
$branch = $_GET['branch'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build Query
$sql = "
    SELECT p.*, c.full_name, c.nic_number, c.phone_number as customer_phone
    FROM pawn_records p 
    LEFT JOIN customers c ON p.customer_id = c.id 
    WHERE 1=1
";
$params = [];

if ($search) {
    $sql .= " AND (p.ir_no LIKE ? OR p.r_no LIKE ? OR p.receipt_no LIKE ? OR c.full_name LIKE ? OR c.nic_number LIKE ? OR c.phone_number LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}
if ($branch) {
    $sql .= " AND p.branch_location = ?";
    $params[] = $branch;
}
if ($date_from) {
    $sql .= " AND DATE(p.created_at) >= ?";
    $params[] = $date_from;
}
if ($date_to) {
    $sql .= " AND DATE(p.created_at) <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();
?>

<header class="fade-in">
    <div class="welcome">
        <h1>Data Management System</h1>
        <p>Advanced search and manage all digitized pawn records.</p>
    </div>
    <div class="actions" style="display: flex; gap: 1rem;">
        <button id="bulkDeleteBtn" class="btn" style="background: #ef4444; color: white; display: none;" onclick="submitBulkDelete()">
            <i class="fas fa-trash-alt"></i> Delete Selected
        </button>
        <a href="export_csv.php?<?php echo http_build_query($_GET); ?>" class="btn" style="background: #10b981; color: white;">
            <i class="fas fa-file-excel"></i> Export to Excel
        </a>
        <button onclick="window.print()" class="btn" style="background: white; border: 1px solid var(--border); color: var(--text-main);">
            <i class="fas fa-print"></i> Print Report (PDF)
        </button>
    </div>
</header>

<!-- Advanced Search Filters -->
<div class="card fade-in" style="margin-bottom: 2rem;">
    <form method="GET" action="records.php" class="filter-grid">
        <div class="form-group">
            <label>General Search</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="IR, Receipt, Name, Phone, NIC...">
        </div>
        <div class="form-group">
            <label>Branch</label>
            <select name="branch" class="form-control">
                <option value="">All Branches</option>
                <?php foreach (BRANCHES as $b): ?>
                    <option value="<?php echo $b; ?>" <?php echo $branch === $b ? 'selected' : ''; ?>>
                        <?php echo $b; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>From Date</label>
            <input type="date" name="date_from" value="<?php echo $date_from; ?>">
        </div>
        <div class="form-group">
            <label>To Date</label>
            <input type="date" name="date_to" value="<?php echo $date_to; ?>">
        </div>
        <div class="form-group" style="display: flex; align-items: flex-end; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center;">
                <i class="fas fa-filter"></i> Apply Filters
            </button>
            <a href="records.php" class="btn" style="background: #f1f5f9; color: #475569; padding: 0.75rem;">
                <i class="fas fa-sync-alt"></i>
            </a>
        </div>
    </form>
</div>

<!-- Results Table -->
<div class="card fade-in">
    <form id="bulkDeleteForm" action="bulk_delete_records.php" method="POST">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;">
                        <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)">
                    </th>
                    <th>Date</th>
                    <th>Branch</th>
                    <th>IR / R No</th>
                    <th>Receipt No</th>
                    <th>Customer Name</th>
                    <th>NIC / Phone</th>
                    <th>Amount (Rs.)</th>
                    <th>Status</th>
                    <th style="text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $row): ?>
                <tr>
                    <td style="text-align: center;">
                        <input type="checkbox" name="ids[]" value="<?php echo $row['id']; ?>" class="record-checkbox" onclick="toggleBulkDeleteBtn()">
                    </td>
                    <td>
                        <div style="font-weight: 600;"><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></div>
                    </td>
                    <td><span class="badge" style="background: rgba(79, 70, 229, 0.1); color: var(--primary);"><?php echo htmlspecialchars($row['branch_location']); ?></span></td>
                    <td><strong><?php echo $row['ir_no'] ?: $row['r_no']; ?></strong></td>
                    <td><span style="color: var(--text-muted);"><?php echo $row['receipt_no']; ?></span></td>
                    <td><div style="font-weight: 600;"><?php echo htmlspecialchars($row['full_name']); ?></div></td>
                    <td>
                        <div><?php echo htmlspecialchars($row['nic_number']); ?></div>
                        <div style="font-size: 0.8rem; color: var(--success);"><?php echo htmlspecialchars($row['customer_phone']); ?></div>
                    </td>
                    <td><strong style="color: var(--primary);"><?php echo number_format($row['principal_amount'], 2); ?></strong></td>
                    <td>
                        <span class="badge badge-<?php echo $row['verification_status'] == 'verified' ? 'success' : 'warning'; ?>">
                            <?php echo $row['verification_status']; ?>
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.5rem; justify-content: center;">
                            <a href="view_record.php?id=<?php echo $row['id']; ?>" class="btn-icon" title="View"><i class="fas fa-eye" style="color: var(--primary);"></i></a>
                            <a href="delete_record.php?id=<?php echo $row['id']; ?>" class="btn-icon" title="Delete" onclick="return confirm('Delete this record?')"><i class="fas fa-trash-alt" style="color: var(--danger);"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    </form>
</div>

<script>
function toggleSelectAll(master) {
    const checkboxes = document.querySelectorAll('.record-checkbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
    toggleBulkDeleteBtn();
}

function toggleBulkDeleteBtn() {
    const checkboxes = document.querySelectorAll('.record-checkbox:checked');
    const btn = document.getElementById('bulkDeleteBtn');
    btn.style.display = checkboxes.length > 0 ? 'inline-flex' : 'none';
}

function submitBulkDelete() {
    if (confirm('Are you sure you want to delete the selected ' + document.querySelectorAll('.record-checkbox:checked').length + ' records? This cannot be undone.')) {
        document.getElementById('bulkDeleteForm').submit();
    }
}
</script>

<style>
    .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; align-items: end; }
    .form-control { background-color: var(--bg-card); border: 1px solid var(--border); padding: 0.6rem 1rem; border-radius: 10px; color: var(--text-main); outline: none; }
    .btn-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border); background: var(--bg-card); }
    
    /* Print Styles */
    @media print {
        .sidebar, .actions, .card:first-of-type, .btn-icon, header.fade-in, th:first-child, td:first-child { display: none !important; }
        .main-content { margin-left: 0 !important; padding: 0 !important; }
        .card { border: none; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid #e2e8f0; padding: 8px; }
    }
</style>

<!-- Print Only Header (Existing) -->
<div class="print-header" style="display: none;">
    <div style="text-align: center; margin-bottom: 20px;">
        <h1 style="font-size: 24px; color: #1e293b;">RUPASINGHE TRUST - PAWN MANAGEMENT SYSTEM</h1>
        <h2 style="font-size: 18px; color: #64748b;">Digitized Records Report</h2>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

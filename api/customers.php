<?php
require_once 'includes/db.php';
include 'includes/header.php';

$stmt = $pdo->query("
    SELECT c.*, COUNT(p.id) as total_pawnings 
    FROM customers c 
    LEFT JOIN pawn_records p ON p.customer_id = c.id 
    GROUP BY c.id 
    ORDER BY c.full_name ASC
");
?>

<header class="fade-in">
    <div class="welcome">
        <h1>Customer Directory</h1>
        <p>Manage and view historical records for all customers.</p>
    </div>
</header>

<div class="card fade-in">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>NIC</th>
                    <th>Phone</th>
                    <th>Total Pawnings</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $stmt->fetch()): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['nic_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                    <td><?php echo $row['total_pawnings']; ?></td>
                    <td>
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="records.php?search=<?php echo urlencode($row['nic_number']); ?>" class="btn" style="padding: 0.4rem; background: rgba(255,255,255,0.05); color: var(--accent);">History</a>
                            <a href="delete_customer.php?id=<?php echo $row['id']; ?>" class="btn" style="padding: 0.4rem; background: rgba(239, 68, 68, 0.1); color: var(--danger);" onclick="return confirm('Delete this customer? This will remove their personal profile, but their pawn records will be preserved as unlinked.')"><i class="fas fa-trash-alt"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<?php
require_once 'includes/db.php';
include 'includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) die("Record ID missing.");

$stmt = $pdo->prepare("
    SELECT p.*, c.full_name, c.nic_number, c.address, c.phone_number 
    FROM pawn_records p 
    LEFT JOIN customers c ON p.customer_id = c.id 
    WHERE p.id = ?
");
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) die("Record not found.");
?>

<header class="fade-in">
    <div class="welcome">
        <h1>Record Verification</h1>
        <p>Review and verify the OCR extracted data for IR No: <?php echo $record['ir_no']; ?></p>
    </div>
    <div class="actions">
        <button class="btn btn-primary" onclick="document.getElementById('verifyForm').submit()">
            <i class="fas fa-check"></i> Verify & Save
        </button>
    </div>
</header>

<div class="verification-container fade-in">
    <!-- LEFT SIDE: Image Viewer (Sticky) -->
    <div class="image-viewer-column">
        <div class="card viewer-card">
            <div class="viewer-header">
                <h3><i class="fas fa-image"></i> SCANNED BILL</h3>
                <div class="zoom-controls">
                    <button class="btn-icon" onclick="zoomImg(1.2)"><i class="fas fa-plus"></i></button>
                    <button class="btn-icon" onclick="zoomImg(0.8)"><i class="fas fa-minus"></i></button>
                    <button class="btn-icon" onclick="resetZoom()"><i class="fas fa-sync-alt"></i></button>
                </div>
            </div>
            <div class="image-scroll-area" id="imageContainer">
                <?php 
                if (!empty($record['file_path']) && strpos($record['file_path'], 'http') === 0): 
                    $file_url = $record['file_path'];
                    $is_pdf = (pathinfo(parse_url($file_url, PHP_URL_PATH), PATHINFO_EXTENSION) === 'pdf');
                    if ($is_pdf && strpos($file_url, 'cloudinary.com') !== false) {
                        $file_url = preg_replace('/\.pdf$/i', '.jpg', $file_url);
                        $is_pdf = false;
                    }
                    if ($is_pdf): ?>
                        <embed src="<?php echo $file_url; ?>" type="application/pdf" width="100%" height="750px" />
                    <?php else: ?>
                        <img src="<?php echo $file_url; ?>" id="billImage" class="verify-img">
                    <?php endif; ?>
                <?php else: 
                    $receipt_path = "uploads/splits/" . $record['receipt_bill_image'];
                    if (!file_exists($receipt_path)) $receipt_path = "uploads/" . $record['receipt_bill_image'];
                    ?>
                    <img src="<?php echo $receipt_path; ?>" id="billImage" class="verify-img">
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- RIGHT SIDE: Data Entry Form -->
    <div class="form-column">
        <form id="verifyForm" action="update_record.php" method="POST" class="card form-card">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            
            <div class="form-section">
                <div class="section-header">
                    <i class="fas fa-user-circle"></i>
                    <h3>Customer Information</h3>
                </div>
                <div class="form-grid">
                    <div class="form-group span-2">
                        <label>Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($record['full_name']); ?>" class="form-control" placeholder="Customer Name">
                    </div>
                    <div class="form-group">
                        <label>NIC Number</label>
                        <input type="text" name="nic_number" value="<?php echo htmlspecialchars($record['nic_number']); ?>" class="form-control" placeholder="123456789V">
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone_number" value="<?php echo htmlspecialchars($record['phone_number']); ?>" class="form-control" placeholder="07XXXXXXXX">
                    </div>
                    <div class="form-group span-2">
                        <label>Address</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($record['address']); ?>" class="form-control" placeholder="Residential Address">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-header">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <h3>Pawn Details</h3>
                </div>
                <div class="form-grid">
                    <div class="form-group span-2">
                        <label>Branch Location</label>
                        <select name="branch_location" class="form-control">
                            <?php foreach (BRANCHES as $branch): ?>
                                <option value="<?php echo $branch; ?>" <?php echo ($record['branch_location'] == $branch) ? 'selected' : ''; ?>>
                                    <?php echo $branch; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>IR Number</label>
                        <input type="text" name="ir_no" value="<?php echo htmlspecialchars($record['ir_no']); ?>" class="form-control highlight">
                    </div>
                    <div class="form-group">
                        <label>R Number</label>
                        <input type="text" name="r_no" value="<?php echo htmlspecialchars($record['r_no']); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Receipt No</label>
                        <input type="text" name="receipt_no" value="<?php echo htmlspecialchars($record['receipt_no']); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Article Description</label>
                        <input type="text" name="article_description" value="<?php echo htmlspecialchars($record['article_description']); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Weight (g)</label>
                        <input type="number" step="0.001" name="weight_g" value="<?php echo $record['weight_g']; ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Weight (mg)</label>
                        <input type="number" step="0.001" name="weight_mg" value="<?php echo $record['weight_mg']; ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Principal Amount (Rs.)</label>
                        <input type="number" step="0.01" name="principal_amount" value="<?php echo $record['principal_amount']; ?>" class="form-control highlight-money">
                    </div>
                    <div class="form-group">
                        <label>Agreed Amount (Rs.)</label>
                        <input type="number" step="0.01" name="agreed_amount" value="<?php echo $record['agreed_amount']; ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Total Collected (Rs.)</label>
                        <input type="number" step="0.01" name="total_amount_collected" value="<?php echo $record['total_amount_collected']; ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Issue Date</label>
                        <input type="date" name="issue_date" value="<?php echo $record['issue_date']; ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Last Date</label>
                        <input type="date" name="last_date" value="<?php echo $record['last_date']; ?>" class="form-control">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="window.history.back()">Cancel</button>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-check-double"></i> Complete Verification
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    :root {
        --bg-verification: #f8fafc;
        --card-bg: #ffffff;
        --text-primary: #0f172a; /* Deep navy for maximum readability */
        --text-secondary: #475569;
        --input-border: #cbd5e1;
        --input-focus: #3b82f6;
        --section-bg: #f1f5f9;
    }

    body { background-color: var(--bg-verification); }

    .verification-container {
        display: flex;
        gap: 1.5rem;
        align-items: flex-start;
        padding: 1rem;
        width: 100%;
        overflow: hidden;
    }

    .image-viewer-column {
        flex: 1;
        flex-shrink: 0;
        position: sticky;
        top: 20px;
        height: calc(100vh - 100px);
        min-width: 50%;
    }

    .form-column {
        flex: 1;
        flex-shrink: 0;
        height: calc(100vh - 100px);
        overflow-y: auto;
        padding-right: 15px;
        min-width: 45%;
    }

    /* Custom Scrollbar for form column */
    .form-column::-webkit-scrollbar { width: 6px; }
    .form-column::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

    .viewer-card {
        height: 100%;
        display: flex;
        flex-direction: column;
        padding: 0 !important;
        overflow: hidden;
        border: 1px solid var(--input-border);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        background: #000;
    }

    .viewer-header {
        padding: 0.75rem 1.25rem;
        background: #1e293b;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .viewer-header h3 { font-size: 0.9rem; letter-spacing: 1px; margin: 0; }

    .zoom-controls {
        display: flex;
        gap: 0.5rem;
    }

    .btn-icon {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .btn-icon:hover {
        background: var(--input-focus);
        border-color: var(--input-focus);
    }

    .image-scroll-area {
        flex: 1;
        overflow: auto;
        background: #334155;
        padding: 2rem;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        cursor: grab;
    }

    .verify-img {
        max-width: 100%;
        height: auto;
        box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        transform-origin: top center;
        border-radius: 4px;
    }

    .form-card {
        padding: 2.5rem !important;
        background: var(--card-bg) !important;
        border: 1px solid var(--input-border) !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
        border-radius: 16px !important;
    }

    .form-section {
        margin-bottom: 3rem;
        padding: 1.5rem;
        background: var(--section-bg);
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }

    .section-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid var(--input-border);
        color: var(--text-primary);
    }

    .section-header h3 { margin: 0; font-size: 1.3rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
    .section-header i { font-size: 1.4rem; color: var(--input-focus); }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 700;
        color: var(--text-primary);
        font-size: 0.95rem;
    }

    .form-control {
        width: 100%;
        padding: 0.85rem 1rem;
        border: 2px solid var(--input-border);
        border-radius: 8px;
        font-size: 1.05rem; /* Larger font for readability */
        color: var(--text-primary);
        background: white;
        transition: all 0.2s;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .form-control:focus {
        border-color: var(--input-focus);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        outline: none;
    }

    .highlight { border-color: #3b82f6 !important; background: #eff6ff !important; font-weight: 800; }
    .highlight-money { border-color: #059669 !important; color: #059669 !important; font-weight: 800; font-size: 1.2rem; }

    .form-actions {
        position: sticky;
        bottom: 0;
        background: white;
        padding: 1.5rem 0;
        margin-top: 2rem;
        border-top: 2px solid var(--input-border);
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
    }
</style>

<script>
    let currentZoom = 1;
    const img = document.getElementById('billImage');
    const container = document.getElementById('imageContainer');

    function zoomImg(scale) {
        currentZoom *= scale;
        if (currentZoom < 0.5) currentZoom = 0.5;
        if (currentZoom > 4) currentZoom = 4;
        img.style.transform = `scale(${currentZoom})`;
        
        // Update cursor based on zoom
        if (currentZoom > 1) {
            img.style.cursor = 'grab';
        } else {
            img.style.cursor = 'default';
        }
    }

    function resetZoom() {
        currentZoom = 1;
        img.style.transform = `scale(1)`;
        img.style.cursor = 'default';
        container.scrollTo(0, 0);
    }

    // Drag to scroll functionality
    let isDown = false;
    let startX;
    let startY;
    let scrollLeft;
    let scrollTop;

    container.addEventListener('mousedown', (e) => {
        if (currentZoom <= 1) return;
        isDown = true;
        img.style.cursor = 'grabbing';
        startX = e.pageX - container.offsetLeft;
        startY = e.pageY - container.offsetTop;
        scrollLeft = container.scrollLeft;
        scrollTop = container.scrollTop;
        img.style.userSelect = 'none'; // Prevent selection
    });

    window.addEventListener('mouseup', () => {
        if (isDown) {
            isDown = false;
            if (currentZoom > 1) img.style.cursor = 'grab';
        }
    });

    container.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - container.offsetLeft;
        const y = e.pageY - container.offsetTop;
        const walkX = (x - startX) * 2;
        const walkY = (y - startY) * 2;
        container.scrollLeft = scrollLeft - walkX;
        container.scrollTop = scrollTop - walkY;
    });
</script>


<?php include 'includes/footer.php'; ?>

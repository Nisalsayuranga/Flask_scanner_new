<?php
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';
?>

<header class="fade-in">
    <div class="welcome">
        <h1>Batch Scan (Bulk Processing)</h1>
        <p>Upload up to 50 bills at once. Processing happens automatically in the background.</p>
    </div>
</header>

<div class="card fade-in">

    <div id="drop-area" class="drop-zone">
        <div class="drop-zone-content">
            <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
            <h3>Drag & Drop Bills Here</h3>
            <p>Support for PDF and Images (JPG, PNG). Maximum 50 files per batch.</p>
            <input type="file" id="file-input" multiple accept="application/pdf,image/*" hidden>
            <button class="btn btn-primary" onclick="document.getElementById('file-input').click()" style="margin-top: 1rem;">
                Select Files
            </button>
        </div>
    </div>

    <!-- Selected Files List -->
    <div id="file-list-container" style="display: none; margin-top: 2rem;">
        <h4 style="margin-bottom: 1rem;">Selected Files (<span id="file-count">0</span>)</h4>
        <div id="file-items" class="file-grid"></div>
        
        <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem;">
            <button class="btn" onclick="clearFiles()">Clear All</button>
            <button id="upload-btn" class="btn btn-primary" onclick="startBatchUpload()">
                <i class="fas fa-rocket"></i> Start Batch Processing
            </button>
        </div>
    </div>
</div>

<!-- Upload Progress Modal -->
<div id="progress-modal" class="modal" style="display: none;">
    <div class="modal-content card">
        <h3 style="margin-bottom: 0.5rem; font-size: 1.5rem;">Processing Batch...</h3>
        <p id="progress-text" style="color: var(--text-muted); margin-bottom: 1rem;">Uploading and preparing files...</p>
        
        <div class="progress-bar-container">
            <div id="progress-bar" class="progress-bar"></div>
        </div>

        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 0.9rem; font-weight: 600; color: var(--primary);">
            <span id="elapsed-time">Elapsed Time: 00:00</span>
            <span id="eta-time">Estimated Remaining: Calculating...</span>
        </div>

        <div id="process-log" class="process-log"></div>
    </div>
</div>

<style>
    .drop-zone {
        border: 2px dashed var(--border);
        border-radius: 16px;
        padding: 4rem 2rem;
        text-align: center;
        transition: all 0.3s ease;
        background: rgba(0,0,0,0.01);
        cursor: pointer;
    }
    .drop-zone.active {
        border-color: var(--primary);
        background: rgba(79, 70, 229, 0.05);
    }
    .file-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
    }
    .file-item {
        background: var(--bg-body);
        border: 1px solid var(--border);
        padding: 0.75rem;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.85rem;
    }
    .file-item i { color: var(--primary); }
    
    .modal {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        backdrop-filter: blur(4px);
    }
    .modal-content {
        width: 800px;
        max-width: 95vw;
        padding: 2.5rem;
    }
    .progress-bar-container {
        height: 12px;
        background: var(--border);
        border-radius: 6px;
        margin: 1rem 0;
        overflow: hidden;
    }
    .progress-bar {
        height: 100%;
        background: var(--primary);
        width: 0%;
        transition: width 0.3s ease;
    }
    .process-log {
        height: 350px;
        overflow-y: auto;
        background: #0f172a;
        color: #10b981;
        padding: 1.5rem;
        border-radius: 10px;
        font-family: monospace;
        font-size: 0.85rem;
        line-height: 1.5;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let selectedFiles = [];
    const dropArea = document.getElementById('drop-area');
    const fileInput = document.getElementById('file-input');
    const fileItems = document.getElementById('file-items');
    const fileListContainer = document.getElementById('file-list-container');
    const fileCountLabel = document.getElementById('file-count');

    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
        }, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.add('active'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.remove('active'), false);
    });

    dropArea.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        handleFiles(dt.files);
    });

    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });

    function handleFiles(files) {
        const newFiles = Array.from(files);
        if (selectedFiles.length + newFiles.length > 50) {
            alert("Maximum 50 files allowed.");
            return;
        }
        selectedFiles = [...selectedFiles, ...newFiles];
        renderFileList();
    }

    function renderFileList() {
        fileItems.innerHTML = '';
        selectedFiles.forEach((file, index) => {
            const div = document.createElement('div');
            div.className = 'file-item fade-in';
            div.innerHTML = `
                <i class="fas fa-file-image"></i>
                <span style="flex:1; overflow:hidden; text-overflow:ellipsis;">${file.name}</span>
                <i class="fas fa-times" style="cursor:pointer; color:var(--danger);" onclick="window.removeBatchFile(${index})"></i>
            `;
            fileItems.appendChild(div);
        });

        fileCountLabel.innerText = selectedFiles.length;
        fileListContainer.style.display = selectedFiles.length > 0 ? 'block' : 'none';
    }

    window.removeBatchFile = function(index) {
        selectedFiles.splice(index, 1);
        renderFileList();
    };

    window.clearFiles = function() {
        selectedFiles = [];
        renderFileList();
    };

    async function compressImage(file) {
        if (file.type === 'application/pdf') return file;
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = (e) => {
                const img = new Image();
                img.src = e.target.result;
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    const MAX_WIDTH = 1800;
                    let width = img.width;
                    let height = img.height;
                    if (width > MAX_WIDTH) {
                        height = Math.round((height * MAX_WIDTH) / width);
                        width = MAX_WIDTH;
                    }
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);
                    canvas.toBlob((blob) => resolve(blob), 'image/jpeg', 0.85);
                };
            };
        });
    }

    window.startBatchUpload = async function() {
        if (selectedFiles.length === 0) return;

        const modal = document.getElementById('progress-modal');
        const log = document.getElementById('process-log');
        const bar = document.getElementById('progress-bar');
        const text = document.getElementById('progress-text');
        const btn = document.getElementById('upload-btn');

        modal.style.display = 'flex';
        btn.disabled = true;
        const total = selectedFiles.length;
        let completed = 0;
        let currentIndex = 0;
        const failedFiles = [];

        log.innerHTML = `<span style="color:#60a5fa">⚡ Starting High-Speed Batch Processing (${Math.min(6, total)} Workers)</span><br><hr style="border-color:#1e293b; margin:10px 0;">`;

        const processNext = async (workerId) => {
            if (currentIndex >= selectedFiles.length) return;
            
            const file = selectedFiles[currentIndex++];
            log.innerHTML += `<span style="color:#94a3b8">[W${workerId}]</span> Preparing ${file.name}...<br>`;
            
            try {
                const blob = await compressImage(file);
                const fd = new FormData();
                fd.append('bill_image', blob, file.name);
                fd.append('batch_mode', '1');

                const response = await fetch('process_scan.php', { method: 'POST', body: fd });
                const resText = await response.text();
                
                let result;
                try {
                    result = JSON.parse(resText);
                } catch (e) {
                    log.innerHTML += `<span style="color:var(--danger)">✗ [W${workerId}] Server Error: ${resText.substring(0, 100)}...</span><br>`;
                    failedFiles.push(file);
                    completed++;
                    await processNext(workerId);
                    return;
                }

                if (result.success) {
                    log.innerHTML += `<span style="color:#10b981">✓ [W${workerId}] Success: ${file.name}</span><br>`;
                } else {
                    log.innerHTML += `<span style="color:var(--danger)">✗ [W${workerId}] AI Error: ${result.error}</span><br>`;
                    failedFiles.push(file);
                }
            } catch (e) {
                log.innerHTML += `<span style="color:var(--danger)">✗ [W${workerId}] Network Error: ${e.message}</span><br>`;
                failedFiles.push(file);
            }

            completed++;
            bar.style.width = Math.round((completed / total) * 100) + '%';
            text.innerText = `Processed ${completed} of ${total} files...`;
            log.scrollTop = log.scrollHeight;
            await processNext(workerId);
        };

        const workers = [];
        const workerCount = Math.min(6, total); // Increased to 6 for faster processing with multiple keys
        for (let i = 1; i <= workerCount; i++) {
            workers.push(processNext(i));
        }

        await Promise.all(workers);

        if (failedFiles.length > 0) {
            log.innerHTML += `<br><span style="color:var(--warning)"><b>⚠️ ${failedFiles.length} files failed. Please retry or check server logs.</b></span>`;
            btn.disabled = false;
            btn.innerText = "Retry Failed Files";
            selectedFiles = [...failedFiles];
        } else {
            log.innerHTML += '<br><b style="color:#10b981">🚀 All bills processed successfully!</b>';
            setTimeout(() => window.location.href = 'records.php', 2000);
        }
    };
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

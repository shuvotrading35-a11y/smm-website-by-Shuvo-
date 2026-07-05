<?php /* Add Funds View */ ?>

<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fas fa-wallet" style="color:var(--primary);"></i> Add Funds</h1>
    <p class="page-subtitle">Top up your balance instantly using your preferred method</p>
  </div>
  <div class="topbar-balance">
    <i class="fas fa-coins"></i> Balance: $<?= number_format((float)$user['balance'], 2) ?>
  </div>
</div>

<div class="row g-4">
  <!-- Left: Payment Method Selector -->
  <div class="col-lg-4">
    <div class="glass-flat p-4 mb-4">
      <h5 style="font-family:var(--font-heading);font-weight:700;margin-bottom:16px;">Choose Method</h5>
      <div style="display:flex;flex-direction:column;gap:8px;" id="methodTabs">
        <?php foreach ($methods as $key => $method): ?>
        <button class="payment-method-tab <?= $key === array_key_first($methods) ? 'active' : '' ?>"
                id="tab-<?= $key ?>"
                onclick="selectPaymentMethod('<?= $key ?>')">
          <i class="fas fa-money-bill-wave" style="color:<?= htmlspecialchars($method['color']) ?>;width:20px;"></i>
          <?= htmlspecialchars($method['name']) ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Minimum deposit notice -->
    <div class="glass-flat p-4" style="border-color:rgba(124,92,255,0.2);background:var(--primary-light);">
      <div style="display:flex;gap:10px;align-items:flex-start;">
        <i class="fas fa-info-circle" style="color:var(--primary);margin-top:2px;"></i>
        <div>
          <div style="font-weight:700;font-size:0.9rem;margin-bottom:4px;">Minimum Deposit</div>
          <div style="color:var(--text-secondary);font-size:0.85rem;">
            Min: $<?= number_format($minDeposit, 2) ?><br>
            Deposits are manually verified (usually within 30 min).
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Right: Payment Instructions + Form -->
  <div class="col-lg-8">
    <form id="depositForm" enctype="multipart/form-data">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="method" id="paymentMethodInput" value="<?= htmlspecialchars(array_key_first($methods)) ?>">

      <?php foreach ($methods as $key => $method): ?>
      <div class="payment-panel glass-flat p-4 mb-4" id="panel-<?= $key ?>"
           style="<?= $key === array_key_first($methods) ? 'display:block;' : 'display:none;' ?>">

        <!-- Method Header -->
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--border);">
          <div style="width:52px;height:52px;border-radius:14px;background:<?= htmlspecialchars($method['color']) ?>20;border:1px solid <?= htmlspecialchars($method['color']) ?>40;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
            <i class="fas fa-money-bill-wave" style="color:<?= htmlspecialchars($method['color']) ?>;"></i>
          </div>
          <div>
            <div style="font-family:var(--font-heading);font-weight:700;font-size:1.1rem;"><?= htmlspecialchars($method['name']) ?></div>
            <div style="font-size:0.82rem;color:var(--text-muted);">Manual verification</div>
          </div>
        </div>

        <!-- Steps -->
        <div style="margin-bottom:20px;">
          <div style="font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-muted);margin-bottom:12px;">How to pay</div>
          <?php foreach ($method['steps'] as $i => $step): ?>
          <div class="payment-step">
            <div class="step-number"><?= $i + 1 ?></div>
            <div style="font-size:0.88rem;color:var(--text-secondary);padding-top:3px;"><?= htmlspecialchars($step) ?></div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Account Number / Address -->
        <?php if (!empty($method['number'])): ?>
        <div style="margin-bottom:20px;">
          <div style="font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-muted);margin-bottom:8px;">
            Send to
          </div>
          <div class="wallet-address-box">
            <span id="addr-<?= $key ?>"><?= htmlspecialchars($method['number']) ?></span>
            <?php if (!empty($method['type'])): ?>
            <span style="margin-left:8px;font-size:0.78rem;background:var(--primary-light);color:var(--primary);padding:2px 8px;border-radius:99px;"><?= htmlspecialchars($method['type']) ?></span>
            <?php endif; ?>
            <button type="button" class="copy-btn" style="position:absolute;top:12px;right:12px;"
                    onclick="copyToClipboard('<?= htmlspecialchars($method['number']) ?>', this)">
              <i class="fas fa-copy"></i>
            </button>
          </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($method['address'])): ?>
        <div style="margin-bottom:20px;">
          <div style="font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-muted);margin-bottom:8px;">
            Wallet Address
          </div>
          <div class="wallet-address-box">
            <?= htmlspecialchars($method['address']) ?>
            <button type="button" class="copy-btn" style="position:absolute;top:12px;right:12px;"
                    onclick="copyToClipboard('<?= htmlspecialchars($method['address']) ?>', this)">
              <i class="fas fa-copy"></i>
            </button>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>

      <!-- Proof Submission -->
      <div class="glass-flat p-4">
        <h5 style="font-family:var(--font-heading);font-weight:700;margin-bottom:20px;">
          <i class="fas fa-paper-plane" style="color:var(--accent);"></i> Submit Payment Proof
        </h5>

        <div class="row g-3">
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">Amount Sent ($)</label>
              <div class="input-icon-wrap">
                <i class="input-icon fas fa-dollar-sign"></i>
                <input type="number" name="amount" class="form-control" id="depositAmount"
                       placeholder="10.00" min="<?= $minDeposit ?>" step="0.01" required>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">Transaction ID / TrxID</label>
              <div class="input-icon-wrap">
                <i class="input-icon fas fa-hashtag"></i>
                <input type="text" name="transaction_id" class="form-control"
                       placeholder="e.g. TRX123456789" required>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Screenshot / Proof <span style="font-weight:400;text-transform:none;font-size:0.8rem;color:var(--text-muted);">(JPEG/PNG/WebP, max 5MB)</span></label>
              <div style="border:2px dashed var(--border);border-radius:var(--radius-lg);padding:24px;text-align:center;cursor:pointer;transition:all 0.25s;"
                   id="dropZone"
                   onclick="document.getElementById('proofFile').click()"
                   ondragover="this.style.borderColor='var(--primary)';event.preventDefault();"
                   ondragleave="this.style.borderColor='var(--border)';"
                   ondrop="handleFileDrop(event)">
                <i class="fas fa-cloud-arrow-up" style="font-size:2rem;color:var(--text-muted);margin-bottom:10px;display:block;"></i>
                <div style="font-size:0.9rem;color:var(--text-secondary);">Drop file here or click to upload</div>
                <div style="font-size:0.78rem;color:var(--text-muted);margin-top:4px;" id="fileLabel">No file chosen</div>
              </div>
              <input type="file" name="proof" id="proofFile" accept="image/jpeg,image/png,image/webp" style="display:none;"
                     onchange="updateFileLabel(this)">
            </div>
          </div>

          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Note <span style="font-weight:400;text-transform:none;font-size:0.8rem;">(optional)</span></label>
              <textarea name="note" class="form-control" rows="2" placeholder="Any additional info for our team…"></textarea>
            </div>
          </div>
        </div>

        <button type="button" class="btn btn-aurora btn-lg w-100 mt-2"
                onclick="submitDeposit(this)">
          <i class="fas fa-paper-plane"></i> Submit Payment Proof
        </button>
      </div>
    </form>

    <!-- Pending Deposits -->
    <?php if (!empty($pendingDeposits)): ?>
    <div class="glass-flat mt-4 p-4">
      <h6 style="font-family:var(--font-heading);font-weight:700;margin-bottom:16px;">
        <i class="fas fa-clock" style="color:var(--warning);"></i> Pending Deposits
      </h6>
      <div class="table-wrap" style="border:none;border-radius:0;">
        <table class="table" style="font-size:0.85rem;">
          <thead>
            <tr>
              <th>Amount</th><th>Method</th><th>TrxID</th><th>Status</th><th>Submitted</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pendingDeposits as $d): ?>
            <tr>
              <td style="font-weight:700;">$<?= number_format((float)$d['amount'], 2) ?></td>
              <td><?= htmlspecialchars(strtoupper($d['method'])) ?></td>
              <td style="font-family:monospace;"><?= htmlspecialchars($d['transaction_id'] ?? '—') ?></td>
              <td><span class="badge badge-pending">Pending</span></td>
              <td style="color:var(--text-muted);"><?= date('M d, H:i', strtotime($d['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
function updateFileLabel(input) {
  const label = document.getElementById('fileLabel');
  if (input.files && input.files[0]) {
    const f = input.files[0];
    label.textContent = f.name + ' (' + (f.size / 1024 / 1024).toFixed(2) + ' MB)';
    label.style.color = 'var(--success)';
    document.getElementById('dropZone').style.borderColor = 'var(--success)';
  }
}

function handleFileDrop(e) {
  e.preventDefault();
  document.getElementById('dropZone').style.borderColor = 'var(--border)';
  const file = e.dataTransfer.files[0];
  if (file) {
    const dt = new DataTransfer();
    dt.items.add(file);
    document.getElementById('proofFile').files = dt.files;
    updateFileLabel(document.getElementById('proofFile'));
  }
}
</script>

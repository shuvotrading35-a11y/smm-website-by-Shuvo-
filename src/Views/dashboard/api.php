<?php /* Dashboard API Access View */ ?>

<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fas fa-code" style="color:var(--primary);"></i> API Access</h1>
    <p class="page-subtitle">Integrate your applications with our REST API</p>
  </div>
  <a href="/api-docs" class="btn btn-outline" target="_blank">
    <i class="fas fa-book"></i> Full Docs
  </a>
</div>

<div class="row g-4">
  <!-- API Key Card -->
  <div class="col-12">
    <div class="glass-flat p-4">
      <h5 style="font-family:var(--font-heading);font-weight:700;margin-bottom:20px;">
        <i class="fas fa-key" style="color:var(--warning);"></i> Your API Key
      </h5>

      <div class="api-key-box mb-3">
        <i class="fas fa-key" style="color:var(--text-muted);flex-shrink:0;"></i>
        <span class="api-key-value"
              id="apiKeyDisplay"
              data-masked="<?= htmlspecialchars($apiKey['key_prefix'] ?? 'xxxx') ?>••••••••••••••••••••••••••••••••••••••••••••••••••••••••••"
              data-full="<?= htmlspecialchars($_SESSION['new_api_key'] ?? ($apiKey['key_prefix'] ?? '')) ?>••••••••••••••••••••••••••••••••••••••••••••••••••••••••••">
          <?= htmlspecialchars($apiKey['key_prefix'] ?? 'No key') ?>••••••••••••••••••••••••••••••••••••••••••••••••••••••••••
        </span>
        <button class="btn btn-ghost btn-icon" onclick="toggleApiKey(this)" title="Show/Hide">
          <i class="fas fa-eye"></i>
        </button>
        <button class="btn btn-ghost btn-icon" title="Copy"
                onclick="copyToClipboard(document.getElementById('apiKeyDisplay').textContent.trim(), this)">
          <i class="fas fa-copy"></i>
        </button>
      </div>

      <?php if (isset($_SESSION['new_api_key'])): ?>
      <div style="padding:12px 16px;background:var(--success-bg);border:1px solid rgba(0,230,118,0.3);border-radius:var(--radius);color:var(--success);font-size:0.85rem;margin-bottom:16px;display:flex;gap:10px;align-items:center;">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Save this key now!</strong> For security, it will not be shown in full again.
      </div>
      <?php php_unset: unset($_SESSION['new_api_key']); ?>
      <?php endif; ?>

      <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-danger btn-sm" onclick="confirmAction('Regenerate API key? Your old key will stop working immediately.', async () => {
          const data = await ajaxPost(\'/dashboard/api/regenerate\', {_csrf: getCsrfToken()});
          if (data.success) { showToast(\'API key regenerated. Refreshing…\', \'success\'); setTimeout(() => location.reload(), 1000); }
          else showToast(data.message, \'error\');
        })">
          <i class="fas fa-rotate"></i> Regenerate Key
        </button>
        <a href="/api-docs" class="btn btn-ghost btn-sm" target="_blank">
          <i class="fas fa-book-open"></i> View Documentation
        </a>
      </div>
    </div>
  </div>

  <!-- Usage Stats -->
  <div class="col-md-4">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(124,92,255,0.15);color:var(--primary);">
        <i class="fas fa-chart-bar"></i>
      </div>
      <div class="stat-value counter" data-target="<?= $usageToday ?>"><?= $usageToday ?></div>
      <div class="stat-label">Requests Today</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(0,212,255,0.15);color:var(--secondary);">
        <i class="fas fa-calendar-month"></i>
      </div>
      <div class="stat-value counter" data-target="<?= $usageMonth ?>"><?= $usageMonth ?></div>
      <div class="stat-label">Requests This Month</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(0,230,118,0.15);color:var(--success);">
        <i class="fas fa-gauge-high"></i>
      </div>
      <div class="stat-value">60</div>
      <div class="stat-label">Rate Limit / min</div>
    </div>
  </div>

  <!-- Endpoint Info -->
  <div class="col-12">
    <div class="glass-flat p-4">
      <h5 style="font-family:var(--font-heading);font-weight:700;margin-bottom:16px;">
        <i class="fas fa-server" style="color:var(--secondary);"></i> Endpoint
      </h5>
      <div class="code-block mb-3">
        <span class="code-lang">URL</span>
        <span class="code-kw">POST</span> <span class="code-str"><?= htmlspecialchars($apiUrl) ?></span>
      </div>
      <p style="color:var(--text-secondary);font-size:0.88rem;">
        All requests use POST with <code style="background:var(--surface);padding:2px 6px;border-radius:4px;">application/x-www-form-urlencoded</code>.
        Always include your API key as the <code style="background:var(--surface);padding:2px 6px;border-radius:4px;">key</code> parameter.
      </p>
    </div>
  </div>

  <!-- Quick Code Example -->
  <div class="col-12">
    <div class="glass-flat p-4">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <h5 style="font-family:var(--font-heading);font-weight:700;margin:0;">
          <i class="fas fa-terminal" style="color:var(--success);"></i> Quick Example
        </h5>
        <div style="display:flex;gap:6px;">
          <?php foreach(['PHP','Python','cURL'] as $i => $lang): ?>
          <button class="btn <?= $i===0?'btn-primary':'btn-ghost' ?> btn-sm api-lang-btn"
                  data-lang="<?= strtolower($lang) ?>"
                  onclick="switchApiLang(this)">
            <?= $lang ?>
          </button>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="code-block api-code" id="code-php">
        <span class="code-lang">PHP</span>
<pre><span class="code-cmt">// Place order via API</span>
<span class="code-var">$ch</span> = <span class="code-fn">curl_init</span>();
<span class="code-fn">curl_setopt_array</span>(<span class="code-var">$ch</span>, [
    CURLOPT_URL        => <span class="code-str">'<?= htmlspecialchars($apiUrl) ?>'</span>,
    CURLOPT_POST       => <span class="code-var">true</span>,
    CURLOPT_POSTFIELDS => <span class="code-fn">http_build_query</span>([
        <span class="code-str">'key'</span>      => <span class="code-str">'YOUR_API_KEY'</span>,
        <span class="code-str">'action'</span>   => <span class="code-str">'add'</span>,
        <span class="code-str">'service'</span>  => <span class="code-var">1</span>,
        <span class="code-str">'link'</span>     => <span class="code-str">'https://instagram.com/yourpage'</span>,
        <span class="code-str">'quantity'</span> => <span class="code-var">1000</span>,
    ]),
    CURLOPT_RETURNTRANSFER => <span class="code-var">true</span>,
]);
<span class="code-var">$result</span> = <span class="code-fn">json_decode</span>(<span class="code-fn">curl_exec</span>(<span class="code-var">$ch</span>), <span class="code-var">true</span>);
<span class="code-fn">echo</span> <span class="code-str">"Order ID: "</span> . <span class="code-var">$result</span>[<span class="code-str">'order'</span>];</pre>
      </div>

      <div class="code-block api-code" id="code-python" style="display:none;">
        <span class="code-lang">Python</span>
<pre><span class="code-kw">import</span> requests

data = {
    <span class="code-str">'key'</span>:      <span class="code-str">'YOUR_API_KEY'</span>,
    <span class="code-str">'action'</span>:   <span class="code-str">'add'</span>,
    <span class="code-str">'service'</span>:  <span class="code-var">1</span>,
    <span class="code-str">'link'</span>:     <span class="code-str">'https://instagram.com/yourpage'</span>,
    <span class="code-str">'quantity'</span>: <span class="code-var">1000</span>,
}
r = requests.<span class="code-fn">post</span>(<span class="code-str">'<?= htmlspecialchars($apiUrl) ?>'</span>, data=data)
<span class="code-fn">print</span>(<span class="code-fn">f</span><span class="code-str">"Order ID: {r.json()['order']}"</span>)</pre>
      </div>

      <div class="code-block api-code" id="code-curl" style="display:none;">
        <span class="code-lang">cURL</span>
<pre>curl -X POST <?= htmlspecialchars($apiUrl) ?> \
  -d <span class="code-str">"key=YOUR_API_KEY"</span> \
  -d <span class="code-str">"action=add"</span> \
  -d <span class="code-str">"service=1"</span> \
  -d <span class="code-str">"link=https://instagram.com/yourpage"</span> \
  -d <span class="code-str">"quantity=1000"</span></pre>
      </div>
    </div>
  </div>
</div>

<script>
function switchApiLang(btn) {
  document.querySelectorAll('.api-lang-btn').forEach(b => b.className = b.className.replace('btn-primary','btn-ghost'));
  btn.className = btn.className.replace('btn-ghost','btn-primary');
  document.querySelectorAll('.api-code').forEach(p => p.style.display = 'none');
  document.getElementById('code-' + btn.dataset.lang).style.display = 'block';
}
</script>

<?php /* API Documentation View */ ?>

<div style="padding-top:70px;">
  <div style="padding:60px 40px 40px;text-align:center;" data-aos="fade-up">
    <div style="display:inline-flex;align-items:center;gap:8px;padding:8px 18px;background:var(--primary-light);border:1px solid rgba(124,92,255,0.3);border-radius:999px;font-size:0.82rem;font-weight:700;color:var(--primary);margin-bottom:20px;">
      <i class="fas fa-code"></i> REST API v2
    </div>
    <h1 style="font-family:var(--font-heading);font-size:clamp(2rem,5vw,3rem);font-weight:700;letter-spacing:-0.03em;margin-bottom:12px;">
      API <span class="gradient-text">Documentation</span>
    </h1>
    <p style="color:var(--text-secondary);font-size:1rem;max-width:560px;margin:0 auto;">
      Automate your SMM orders with our simple REST API. Compatible with any programming language.
    </p>
  </div>

  <div class="container-xl" style="padding-bottom:80px;">
    <div class="row g-4">
      <!-- Left: Navigation -->
      <div class="col-lg-3 d-none d-lg-block">
        <div class="glass-flat p-4" style="position:sticky;top:90px;">
          <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--text-muted);margin-bottom:12px;">Contents</div>
          <?php
          $sections = ['Authentication','Endpoint','Actions','Services','Add Order','Order Status','Balance','Refill','Cancel','Code Examples'];
          foreach ($sections as $s):
            $anchor = strtolower(str_replace(' ', '-', $s));
          ?>
          <a href="#<?= $anchor ?>" style="display:block;padding:7px 10px;color:var(--text-secondary);font-size:0.88rem;border-radius:8px;transition:all 0.2s;"
             onmouseover="this.style.background='var(--surface-hover)';this.style.color='var(--text)'"
             onmouseout="this.style.background='';this.style.color=''">
            <?= htmlspecialchars($s) ?>
          </a>
          <?php endforeach; ?>
          <div class="divider"></div>
          <a href="/dashboard/api" class="btn btn-primary btn-sm w-100">
            <i class="fas fa-key"></i> Get API Key
          </a>
        </div>
      </div>

      <!-- Right: Content -->
      <div class="col-lg-9">
        <!-- Authentication -->
        <div id="authentication" class="glass-flat p-5 mb-4" data-aos="fade-up">
          <h2 style="font-family:var(--font-heading);font-weight:700;font-size:1.5rem;margin-bottom:12px;">
            <i class="fas fa-key" style="color:var(--primary);"></i> Authentication
          </h2>
          <p style="color:var(--text-secondary);line-height:1.7;">
            All API requests require your personal API key. Never expose your key in client-side code. Find your key in
            <a href="/dashboard/api" style="color:var(--primary);">Dashboard → API Access</a>.
          </p>
          <div style="background:var(--danger-bg);border:1px solid rgba(255,75,85,0.2);border-radius:var(--radius);padding:12px 16px;margin-top:16px;font-size:0.88rem;color:var(--danger);display:flex;gap:10px;align-items:center;">
            <i class="fas fa-exclamation-triangle"></i>
            Keep your API key secret. Do not share it or commit it to public repositories.
          </div>
        </div>

        <!-- Endpoint -->
        <div id="endpoint" class="glass-flat p-5 mb-4" data-aos="fade-up">
          <h2 style="font-family:var(--font-heading);font-weight:700;font-size:1.5rem;margin-bottom:16px;">
            <i class="fas fa-server" style="color:var(--secondary);"></i> Endpoint
          </h2>
          <div class="code-block">
            <span class="code-kw">POST</span> <span class="code-str"><?= htmlspecialchars($apiUrl) ?></span>
            <span class="code-lang">HTTP</span>
          </div>
          <p style="color:var(--text-secondary);margin-top:16px;font-size:0.9rem;">
            All requests are <strong>POST</strong>. Parameters are sent as <code style="background:var(--surface);padding:2px 6px;border-radius:4px;font-size:0.85rem;">application/x-www-form-urlencoded</code>.
          </p>
        </div>

        <!-- Actions Reference Table -->
        <div id="actions" class="glass-flat p-5 mb-4" data-aos="fade-up">
          <h2 style="font-family:var(--font-heading);font-weight:700;font-size:1.5rem;margin-bottom:16px;">
            <i class="fas fa-table" style="color:var(--accent);"></i> Actions Reference
          </h2>
          <div class="table-wrap">
            <table class="table">
              <thead>
                <tr><th>Action</th><th>Required Parameters</th><th>Description</th></tr>
              </thead>
              <tbody>
                <?php
                $actions = [
                  ['services',      'key, action',                             'Fetch all available services'],
                  ['add',           'key, action, service, link, quantity',    'Place a new order'],
                  ['status',        'key, action, order',                      'Get single order status'],
                  ['status',        'key, action, orders (comma-separated)',   'Get bulk order statuses'],
                  ['balance',       'key, action',                             'Get account balance'],
                  ['refill',        'key, action, order',                      'Request order refill'],
                  ['refill_status', 'key, action, refill',                     'Check refill status'],
                  ['cancel',        'key, action, orders (comma-separated)',   'Cancel orders'],
                ];
                foreach ($actions as $a): ?>
                <tr>
                  <td><code style="background:var(--primary-light);color:var(--primary);padding:2px 8px;border-radius:6px;font-size:0.82rem;"><?= $a[0] ?></code></td>
                  <td style="font-size:0.82rem;color:var(--text-muted);font-family:monospace;"><?= $a[1] ?></td>
                  <td style="font-size:0.88rem;"><?= $a[2] ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Code Examples Tabs -->
        <div id="code-examples" class="glass-flat p-5 mb-4" data-aos="fade-up">
          <h2 style="font-family:var(--font-heading);font-weight:700;font-size:1.5rem;margin-bottom:20px;">
            <i class="fas fa-terminal" style="color:var(--success);"></i> Code Examples
          </h2>

          <!-- Language Tabs -->
          <div style="display:flex;gap:6px;margin-bottom:20px;flex-wrap:wrap;">
            <?php foreach (['PHP','Python','Node.js','cURL'] as $i => $lang): ?>
            <button class="btn <?= $i===0?'btn-primary':'btn-ghost' ?> btn-sm lang-tab"
                    data-lang="<?= strtolower(str_replace('.','',str_replace(' ','',$lang))) ?>"
                    onclick="switchLang(this)">
              <?= $lang ?>
            </button>
            <?php endforeach; ?>
          </div>

          <!-- PHP -->
          <div class="code-block lang-panel" id="panel-php">
            <span class="code-lang">PHP</span>
<pre><span class="code-cmt">// Add order example</span>
<span class="code-var">$params</span> = [
    <span class="code-str">'key'</span>      => <span class="code-str">'YOUR_API_KEY'</span>,
    <span class="code-str">'action'</span>   => <span class="code-str">'add'</span>,
    <span class="code-str">'service'</span>  => <span class="code-var">1</span>,
    <span class="code-str">'link'</span>     => <span class="code-str">'https://instagram.com/username'</span>,
    <span class="code-str">'quantity'</span> => <span class="code-var">1000</span>,
];

<span class="code-var">$ch</span> = <span class="code-fn">curl_init</span>();
<span class="code-fn">curl_setopt_array</span>(<span class="code-var">$ch</span>, [
    CURLOPT_URL            => <span class="code-str">'<?= htmlspecialchars($apiUrl) ?>'</span>,
    CURLOPT_POST           => <span class="code-var">true</span>,
    CURLOPT_POSTFIELDS     => <span class="code-fn">http_build_query</span>(<span class="code-var">$params</span>),
    CURLOPT_RETURNTRANSFER => <span class="code-var">true</span>,
]);

<span class="code-var">$response</span> = <span class="code-fn">json_decode</span>(<span class="code-fn">curl_exec</span>(<span class="code-var">$ch</span>), <span class="code-var">true</span>);
<span class="code-fn">curl_close</span>(<span class="code-var">$ch</span>);

<span class="code-fn">echo</span> <span class="code-str">'Order ID: '</span> . <span class="code-var">$response</span>[<span class="code-str">'order'</span>];</pre>
          </div>

          <!-- Python -->
          <div class="code-block lang-panel" id="panel-python" style="display:none;">
            <span class="code-lang">Python</span>
<pre><span class="code-kw">import</span> requests

params = {
    <span class="code-str">'key'</span>:      <span class="code-str">'YOUR_API_KEY'</span>,
    <span class="code-str">'action'</span>:   <span class="code-str">'add'</span>,
    <span class="code-str">'service'</span>:  <span class="code-var">1</span>,
    <span class="code-str">'link'</span>:     <span class="code-str">'https://instagram.com/username'</span>,
    <span class="code-str">'quantity'</span>: <span class="code-var">1000</span>,
}

response = requests.<span class="code-fn">post</span>(<span class="code-str">'<?= htmlspecialchars($apiUrl) ?>'</span>, data=params)
data = response.<span class="code-fn">json</span>()

<span class="code-fn">print</span>(<span class="code-fn">f</span><span class="code-str">"Order ID: {data['order']}"</span>)</pre>
          </div>

          <!-- Node.js -->
          <div class="code-block lang-panel" id="panel-nodejs" style="display:none;">
            <span class="code-lang">Node.js</span>
<pre><span class="code-kw">const</span> axios = <span class="code-fn">require</span>(<span class="code-str">'axios'</span>);

<span class="code-kw">const</span> params = <span class="code-kw">new</span> URLSearchParams({
    key:      <span class="code-str">'YOUR_API_KEY'</span>,
    action:   <span class="code-str">'add'</span>,
    service:  <span class="code-var">1</span>,
    link:     <span class="code-str">'https://instagram.com/username'</span>,
    quantity: <span class="code-var">1000</span>,
});

<span class="code-kw">const</span> { data } = <span class="code-kw">await</span> axios.<span class="code-fn">post</span>(
    <span class="code-str">'<?= htmlspecialchars($apiUrl) ?>'</span>,
    params.toString()
);

console.<span class="code-fn">log</span>(<span class="code-str">'Order ID:'</span>, data.order);</pre>
          </div>

          <!-- cURL -->
          <div class="code-block lang-panel" id="panel-curl" style="display:none;">
            <span class="code-lang">cURL</span>
<pre>curl -X POST <?= htmlspecialchars($apiUrl) ?> \
  -d <span class="code-str">"key=YOUR_API_KEY"</span> \
  -d <span class="code-str">"action=add"</span> \
  -d <span class="code-str">"service=1"</span> \
  -d <span class="code-str">"link=https://instagram.com/username"</span> \
  -d <span class="code-str">"quantity=1000"</span></pre>
          </div>
        </div>

        <!-- CTA -->
        <div class="glass-flat p-5 text-center" data-aos="fade-up">
          <h3 style="font-family:var(--font-heading);font-weight:700;margin-bottom:12px;">Ready to integrate?</h3>
          <p style="color:var(--text-secondary);margin-bottom:24px;">Get your API key from the dashboard and start automating in minutes.</p>
          <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <a href="/register" class="btn btn-aurora"><i class="fas fa-rocket"></i> Create Account</a>
            <a href="/dashboard/api" class="btn btn-outline"><i class="fas fa-key"></i> Get API Key</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function switchLang(btn) {
  document.querySelectorAll('.lang-tab').forEach(b => {
    b.className = b.className.replace('btn-primary','btn-ghost');
  });
  btn.className = btn.className.replace('btn-ghost','btn-primary');

  document.querySelectorAll('.lang-panel').forEach(p => p.style.display = 'none');
  document.getElementById('panel-' + btn.dataset.lang).style.display = 'block';
}
</script>

<?php
/**
 * ============================================================
 *  SHIPROCKET SHIPPING MANAGER (ADMIN)
 *  URL: /backend/shiprocket/admin.php
 *
 *  A minimal, self-contained admin panel to manage order shipping:
 *    - List store orders
 *    - Create Shiprocket shipments
 *    - Download shipping labels (AWB)
 *    - Track shipments in real time
 *
 *  Secured with a session password from the environment variable
 *  SHIPROCKET_ADMIN_KEY. Set it before use; leave it blank to
 *  disable the panel entirely (recommended for production).
 * ============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/shiprocket-curl.php';
require_once __DIR__ . '/shiprocket-helper.php';

session_start();

// Build absolute URLs for API calls from this page.
$base = (getenv('SITE_URL') ?: ('https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'))) . '/backend/shiprocket';

$adminKey = getenv('SHIPROCKET_ADMIN_KEY') ?: '';

// ---- Router: handle API actions (JSON) ----
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    // Auth check for API actions too.
    if ($adminKey === '' || ($_SESSION['sr_admin'] ?? '') !== hash('sha256', $adminKey)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $action = $_GET['action'];
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'login') {
        $pw = $input['password'] ?? '';
        if (hash('sha256', $pw) === hash('sha256', $adminKey)) {
            $_SESSION['sr_admin'] = hash('sha256', $adminKey);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Wrong password']);
        }
        exit;
    }

    if ($action === 'logout') {
        unset($_SESSION['sr_admin']);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'create') {
        $orderId = trim($input['order_id'] ?? '');
        $orderFile = ORDERS_DIR . '/' . basename($orderId) . '.json';
        if (!preg_match('/^OP\d{17}$/', $orderId) || !file_exists($orderFile)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }
        $order = json_decode(file_get_contents($orderFile), true);

        // Only ship PAID or COD-approved orders.
        $status = $order['payment_status'] ?? 'PENDING';
        $isCodApproved = (($order['payment_method'] ?? '') === 'cod') && (($order['cod_confirmed'] ?? false) === true);
        if ($status !== 'PAID' && !$isCodApproved) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Order must be PAID before shipping']);
            exit;
        }
        if (!empty($order['shiprocket_id'])) {
            echo json_encode(['success' => true, 'message' => 'Already shipped', 'awb' => $order['awb'] ?? '']);
            exit;
        }

        $payload = sr_build_shipment_payload($order);
        $result = sr_api_request('POST', '/shipments/create', $payload);
        if (!$result['success']) {
            http_response_code(502);
            echo json_encode(['success' => false, 'message' => $result['error']]);
            exit;
        }
        $shipment = $result['data'];
        $order['shiprocket_id'] = $shipment['shipment_id'] ?? null;
        $order['awb'] = $shipment['awb'] ?? '';
        $order['shiprocket_status'] = $shipment['status'] ?? 'PICKUP_PENDING';
        $order['shipped_at'] = date('Y-m-d H:i:s');
        file_put_contents($orderFile, json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        @chmod($orderFile, 0600);
        log_msg("Shiprocket shipment created for $orderId");
        echo json_encode(['success' => true, 'awb' => $order['awb'], 'shipment_id' => $order['shiprocket_id']]);
        exit;
    }

    if ($action === 'track') {
        $orderId = trim($input['order_id'] ?? '');
        $orderFile = ORDERS_DIR . '/' . basename($orderId) . '.json';
        if (!preg_match('/^OP\d{17}$/', $orderId) || !file_exists($orderFile)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }
        $order = json_decode(file_get_contents($orderFile), true);
        $awb = $order['awb'] ?? '';
        if ($awb === '') {
            echo json_encode(['success' => false, 'message' => 'No AWB yet']);
            exit;
        }
        $result = sr_api_request('GET', '/courier/track/awb/' . urlencode($awb));
        if (!$result['success']) {
            http_response_code(502);
            echo json_encode(['success' => false, 'message' => $result['error']]);
            exit;
        }
        echo json_encode(['success' => true, 'awb' => $awb, 'tracking_data' => $result['data']]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

// ---- Folder listing ---
$orders = [];
if (is_dir(ORDERS_DIR)) {
    $files = glob(ORDERS_DIR . '/*.json');
    foreach ($files as $f) {
        $o = json_decode(file_get_contents($f), true);
        if ($o && isset($o['order_id'])) {
            $o['_file'] = basename($f);
            $orders[] = $o;
        }
    }
    // Newest first
    usort($orders, function ($a, $b) {
        return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
    });
}

$authed = ($adminKey !== '' && ($_SESSION['sr_admin'] ?? '') === hash('sha256', $adminKey));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shiprocket Shipping Manager</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: system-ui,-apple-system,Segoe UI,Roboto,sans-serif; background:#f1f5f9; margin:0; color:#0f172a; }
    header { background:#0f766e; color:#fff; padding:18px 24px; }
    header h1 { margin:0; font-size:1.3rem; }
    .wrap { max-width:1100px; margin:24px auto; padding:0 16px; }
    .card { background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,.1); padding:20px; margin-bottom:20px; }
    table { width:100%; border-collapse:collapse; }
    th, td { text-align:left; padding:10px 8px; border-bottom:1px solid #e2e8f0; font-size:.9rem; vertical-align:top; }
    th { background:#f8fafc; font-size:.8rem; text-transform:uppercase; letter-spacing:.03em; color:#64748b; }
    .badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:.75rem; font-weight:600; }
    .b-paid { background:#dcfce7; color:#166534; }
    .b-cod { background:#fef9c3; color:#854d0e; }
    .b-pending { background:#fee2e2; color:#991b1b; }
    .b-shipped { background:#dbeafe; color:#1e40af; }
    .btn { border:0; border-radius:8px; padding:8px 12px; font-weight:600; cursor:pointer; font-size:.85rem; margin:2px; }
    .btn-primary { background:#0f766e; color:#fff; }
    .btn-label { background:#1d4ed8; color:#fff; }
    .btn-track { background:#7c3aed; color:#fff; }
    .btn:disabled { opacity:.5; cursor:not-allowed; }
    .empty { color:#94a3b8; text-align:center; padding:30px; }
    .msg { padding:10px 14px; border-radius:8px; margin-top:12px; font-size:.85rem; display:none; }
    .msg.ok { background:#dcfce7; color:#166534; }
    .msg.err { background:#fee2e2; color:#991b1b; }
    input[type=password] { padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:.9rem; }
    .login-box { max-width:360px; margin:80px auto; }
    .track-box { background:#f8fafc; border-radius:8px; padding:12px; margin-top:10px; font-size:.85rem; max-height:280px; overflow:auto; white-space:pre-wrap; }
    code { background:#f1f5f9; padding:1px 5px; border-radius:4px; }
</style>
</head>
<body>
<header>
    <h1>Shiprocket Shipping Manager</h1>
    <div style="font-size:.85rem;margin-top:4px;opacity:.85;">Organic Pesticide - Krishna Worldwide</div>
</header>

<div class="wrap">
<?php if (!$authed): ?>
    <div class="card login-box">
        <h3>Admin Login</h3>
        <p style="color:#64748b;font-size:.85rem;">Enter the admin password set in <code>SHIPROCKET_ADMIN_KEY</code>.</p>
        <input type="password" id="pw" placeholder="Password" style="width:100%;">
        <div class="msg" id="msg"></div>
        <button class="btn btn-primary" id="loginBtn" style="width:100%;margin-top:10px;">Login</button>
    </div>
    <script>
        document.getElementById('loginBtn').addEventListener('click', async () => {
            const msg = document.getElementById('msg');
            msg.style.display = 'block';
            msg.className = 'msg err';
            msg.textContent = 'Logging in...';
            const r = await fetch('?action=login', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ password: document.getElementById('pw').value }) });
            if (r.ok) { location.reload(); }
            else { msg.textContent = (await r.json()).message || 'Login failed'; }
        });
    </script>
<?php else: ?>
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
            <div><strong>Orders: <?php echo count($orders); ?></strong>
                <span style="color:#64748b;font-size:.8rem;margin-left:8px;">Shopping via Shiprocket v2 API</span>
            </div>
            <div>
                <?php if (SHIPROCKET_TOKEN_KEY === ''): ?>
                    <span class="badge b-pending" title="Set SHIPROCKET_TOKEN_KEY or email/password env vars">Auth: env not configured</span>
                <?php else: ?>
                    <span class="badge b-paid">Auth: token configured</span>
                <?php endif; ?>
                <button class="btn" id="logoutBtn">Logout</button>
            </div>
        </div>
    </div>

    <div class="card">
        <?php if (count($orders) === 0): ?>
            <div class="empty">No orders found yet.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Shipping</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <?php
                $pm = $o['payment_method'] ?? '';
                $ps = $o['payment_status'] ?? 'PENDING';
                $awb = $o['awb'] ?? '';
                $shipped = !empty($awb);
                $payBadge = $ps === 'PAID' ? 'b-paid' : (($pm === 'cod') ? 'b-cod' : 'b-pending');
                $shipBadge = $shipped ? 'b-shipped' : 'b-pending';
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($o['order_id']); ?></strong><br>
                        <span style="color:#94a3b8;font-size:.75rem;"><?php echo htmlspecialchars($o['created_at'] ?? ''); ?></span>
                    </td>
                    <td><?php echo htmlspecialchars(($o['customer']['name'] ?? '') . ' ' . ($o['customer']['lastname'] ?? '')); ?><br>
                        <span style="color:#94a3b8;font-size:.75rem;"><?php echo htmlspecialchars($o['customer']['phone'] ?? ''); ?><br>(<?php echo htmlspecialchars(($o['customer']['city'] ?? '') . ', ' . ($o['customer']['state'] ?? '')); ?>)</span>
                    </td>
                    <td>₹<?php echo htmlspecialchars(number_format((float)($o['total'] ?? 0), 2)); ?></td>
                    <td><span class="badge <?php echo $payBadge; ?>"><?php echo strtoupper($pm); ?> / <?php echo $ps; ?></span></td>
                    <td>
                        <?php if ($shipped): ?>
                            <span class="badge <?php echo $shipBadge; ?>">AWB: <?php echo htmlspecialchars($awb); ?></span>
                        <?php else: ?>
                            <span class="badge b-pending">Not shipped</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$shipped): ?>
                            <button class="btn btn-primary" data-act="create" data-order="<?php echo htmlspecialchars($o['order_id']); ?>">Create Shipment</button>
                        <?php else: ?>
                            <a class="btn btn-label" href="label.php?order_id=<?php echo urlencode($o['order_id']); ?>" target="_blank">Label</a>
                            <button class="btn btn-track" data-act="track" data-order="<?php echo htmlspecialchars($o['order_id']); ?>">Track</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <div class="msg" id="msg"></div>
        <div class="track-box" id="trackBox" style="display:none;"></div>
    </div>

    <script>
        const msg = document.getElementById('msg');
        function setMsg(text, ok) {
            msg.style.display = 'block';
            msg.className = 'msg ' + (ok ? 'ok' : 'err');
            msg.textContent = text;
            setTimeout(() => { msg.style.display = 'none'; }, 4000);
        }
        document.getElementById('logoutBtn').addEventListener('click', () => {
            fetch('?action=logout', { method:'POST' }).then(() => location.reload());
        });

        document.querySelectorAll('button[data-act]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const act = btn.dataset.act;
                const order = btn.dataset.order;
                btn.disabled = true;

                if (act === 'create') {
                    const r = await fetch('?action=create', {
                        method:'POST',
                        headers:{'Content-Type':'application/json'},
                        body: JSON.stringify({ order_id: order })
                    });
                    const j = await r.json();
                    if (j.success) {
                        setMsg('Shipment created! AWB: ' + (j.awb || 'n/a'), true);
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        setMsg(j.message, false);
                    }
                }

                if (act === 'track') {
                    const box = document.getElementById('trackBox');
                    const r = await fetch('?action=track', {
                        method:'POST',
                        headers:{'Content-Type':'application/json'},
                        body: JSON.stringify({ order_id: order })
                    });
                    const j = await r.json();
                    if (j.success) {
                        box.style.display = 'block';
                        const td = j.tracking_data;
                        const status = (td && td.current_status) || 'n/a';
                        box.textContent = 'AWB ' + j.awb + ' — Status: ' + status + '\n\n' + JSON.stringify(td, null, 2);
                    } else {
                        box.style.display = 'block';
                        box.textContent = j.message;
                    }
                }
                btn.disabled = false;
            });
        });
    </script>
<?php endif; ?>
</div>
</body>
</html>

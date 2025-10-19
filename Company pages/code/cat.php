<?php
session_start();
// Simple envato-style cart page (single-file)
// Save this as cart.php and run with a PHP server: php -S localhost:8000

// Initialize sample cart (only if session empty)
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [
        101 => ['id'=>101,'title'=>'Minimal UI Kit','price'=>29.00,'thumb'=>'assets/thumb1.jpg','qty'=>1],
        102 => ['id'=>102,'title'=>'Multipurpose WordPress Theme','price'=>49.00,'thumb'=>'assets/thumb2.jpg','qty'=>1],
        103 => ['id'=>103,'title'=>'Illustration Pack','price'=>19.00,'thumb'=>'assets/thumb3.jpg','qty'=>2],
    ];
}

// Basic helpers
function money($n){return number_format((float)$n,2,'.',',');}

// Handle actions: update qty, remove, apply coupon, checkout
$messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_qty') {
        $id = intval($_POST['id'] ?? 0);
        $qty = max(0,intval($_POST['qty'] ?? 1));
        if ($qty === 0) {
            unset($_SESSION['cart'][$id]);
            $messages[] = "Item removed from cart.";
        } else {
            if (isset($_SESSION['cart'][$id])) $_SESSION['cart'][$id]['qty'] = $qty;
            $messages[] = "Quantity updated.";
        }
    }
    if ($action === 'remove') {
        $id = intval($_POST['id'] ?? 0);
        unset($_SESSION['cart'][$id]);
        $messages[] = "Item removed from cart.";
    }
    if ($action === 'apply_coupon') {
        $code = trim($_POST['coupon'] ?? '');
        // Simple coupon rules
        $valid = ['ENVATO10'=>0.10, 'ELEM5'=>0.05];
        if ($code && isset($valid[strtoupper($code)])) {
            $_SESSION['coupon'] = strtoupper($code);
            $messages[] = "Coupon applied: ".htmlspecialchars($_SESSION['coupon']);
        } else {
            unset($_SESSION['coupon']);
            $messages[] = "Invalid coupon code.";
        }
    }
    if ($action === 'checkout') {
        // Basic capture - server-side validate
        $payment = $_POST['payment'] ?? 'card';
        // For demo, we'll 'process' and clear cart
        $order = [
            'items'=>$_SESSION['cart'],
            'total'=>0,
            'payment'=>$payment,
            'time'=>date('c')
        ];
        // compute totals
        $subtotal = 0;
        foreach ($_SESSION['cart'] as $it) $subtotal += $it['price'] * $it['qty'];
        $discount = 0;
        if (!empty($_SESSION['coupon'])) {
            $map = ['ENVATO10'=>0.10,'ELEM5'=>0.05];
            $discount = $subtotal * ($map[$_SESSION['coupon']] ?? 0);
        }
        $tax = ($subtotal - $discount) * 0.10; // 10% tax
        $total = $subtotal - $discount + $tax;
        $order['total'] = $total;
        // pretend to store order (in session)
        $_SESSION['last_order'] = $order;
        // clear cart
        unset($_SESSION['cart'], $_SESSION['coupon']);
        $messages[] = "Checkout complete — Order total: $".money($total).". (This is a demo.)";
    }
}

// Recompute summary
$items = $_SESSION['cart'] ?? [];
$subtotal = 0;
foreach ($items as $it) $subtotal += $it['price'] * $it['qty'];
$coupon_code = $_SESSION['coupon'] ?? null;
$discount = 0;
if ($coupon_code) {
    $map = ['ENVATO10'=>0.10,'ELEM5'=>0.05];
    $discount = $subtotal * ($map[$coupon_code] ?? 0);
}
$tax = ($subtotal - $discount) * 0.10; // 10% tax
$total = $subtotal - $discount + $tax;

?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Cart — Envato-style Checkout</title>
  <style>
    :root{--accent:#00a878;--muted:#6b7280;--bg:#f6f7f9;--card:#ffffff;--radius:12px}
    *{box-sizing:border-box}
    body{font-family:Inter,ui-sans-serif,system-ui,Segoe UI,Arial;line-height:1.4;background:var(--bg);color:#111;margin:0;padding:32px}
    .container{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:1fr 360px;gap:24px}
    @media (max-width:900px){.container{grid-template-columns:1fr;padding:16px} .side{order:2}}

    .card{background:var(--card);border-radius:var(--radius);box-shadow:0 6px 22px rgba(15,23,42,0.06);padding:20px}
    h1{margin:0 0 12px;font-size:20px}

    /* Items list */
    .items{display:flex;flex-direction:column;gap:12px}
    .item{display:flex;gap:12px;align-items:center;padding:12px;border-radius:10px;border:1px solid #eef2f7}
    .thumb{width:68px;height:68px;border-radius:8px;flex:0 0 68px;background:#eee;background-size:cover;background-position:center}
    .meta{flex:1}
    .meta h3{margin:0;font-size:15px}
    .meta p{margin:6px 0 0;color:var(--muted);font-size:13px}
    .price{font-weight:600;min-width:90px;text-align:right}

    .qty-wrap{display:flex;align-items:center;gap:8px;margin-top:8px}
    .qty-wrap input[type=number]{width:64px;padding:6px;border-radius:6px;border:1px solid #dbe4ea}
    .btn{display:inline-block;padding:10px 14px;border-radius:8px;border:0;background:var(--accent);color:white;font-weight:600;cursor:pointer}
    .btn.ghost{background:transparent;color:var(--accent);border:1px solid rgba(0,168,120,0.12)}

    /* Coupon */
    .coupon{display:flex;gap:8px;margin-top:12px}
    .coupon input{flex:1;padding:10px;border-radius:8px;border:1px solid #dbe4ea}

    /* Summary */
    .side .summary-row{display:flex;justify-content:space-between;padding:8px 0;color:var(--muted)}
    .total{font-size:18px;font-weight:700;padding-top:8px}

    /* Payment */
    .payments{display:flex;flex-direction:column;gap:8px;margin-top:12px}
    .pm{display:flex;align-items:center;gap:12px;padding:10px;border-radius:8px;border:1px solid #eef2f7}
    .pm input{transform:scale(1.1)}

    /* messages */
    .messages{margin-bottom:12px}
    .message{background:#f0fff6;border:1px solid rgba(0,168,120,0.12);padding:10px;border-radius:8px;color:#064e3b}

    /* empty cart */
    .empty{padding:40px;text-align:center;color:var(--muted)}

    footer.note{margin-top:18px;color:var(--muted);font-size:13px}
  </style>
</head>
<body>
  <div class="container">
    <main>
      <div class="card">
        <h1>Your cart</h1>
        <?php if($messages): ?>
          <div class="messages">
            <?php foreach($messages as $m): ?>
              <div class="message"><?php echo htmlspecialchars($m); ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if(empty($items)): ?>
          <div class="empty card">Your cart is empty. Browse items and add them to proceed to checkout.</div>
        <?php else: ?>
        <div class="items">
          <?php foreach($items as $it): ?>
            <div class="item">
              <div class="thumb" style="background-image:url('<?php echo htmlspecialchars($it['thumb']); ?>')"></div>
              <div class="meta">
                <h3><?php echo htmlspecialchars($it['title']); ?></h3>
                <p>By <strong>AuthorName</strong> • License: Regular</p>
                <div class="qty-wrap">
                  <form method="post" style="display:flex;gap:8px;align-items:center">
                    <input type="hidden" name="action" value="update_qty">
                    <input type="hidden" name="id" value="<?php echo $it['id']; ?>">
                    <label for="qty-<?php echo $it['id']; ?>">Qty</label>
                    <input id="qty-<?php echo $it['id']; ?>" type="number" name="qty" min="0" value="<?php echo $it['qty']; ?>">
                    <button class="btn ghost" type="submit">Update</button>
                  </form>
                  <form method="post" style="margin-left:6px">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="id" value="<?php echo $it['id']; ?>">
                    <button class="btn" type="submit">Remove</button>
                  </form>
                </div>
              </div>
              <div class="price">$<?php echo money($it['price'] * $it['qty']); ?></div>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="margin-top:14px" class="card">
          <form method="post" style="display:flex;flex-direction:column;gap:8px">
            <div style="display:flex;gap:8px" class="coupon">
              <input type="text" name="coupon" placeholder="Coupon code" value="<?php echo htmlspecialchars($coupon_code ?? ''); ?>">
              <input type="hidden" name="action" value="apply_coupon">
              <button class="btn" type="submit">Apply</button>
            </div>
            <div style="font-size:13px;color:var(--muted)">Try codes: <strong>ENVATO10</strong> (10%) or <strong>ELEM5</strong> (5%)</div>
          </form>
        </div>

        <?php endif; ?>

      </div>

      <div class="card" style="margin-top:16px">
        <h1>Order summary</h1>
        <div class="summary-row"><span>Subtotal</span><span>$<?php echo money($subtotal); ?></span></div>
        <div class="summary-row"><span>Discount</span><span>$<?php echo money($discount); ?></span></div>
        <div class="summary-row"><span>Tax (10%)</span><span>$<?php echo money($tax); ?></span></div>
        <div class="summary-row total"><span>Total</span><span>$<?php echo money($total); ?></span></div>
        <footer class="note">All prices in USD. Taxes estimated.</footer>
      </div>
    </main>

    <aside class="side">
      <div class="card">
        <h1>Payment</h1>
        <form method="post">
          <input type="hidden" name="action" value="checkout">

          <div class="payments">
            <label class="pm"><input type="radio" name="payment" value="paypal" <?php echo (isset($_POST['payment']) && $_POST['payment']==='paypal') ? 'checked' : ''; ?>> PayPal</label>
            <label class="pm"><input type="radio" name="payment" value="card" checked> Credit / Debit Card</label>
            <label class="pm"><input type="radio" name="payment" value="apple"> Apple Pay</label>
          </div>

          <div style="margin-top:12px">
            <div style="font-size:13px;color:var(--muted);margin-bottom:8px">Card details (demo only)</div>
            <input name="ccname" placeholder="Name on card" style="width:100%;padding:10px;border-radius:8px;border:1px solid #dbe4ea;margin-bottom:8px">
            <input name="ccnum" placeholder="Card number" style="width:100%;padding:10px;border-radius:8px;border:1px solid #dbe4ea;margin-bottom:8px">
            <div style="display:flex;gap:8px"><input name="exp" placeholder="MM/YY" style="flex:1;padding:10px;border-radius:8px;border:1px solid #dbe4ea"><input name="cvc" placeholder="CVC" style="width:120px;padding:10px;border-radius:8px;border:1px solid #dbe4ea"></div>
          </div>

          <div style="margin-top:12px;display:flex;gap:8px;align-items:center">
            <button class="btn" type="submit">Proceed to Checkout</button>
            <button type="button" onclick="window.location='cart.php?clear=1'" class="btn ghost">Clear cart</button>
          </div>
        </form>
      </div>

      <div class="card" style="margin-top:12px">
        <h1>Secure checkout</h1>
        <p style="color:var(--muted);font-size:14px">We use secure payment processors. This demo does not transmit real card data.</p>
      </div>
    </aside>
  </div>

  <script>
    // small enhancement: confirm clear cart
    document.querySelectorAll('button[onclick]').forEach(b=>{
      b.addEventListener('click',e=>{
        if(b.getAttribute('onclick') && b.getAttribute('onclick').includes('clear=1')){
          if(!confirm('Clear cart? This will remove all items.')) e.preventDefault();
        }
      })
    })
  </script>
</body>
</html>

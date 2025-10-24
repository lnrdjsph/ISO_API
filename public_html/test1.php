<title>EGC Summary</title>
<?php
require_once 'views/header.php';

if (!in_array((int)$user_role, [1, 4], true)) {
    if (!headers_sent()) {
        header("Location: unauthorized.php");
        exit();
    } else {
        echo '<script>window.location.href="'. appendRandomString('unauthorized') . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=unauthorized.php"></noscript>';
        exit();
    }
}

$connect = mysqli_connect('188.166.251.64', 'jeajqgxxrd', '4nHT3RFc2g', 'jeajqgxxrd');
if (mysqli_connect_errno()) {
    exit('Failed to connect to MySQL: ' . mysqli_connect_error());
}

function displayTimePassedOrDate($timestamp) {
    date_default_timezone_set('Asia/Manila');
    return date("Y-m-d h:i A", strtotime($timestamp));
}

$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

$dateCondition = '';
if ($startDate && $endDate) {
    if ($startDate === $endDate) {
        $dateCondition = "AND posts.post_date BETWEEN '{$startDate} 00:00:00' AND '{$endDate} 23:59:59'";
    } else {
        $dateCondition = "AND posts.post_date BETWEEN '$startDate' AND '$endDate 23:59:59'";
    }
} elseif ($startDate) {
    $dateCondition = "AND posts.post_date >= '$startDate 00:00:00'";
} elseif ($endDate) {
    $dateCondition = "AND posts.post_date <= '{$endDate} 23:59:59'";
}

$search = $_GET['search'] ?? '';
$searchCondition = '';

if ($search !== '') {
    $searchEscaped = mysqli_real_escape_string($connect, $search);
    $searchCondition = " AND (posts.ID LIKE '%$searchEscaped%' OR paymaya.meta_value LIKE '%$searchEscaped%')";
}

$table_prefix = 'wp_55_';
$sql = "
    SELECT SQL_NO_CACHE
        posts.ID AS order_id,
        posts.post_date,
        posts.post_status AS order_status,
        billing.meta_key AS billing_key,
        billing.meta_value AS billing_value,
        itemmeta.meta_key AS item_meta_key,
        itemmeta.meta_value AS item_meta_value,
        order_items.order_item_id,
        paymaya.meta_value AS paymaya_checkout_id
    FROM {$table_prefix}posts posts
    LEFT JOIN {$table_prefix}postmeta billing ON billing.post_id = posts.ID
    LEFT JOIN {$table_prefix}postmeta paymaya ON paymaya.post_id = posts.ID AND paymaya.meta_key = 'paymaya_checkout_id'
    LEFT JOIN {$table_prefix}woocommerce_order_items order_items ON order_items.order_id = posts.ID
    LEFT JOIN {$table_prefix}woocommerce_order_itemmeta itemmeta ON order_items.order_item_id = itemmeta.order_item_id
    WHERE posts.post_type = 'shop_order' $dateCondition   $searchCondition
    AND posts.post_status = 'wc-completed'  -- <-- Added this line
    ORDER BY posts.post_date DESC
";

$result = mysqli_query($connect, $sql);
if (!$result) die("Query failed: " . mysqli_error($connect));

$orders = [];
while ($row = mysqli_fetch_assoc($result)) {
    $order_id = $row['order_id'];
    $order_item_id = $row['order_item_id'];

    $paymaya_checkout_id = $row['paymaya_checkout_id'] ?? '';
    $cleaned_paymaya_id = substr(preg_replace('/[^a-zA-Z0-9]/', '', $paymaya_checkout_id), -12);
    $cleaned_paymaya_id = !empty($cleaned_paymaya_id) ? $cleaned_paymaya_id : "N/A";

    if (!isset($orders[$order_id])) {
        $orders[$order_id] = [
            'payment_ref' => $cleaned_paymaya_id,
            'first_name' => '',
            'last_name' => '',
            'phone' => '',
            'email' => '',
            'created_at' => $row['post_date'],
            'order_status' => $row['order_status'],
            'items' => []
        ];
    }

    if (!empty($row['billing_key'])) {
        switch ($row['billing_key']) {
            case '_billing_first_name': $orders[$order_id]['first_name'] = $row['billing_value']; break;
            case '_billing_last_name':  $orders[$order_id]['last_name']  = $row['billing_value']; break;
            case '_billing_phone':      $orders[$order_id]['phone']      = $row['billing_value']; break;
            case '_billing_email':      $orders[$order_id]['email']      = $row['billing_value']; break;
        }
    }

    if (!isset($orders[$order_id]['items'][$order_item_id])) {
        $orders[$order_id]['items'][$order_item_id] = [
            'order_ref' => "order-{$order_item_id}",
            'payment_ref' => $cleaned_paymaya_id,
            'quantity' => 0,
            'amount' => 0,
            'receiver_email' => '',
            'receiver_name' => '',
            'message' => '',
            'delivery_date' => '',
            'egc_icard' => ''
        ];
    }

    if (!empty($row['item_meta_key'])) {
        switch ($row['item_meta_key']) {
            case '_qty':
                $orders[$order_id]['items'][$order_item_id]['quantity'] = (int)$row['item_meta_value'];
                break;
            case '_ywgc_amount':
                $orders[$order_id]['items'][$order_item_id]['amount'] = (float)$row['item_meta_value'];
                break;
            case '_ywgc_recipients':
                $recipients = unserialize($row['item_meta_value']);
                $orders[$order_id]['items'][$order_item_id]['receiver_email'] = $recipients[0] ?? '';
                break;
            case '_ywgc_recipient_name':
                $orders[$order_id]['items'][$order_item_id]['receiver_name'] = $row['item_meta_value'];
                break;
            case '_ywgc_message':
                $orders[$order_id]['items'][$order_item_id]['message'] = $row['item_meta_value'];
                break;
            case '_ywgc_delivery_date':
                $timestamp = $row['item_meta_value'];
                $orders[$order_id]['items'][$order_item_id]['delivery_date'] = date('Y-m-d h:i A', $timestamp);
                break;
            case '_egc_icard':
                $orders[$order_id]['items'][$order_item_id]['egc_icard'] = (int)$row['item_meta_value'];
                break;
        }
    }
}
mysqli_close($connect);

// Calculate grand total
$grand_total = 0;
foreach ($orders as $order) {
    foreach ($order['items'] as $item) {
        $grand_total += $item['amount'];
    }
}


function generateRandomRef($length = 10) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $ref = '';
    for ($i = 0; $i < $length; $i++) {
        $ref .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $ref;
}
$randomRef = generateRandomRef();
?>
<style>
.page-link {
  user-select: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
}</style>
<!-- Bootstrap Filter Form -->
<form method="GET" class="my-4">
    <input type="hidden" name="ref" value="<?= htmlspecialchars($randomRef) ?>">
    <div class="row g-3">
        <div class="col-md-4">
            <div class="form-floating">
                <input type="text" id="search" name="search" class="form-control"
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Order Number or Payment Ref">
                <label for="search">Order # or Payment Ref</label>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-floating">
                <input type="date" id="start_date" name="start_date" class="form-control"
                       value="<?= htmlspecialchars($startDate) ?>" placeholder="Start Date">
                <label for="start_date">Start Date</label>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-floating">
                <input type="date" id="end_date" name="end_date" class="form-control"
                       value="<?= htmlspecialchars($endDate) ?>" placeholder="End Date">
                <label for="end_date">End Date</label>
            </div>
        </div>
        <div class="col-md-1 d-grid align-items-end">
            <button type="submit" class="btn btn-secondary p-3">Filter</button>
        </div>
    </div>
</form>




<div class="my-4">
    <div class="row">
        <!-- Orders Summary Table (8 columns) -->
    <div class="col-md-9">
    <div class="card rounded">
        <div class="card-body rounded p-0">
        <table class="table table-bordered table-striped mb-0">
            <thead class="table-dark">
            <tr>
                <th>Order Number</th>
                <th>Payment Ref</th>
                <th>Amount (₱)</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order_id => $order): 
                $order_total = 0;
                foreach ($order['items'] as $item) {
                    $order_total += $item['amount'];
                }
            ?>
            <tr>
                <td>#<?= htmlspecialchars($order_id) ?></td>
                <td><?= htmlspecialchars($order['payment_ref']) ?></td>
                <td><?= number_format($order_total, 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?>
            <tr><td colspan="3" class="text-center">No orders found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
        <div class="card-footer">
        <nav>
            <ul class="pagination justify-content-center mb-0" id="pagination"></ul>
        </nav>
        </div>
    </div>
    </div>


        <!-- Summary Card (4 columns) -->
        <div class="col-md-3 d-flex align-items-center justify-content-center align-items-center">
            <div style="
                background-color: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 10px;
                padding: 20px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                text-align: center;
                width: 100%;
                height: 100%;  /* fill full height of col */
            ">
                <br><h2 class="text-sidebar">Summary of Orders</h2><br>
                <div class="bg-white border rounded-3 p-4 mb-4 shadow-sm">
                    <h4 class="mb-3 text-sidebar">Total Orders</h4>
                    <h2 class="m-0 " style="color: #7DAE32;"><?= count($orders) ?></h2>
                </div>

                <div class="bg-white border rounded-3 p-4 shadow-sm">
                    <h4 class="mb-3 text-sidebar">Total Amount</h4>
                    <h2 class="m-0" style="color: #7DAE32;">₱<?= number_format($grand_total, 2) ?></h2>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const rowsPerPage = 10;
  const table = document.querySelector('table tbody');
  const rows = table.querySelectorAll('tr');
  const pagination = document.getElementById('pagination');
  const totalRows = rows.length;
  const totalPages = Math.ceil(totalRows / rowsPerPage);

  let currentPage = 1;

  function showPage(page) {
    if (page < 1) page = 1;
    if (page > totalPages) page = totalPages;
    currentPage = page;

    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    rows.forEach((row, index) => {
      row.style.display = (index >= start && index < end) ? '' : 'none';
    });
    updatePagination();
  }

  function updatePagination() {
    pagination.innerHTML = '';
    const ul = document.createElement('ul');
    ul.className = 'pagination';

    ul.appendChild(createPageItem('«', currentPage === 1, () => showPage(1)));           // First
    ul.appendChild(createPageItem('‹', currentPage === 1, () => showPage(currentPage - 1))); // Previous

    let startPage = Math.max(1, currentPage - 1);
    let endPage = Math.min(totalPages, startPage + 2);
    if (endPage - startPage < 2) {
      startPage = Math.max(1, endPage - 2);
    }

    for (let i = startPage; i <= endPage; i++) {
      ul.appendChild(createPageItem(i.toString(), i === currentPage, () => showPage(i)));
    }

    ul.appendChild(createPageItem('›', currentPage === totalPages, () => showPage(currentPage + 1))); // Next
    ul.appendChild(createPageItem('»', currentPage === totalPages, () => showPage(totalPages)));        // Last

    pagination.appendChild(ul);
  }

  function createPageItem(text, disabledOrActive, onClick) {
    const li = document.createElement('li');
    li.className = 'page-item';

    const a = document.createElement('a');
    a.className = 'page-link';
    a.href = '#';
    a.textContent = text;

    if (typeof disabledOrActive === 'boolean') {
      if (disabledOrActive) {
        li.classList.add('disabled');
      } else {
        a.addEventListener('click', e => {
          e.preventDefault();
          onClick();
        });
      }
    } else {
      li.classList.add('active');
    }

    li.appendChild(a);
    return li;
  }

  if (totalPages > 1) {
    showPage(1);
  }
});

</script>

<?php include('views/footer.php'); ?>
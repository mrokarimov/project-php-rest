<?php
date_default_timezone_set("Europe/Warsaw");

/* ===================== GENERIC API FETCH ===================== */
function fetchApi($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true) ?? [];
}

/* ===================== NBP CURRENCY ===================== */
function getRates($currency) {
    $url = "https://api.nbp.pl/api/exchangerates/rates/a/$currency/last/20/?format=json";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true) ?? [];
}

/* ===================== BASE URL ===================== */
$BASE = "https://project-php-rest.onrender.com/api";

/* ===================== FETCH DB APIs ===================== */
$salesData   = fetchApi("$BASE/sales_monthly.php");
$userData    = fetchApi("$BASE/user_growth.php");
$trafficData = fetchApi("$BASE/traffic_sources.php");
$productData = fetchApi("$BASE/product_categories.php");
$clapsData   = fetchApi("$BASE/get_claps.php");

/* ===================== FETCH CURRENCY ===================== */
$usd = getRates("usd");
$chf = getRates("chf");

/* ===================== DATA PREP ===================== */
$salesMonths = array_column($salesData,'month');
$salesValues = array_map('intval',array_column($salesData,'value'));

$userDays   = array_column($userData,'day');
$userCounts = array_map('intval',array_column($userData,'users'));

$trafficLabels = array_column($trafficData,'source');
$trafficValues = array_map('intval',array_column($trafficData,'value'));

$productLabels = array_column($productData,'category');
$productValues = array_map('intval',array_column($productData,'value'));

$usdX = array_column($usd['rates'] ?? [], 'effectiveDate');
$usdY = array_column($usd['rates'] ?? [], 'mid');

$chfX = array_column($chf['rates'] ?? [], 'effectiveDate');
$chfY = array_column($chf['rates'] ?? [], 'mid');

$totalSales = array_sum($salesValues);
$totalUsers = array_sum($userCounts);
$totalClaps = $clapsData['total'] ?? 0;

$topTraffic = $trafficLabels[array_search(max($trafficValues),$trafficValues)] ?? '-';
$topProduct = $productLabels[array_search(max($productValues),$productValues)] ?? '-';

$lastUpdate = date("d M Y, H:i");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Analytics Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>

<style>
body{background:#f5f7fb;font-family:system-ui}
.card{border:none;border-radius:14px;box-shadow:0 4px 14px rgba(0,0,0,.06)}
.chart{height:320px}
h2{font-weight:700}
</style>
</head>

<body>
<div class="container py-5">

<div class="text-center mb-4">
<h2>Enterprise Analytics Dashboard</h2>
<p class="text-muted">
REST APIs • PostgreSQL • NBP API • Render<br>
Last updated: <?=$lastUpdate?>
</p>
</div>

<!-- KPI -->
<div class="row g-4 mb-4">
<div class="col-md-3"><div class="card p-3 text-center"><small>Total Sales</small><h4><?=$totalSales?> PLN</h4></div></div>
<div class="col-md-3"><div class="card p-3 text-center"><small>Total Users</small><h4><?=$totalUsers?></h4></div></div>
<div class="col-md-3"><div class="card p-3 text-center"><small>Traffic Sources</small><h4><?=count($trafficLabels)?></h4><small>Top: <?=$topTraffic?></small></div></div>
<div class="col-md-3"><div class="card p-3 text-center"><small>Claps</small><h4><?=$totalClaps?></h4></div></div>
</div>

<!-- ROW 1 -->
<div class="row g-4 mb-4">
<div class="col-lg-6">
<div class="card p-3">
<strong>Monthly Sales</strong>
<div id="salesChart" class="chart"></div>
</div>
</div>
<div class="col-lg-6">
<div class="card p-3">
<strong>User Growth</strong>
<div id="userChart" class="chart"></div>
</div>
</div>
</div>

<!-- ROW 2 -->
<div class="row g-4 mb-4">
<div class="col-lg-6">
<div class="card p-3">
<strong>Traffic Sources</strong>
<div id="trafficChart" class="chart"></div>
</div>
</div>
<div class="col-lg-6">
<div class="card p-3">
<strong>Product Categories</strong>
<div id="productChart" class="chart"></div>
</div>
</div>
</div>

<!-- ROW 3 -->
<div class="row g-4">
<div class="col-lg-6">
<div class="card p-3">
<strong>USD → PLN (last 20)</strong>
<div id="usdChart" class="chart"></div>
</div>
</div>
<div class="col-lg-6">
<div class="card p-3">
<strong>CHF → PLN (last 20)</strong>
<div id="chfChart" class="chart"></div>
</div>
</div>
</div>

</div>

<footer class="text-center py-3">
<b>Ozodbek Karimov – 69019</b>
</footer>

<script>
const layout={paper_bgcolor:'transparent',plot_bgcolor:'transparent',margin:{t:30}};
const cfg={displayModeBar:false,responsive:true};

Plotly.newPlot('salesChart',[{x:<?=json_encode($salesMonths)?>,y:<?=json_encode($salesValues)?>,type:'bar'}],layout,cfg);
Plotly.newPlot('userChart',[{x:<?=json_encode($userDays)?>,y:<?=json_encode($userCounts)?>,type:'bar'}],layout,cfg);
Plotly.newPlot('trafficChart',[{labels:<?=json_encode($trafficLabels)?>,values:<?=json_encode($trafficValues)?>,type:'pie',hole:.4}],layout,cfg);
Plotly.newPlot('productChart',[{labels:<?=json_encode($productLabels)?>,values:<?=json_encode($productValues)?>,type:'pie',hole:.4}],layout,cfg);

Plotly.newPlot('usdChart',[{
 x:<?=json_encode($usdX)?>,
 y:<?=json_encode($usdY)?>,
 mode:'lines+markers',
 line:{shape:'spline'}
}],layout,cfg);

Plotly.newPlot('chfChart',[{
 x:<?=json_encode($chfX)?>,
 y:<?=json_encode($chfY)?>,
 mode:'lines+markers',
 line:{shape:'spline'}
}],layout,cfg);
</script>

</body>
</html>

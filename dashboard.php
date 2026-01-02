<?php
date_default_timezone_set("Europe/Warsaw");

/* ===================== API FETCH ===================== */
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

/* ===================== BASE URL ===================== */
$BASE = "https://project-php-rest.onrender.com/api";

/* ===================== FETCH DATA ===================== */
$salesData    = fetchApi("$BASE/sales_monthly.php");
$userData     = fetchApi("$BASE/user_growth.php");
$trafficData  = fetchApi("$BASE/traffic_sources.php");
$productData  = fetchApi("$BASE/product_categories.php");
$currencyData = fetchApi("$BASE/currency_last20.php");
$clapsData    = fetchApi("$BASE/get_claps.php");

/* ===================== DATA PREP ===================== */
$salesMonths = array_column($salesData,'month');
$salesValues = array_map('intval',array_column($salesData,'value'));

$userDays   = array_column($userData,'day');
$userCounts = array_map('intval',array_column($userData,'users'));

$trafficLabels = array_column($trafficData,'source');
$trafficValues = array_map('intval',array_column($trafficData,'value'));

$productLabels = array_column($productData,'category');
$productValues = array_map('intval',array_column($productData,'value'));

$currencyDates = array_column($currencyData,'date');
$currencyRates = array_map('floatval',array_column($currencyData,'rate'));

$currentClaps = $clapsData['claps'] ?? 0;

/* ===================== KPI LOGIC ===================== */
$salesDelta = count($salesValues) >= 2
    ? end($salesValues) - prev($salesValues)
    : 0;

$userDelta = count($userCounts) >= 2
    ? end($userCounts) - prev($userCounts)
    : 0;

$totalSales = array_sum($salesValues);
$totalUsers = array_sum($userCounts);

$topTraffic = $trafficLabels[array_search(max($trafficValues), $trafficValues)] ?? '-';
$topProduct = $productLabels[array_search(max($productValues), $productValues)] ?? '-';

$lastUpdate = date("d M Y, H:i");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Enterprise Analytics Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>

<style>
:root{
 --bg:#f5f7fb;--card:#fff;--text:#1f2937;--muted:#6b7280
}
body.dark{
 --bg:#0f172a;--card:#1e293b;--text:#e5e7eb;--muted:#9ca3af
}
body{
 background:var(--bg);
 color:var(--text);
 font-family:system-ui
}
.card{
 background:var(--card);
 border:none;
 border-radius:16px
}
.chart-box{height:320px}
</style>
</head>

<body class="d-flex flex-column min-vh-100">

<div class="container py-5 flex-grow-1">

<div class="text-center mb-4">
<h2><b>Enterprise Analytics Dashboard</b></h2>
<p class="text-muted">
REST APIs • PostgreSQL • Render<br>
Last updated: <?=$lastUpdate?>
</p>
</div>

<!-- KPI -->
<div class="row g-4 mb-5">
<div class="col-lg-3">
<div class="card p-3 text-center shadow-sm">
<small>Total Sales</small>
<h4><b><?=$totalSales?> PLN</b></h4>
<small><?=($salesDelta>=0?'+':'')?><?=$salesDelta?></small>
</div>
</div>

<div class="col-lg-3">
<div class="card p-3 text-center shadow-sm">
<small>Total Users</small>
<h4><b><?=$totalUsers?></b></h4>
<small><?=($userDelta>=0?'+':'')?><?=$userDelta?></small>
</div>
</div>

<div class="col-lg-3">
<div class="card p-3 text-center shadow-sm">
<small>Traffic Sources</small>
<h4><?=count($trafficLabels)?></h4>
<small>Top: <?=$topTraffic?></small>
</div>
</div>

<div class="col-lg-3">
<div class="card p-3 text-center shadow-sm">
<small>Claps</small>
<h4 id="clapCount"><b><?=$currentClaps?></b></h4>
<button class="btn btn-sm btn-outline-primary mt-2" onclick="addClap()">👏 Clap</button>
</div>
</div>
</div>

<!-- CHARTS -->
<div class="row g-4">
<div class="col-lg-6">
<div class="card p-3">
<strong>Monthly Sales</strong>
<div id="salesChart" class="chart-box"></div>
</div>
</div>

<div class="col-lg-6">
<div class="card p-3">
<strong>User Growth</strong>
<div id="userChart" class="chart-box"></div>
</div>
</div>

<div class="col-lg-6">
<div class="card p-3">
<strong>Traffic Sources</strong>
<div id="trafficChart" class="chart-box"></div>
</div>
</div>

<div class="col-lg-6">
<div class="card p-3">
<strong>Product Categories</strong>
<div id="productChart" class="chart-box"></div>
</div>
</div>

<div class="col-lg-12">
<div class="card p-3">
<strong>Currency Rate (Last 20)</strong>
<div id="currencyChart" class="chart-box"></div>
</div>
</div>
</div>

</div>

<footer class="text-center py-3">
<b>Ozodbek Karimov – 69019</b>
</footer>

<script>
const layout={
 paper_bgcolor:'transparent',
 plot_bgcolor:'transparent',
 margin:{t:30}
};
const cfg={displayModeBar:false,responsive:true};

Plotly.newPlot('salesChart',[{
 x:<?=json_encode($salesMonths)?>,
 y:<?=json_encode($salesValues)?>,
 type:'bar'
}],layout,cfg);

Plotly.newPlot('userChart',[{
 x:<?=json_encode($userDays)?>,
 y:<?=json_encode($userCounts)?>,
 type:'bar'
}],layout,cfg);

Plotly.newPlot('trafficChart',[{
 labels:<?=json_encode($trafficLabels)?>,
 values:<?=json_encode($trafficValues)?>,
 type:'pie',
 hole:.4
}],layout,cfg);

Plotly.newPlot('productChart',[{
 labels:<?=json_encode($productLabels)?>,
 values:<?=json_encode($productValues)?>,
 type:'pie',
 hole:.4
}],layout,cfg);

Plotly.newPlot('currencyChart',[{
 x:<?=json_encode($currencyDates)?>,
 y:<?=json_encode($currencyRates)?>,
 type:'scatter',
 mode:'lines+markers',
 line:{shape:'spline'}
}],layout,cfg);

function addClap(){
 fetch("<?=$BASE?>/add_clap.php")
   .then(r=>r.json())
   .then(d=>{
     document.getElementById("clapCount").innerText=d.claps;
   });
}
</script>

</body>
</html>

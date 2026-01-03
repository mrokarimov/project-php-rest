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

/* ===================== NBP CURRENCY ===================== */
function getRates($currency) {
    $url = "https://api.nbp.pl/api/exchangerates/rates/a/$currency/last/20/?format=json";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true) ?? [];
}

/* ===================== BASE URL (RENDER) ===================== */
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

/* ===================== CURRENCY PREP ===================== */
$usdTimes  = array_column($usd['rates'] ?? [], 'effectiveDate');
$usdRates  = array_column($usd['rates'] ?? [], 'mid');

$chfTimes = array_column($chf['rates'] ?? [], 'effectiveDate');
$chfRates = array_column($chf['rates'] ?? [], 'mid');

/* ===================== CURRENCY TABLE ===================== */
$currencyData = [];

foreach ($usd['rates'] ?? [] as $r) {
    $currencyData[] = [
        'currency' => 'USD',
        'rate' => $r['mid'],
        'timestamp' => $r['effectiveDate']
    ];
}
foreach ($chf['rates'] ?? [] as $r) {
    $currencyData[] = [
        'currency' => 'CHF',
        'rate' => $r['mid'],
        'timestamp' => $r['effectiveDate']
    ];
}

/* ===================== KPI LOGIC ===================== */
$totalSales = array_sum($salesValues);
$totalUsers = array_sum($userCounts);
$totalClaps = $clapsData['total'] ?? 0;

$salesDelta = count($salesValues) >= 2
    ? $salesValues[count($salesValues)-1] - $salesValues[count($salesValues)-2]
    : 0;

$userDelta = count($userCounts) >= 2
    ? $userCounts[count($userCounts)-1] - $userCounts[count($userCounts)-2]
    : 0;

$topTrafficPercent = max($trafficValues);
$topProductCount  = max($productValues);

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
 --bg:#f5f7fb; --card:#fff; --text:#1f2937; --muted:#6b7280;
}
body.dark{
 --bg:#0f172a; --card:#1e293b; --text:#e5e7eb; --muted:#9ca3af;
}
body{
 background:var(--bg);
 color:var(--text);
 font-family:system-ui;
 transition:.25s;
}
body, body *{color:var(--text)}
.text-muted, small{color:var(--muted)!important}
.card{background:var(--card);border:none;border-radius:16px}
.chart-box{height:320px}
footer{background:var(--card);border-top:1px solid rgba(0,0,0,.1)}
</style>
</head>

<body class="d-flex flex-column min-vh-100">

<button id="themeBtn"
 class="btn btn-sm btn-outline-secondary position-fixed end-0 m-3"
 onclick="toggleTheme()">🌙 Dark</button>

<div class="container py-5 flex-grow-1">

<div class="text-center mb-4">
<h2><b>Enterprise Analytics Dashboard</b></h2>
<p class="text-muted">
<b>REST APIs • PostgreSQL • Render</b><br>
Last updated: <?=$lastUpdate?>
</p>
</div>

<!-- KPI -->
<div class="row g-4 mb-5">

<div class="col-lg-3">
<div class="card p-3 text-center shadow-sm">
<small><b>Total Sales</b></small>
<h4><b><?=$totalSales?> PLN</b></h4>
<small><b><?=($salesDelta>=0?'+':'')?><?=$salesDelta?></b></small>
</div>
</div>

<div class="col-lg-3">
<div class="card p-3 text-center shadow-sm">
<small><b>Total Users</b></small>
<h4><b><?=$totalUsers?></b></h4>
<small><b><?=($userDelta>=0?'+':'')?><?=$userDelta?></b></small>
</div>
</div>

<div class="col-lg-3">
<div class="card p-3 text-center shadow-sm">
<small><b>Traffic Sources</b></small>
<h4><b><?=count($trafficLabels)?></b></h4>
<small><b><?=$topTraffic?> (<?=$topTrafficPercent?>%)</b></small>
</div>
</div>

<div class="col-lg-3">
<div class="card p-3 text-center shadow-sm">
<small><b>Product Categories</b></small>
<h4><b><?=count($productLabels)?></b></h4>
<small><b><?=$topProduct?> (<?=$topProductCount?>)</b></small>
</div>
</div>

</div>

<!-- ===== CURRENCY CHART ===== -->
<div class="row mb-5">
<div class="col-lg-12">
<div class="card p-3 shadow-sm">
<div class="d-flex justify-content-between mb-2">
<strong>Currency Exchange – Last 20</strong>
<button class="btn btn-sm btn-outline-primary"
 data-bs-toggle="modal"
 data-bs-target="#currencyModal">Show table</button>
</div>
<div id="currencyChart" class="chart-box"></div>
</div>
</div>
</div>
<!-- ===== 4 MAIN CHARTS ===== -->
<div class="row g-4">

<?php
$charts = [
  ["salesChart","Monthly Sales","salesModal",$salesData],
  ["userChart","User Growth","userModal",$userData],
  ["trafficChart","Traffic Sources","trafficModal",$trafficData],
  ["productChart","Product Categories","productModal",$productData]
];
foreach($charts as $c):
?>
<div class="col-lg-6">
<div class="card p-3 shadow-sm">
<div class="d-flex justify-content-between mb-2 gap-2">
<strong><?=$c[1]?></strong>
<div class="d-flex gap-2">
<button class="btn btn-sm btn-outline-primary"
 data-bs-toggle="modal"
 data-bs-target="#<?=$c[2]?>">Show table</button>

<button class="btn btn-sm btn-outline-success"
 onclick="openTestDialog('<?=$c[1]?>','<?=$c[0]?>')">
 Show test
</button>
</div>
</div>
<div id="<?=$c[0]?>" class="chart-box"></div>
</div>
</div>
<?php endforeach; ?>

</div>
</div>

<!-- ===== FOOTER ===== -->
<footer class="text-center py-4">
  <div class="fw-semibold mb-2">
    Ozodbek Karimov – 69019
  </div>

  <div class="clap-box" onclick="addClap()">
    <span class="clap-icon">👏</span>
    <span class="clap-text">Claps</span>
    <span class="clap-count" id="clapCount">0</span>
  </div>
</footer>

<!-- ===== API TEST MODAL ===== -->
<div class="modal fade" id="testModal" tabindex="-1">
<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
<div class="modal-content">

<div class="modal-header">
<h5 id="testTitle">API Test</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<div class="card p-3 mb-3">
<strong>Test Case</strong>
<pre id="testCaseText" class="mb-2"></pre>
<hr>
<small class="text-muted">
<b>HTTP Method:</b> GET<br>
<b>Endpoint:</b><br>
<code id="testApiUrl"></code>
</small>
</div>

<button class="btn btn-success mb-3" onclick="runApiTest()">▶ Run Test</button>

<div id="testResult"
 style="display:none;background:#0b1220;color:#e5e7eb;
 padding:16px;border-radius:12px;font-family:monospace;white-space:pre">
</div>

</div>
</div>
</div>
</div>

<!-- ===== DATA TABLE MODALS ===== -->
<?php foreach($charts as $c): ?>
<div class="modal fade" id="<?=$c[2]?>" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
<div class="modal-content">

<div class="modal-header">
<h5><?=$c[1]?> – Data Table</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<table class="table table-striped">
<thead>
<tr>
<?php foreach(array_keys($c[3][0] ?? []) as $h) echo "<th>$h</th>"; ?>
</tr>
</thead>
<tbody>
<?php foreach($c[3] as $r): ?>
<tr>
<?php foreach($r as $v) echo "<td>$v</td>"; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

</div>
</div>
</div>
<?php endforeach; ?>

<!-- ===== CURRENCY TABLE MODAL ===== -->
<div class="modal fade" id="currencyModal" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
<div class="modal-content">

<div class="modal-header">
<h5>Currency – Last 20 Quotes</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<table class="table table-striped">
<thead>
<tr>
<th>Currency</th>
<th>Rate</th>
<th>Date</th>
</tr>
</thead>
<tbody>
<?php foreach($currencyData as $r): ?>
<tr>
<td><?=$r['currency']?></td>
<td><?=$r['rate']?></td>
<td><?=$r['timestamp']?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* ===================== API TEST CONFIG ===================== */

const BASE_URL = "https://project-php-rest.onrender.com/api";

const testCases = {

 salesChart:{
  api:`${BASE_URL}/sales_monthly.php`,
  tests:[
   {name:"HTTP 200",check:(r,d)=>r.status===200},
   {name:"Array not empty",check:(r,d)=>Array.isArray(d)&&d.length>0},
   {name:"Fields month,value",check:(r,d)=>"month"in d[0]&&"value"in d[0]}
  ]
 },

 userChart:{
  api:`${BASE_URL}/user_growth.php`,
  tests:[
   {name:"HTTP 200",check:(r,d)=>r.status===200},
   {name:"Has day,users",check:(r,d)=>"day"in d[0]&&"users"in d[0]}
  ]
 },

 trafficChart:{
  api:`${BASE_URL}/traffic_sources.php`,
  tests:[
   {name:"HTTP 200",check:(r,d)=>r.status===200},
   {name:"Sum = 100",check:(r,d)=>Math.round(d.reduce((s,i)=>s+Number(i.value),0))===100}
  ]
 },

 productChart:{
  api:`${BASE_URL}/product_categories.php`,
  tests:[
   {name:"HTTP 200",check:(r,d)=>r.status===200},
   {name:"category,value",check:(r,d)=>"category"in d[0]&&"value"in d[0]}
  ]
 },

 currencyChart:{
  api:`${BASE_URL}/currency_last20.php`,
  tests:[
   {name:"<=20 records",check:(r,d)=>d.length<=40},
   {name:"Rates numeric",check:(r,d)=>d.every(i=>!isNaN(i.rate))}
  ]
 }

};

let currentTest=null;

function openTestDialog(title,id){
 currentTest=testCases[id];
 document.getElementById("testTitle").innerText=title+" – API Tests";
 document.getElementById("testApiUrl").innerText=currentTest.api;
 document.getElementById("testCaseText").innerText=
  currentTest.tests.map((t,i)=>`${i+1}. ${t.name}`).join("\n");
 document.getElementById("testResult").style.display="none";
 new bootstrap.Modal(document.getElementById("testModal")).show();
}

async function runApiTest(){
 const box=document.getElementById("testResult");
 box.style.display="block";
 box.innerHTML="Running tests...\n\n";

 const res=await fetch(currentTest.api);
 const data=await res.json();

 let passed=0;
 currentTest.tests.forEach((t,i)=>{
  const ok=t.check(res,data);
  if(ok) passed++;
  box.innerHTML+=`${ok?"✅":"❌"} ${t.name}\n`;
 });

 box.innerHTML+=`\n${passed}/${currentTest.tests.length} tests passed`;
}

/* ===================== CLAPS ===================== */
async function loadClaps(){
 const r=await fetch(`${BASE_URL}/get_claps.php`);
 const j=await r.json();
 document.getElementById("clapCount").innerText=j.total;
}

async function addClap(){
 await fetch(`${BASE_URL}/add_clap.php`,{method:"POST"});
 loadClaps();
}

loadClaps();
</script>
<script>
/* ===================== CHART DRAW ===================== */

function drawCharts(){
 const dark=document.body.classList.contains('dark');
 const fontColor=dark?'#e5e7eb':'#1f2937';

 const layout={
  paper_bgcolor:'transparent',
  plot_bgcolor:'transparent',
  font:{color:fontColor},
  margin:{t:40,l:40,r:30,b:40}
 };

 const cfg={
  displayModeBar:false,
  displaylogo:false,
  responsive:true
 };

 /* SALES */
 Plotly.newPlot(
  'salesChart',
  [{
    x:<?=json_encode($salesMonths)?>,
    y:<?=json_encode($salesValues)?>,
    type:'bar',
    marker:{color:'#3b82f6'}
  }],
  layout,
  cfg
 );

 /* USERS */
 Plotly.newPlot(
  'userChart',
  [{
    x:<?=json_encode($userDays)?>,
    y:<?=json_encode($userCounts)?>,
    type:'bar',
    marker:{color:'#22c55e'}
  }],
  layout,
  cfg
 );

 /* TRAFFIC */
 Plotly.newPlot(
  'trafficChart',
  [{
    labels:<?=json_encode($trafficLabels)?>,
    values:<?=json_encode($trafficValues)?>,
    type:'pie',
    hole:.45
  }],
  layout,
  cfg
 );

 /* PRODUCTS */
 Plotly.newPlot(
  'productChart',
  [{
    labels:<?=json_encode($productLabels)?>,
    values:<?=json_encode($productValues)?>,
    type:'pie',
    hole:.45
  }],
  layout,
  cfg
 );

 /* CURRENCY */
 Plotly.newPlot(
  'currencyChart',
  [
   {
    x:<?=json_encode($usdTimes)?>,
    y:<?=json_encode($usdRates)?>,
    mode:'lines+markers',
    name:'USD → PLN',
    line:{color:'#2563eb'}
   },
   {
    x:<?=json_encode($chfTimes)?>,
    y:<?=json_encode($chfRates)?>,
    mode:'lines+markers',
    name:'CHF → PLN',
    line:{color:'#16a34a'}
   }
  ],
  layout,
  cfg
 );
}

/* ===================== THEME ===================== */

function toggleTheme(){
 const dark=document.body.classList.toggle('dark');
 localStorage.setItem('theme',dark?'dark':'light');
 document.getElementById('themeBtn').innerText=dark?'☀ Light':'🌙 Dark';
 drawCharts();
}

if(localStorage.getItem('theme')==='dark'){
 document.body.classList.add('dark');
 document.getElementById('themeBtn').innerText='☀ Light';
}

/* ===================== JSON SYNTAX ===================== */

function syntaxHighlight(json){
 json=JSON.stringify(json,null,2)
  .replace(/&/g,'&amp;')
  .replace(/</g,'&lt;')
  .replace(/>/g,'&gt;');

 return json.replace(
  /("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+)/g,
  m=>{
   let cls='number';
   if(/^"/.test(m)) cls=/:$/.test(m)?'key':'string';
   else if(/true|false/.test(m)) cls='boolean';
   else if(/null/.test(m)) cls='null';
   return `<span class="${cls}">${m}</span>`;
  }
 );
}

/* ===================== INIT ===================== */

drawCharts();
</script>

</body>
</html>

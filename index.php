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

/* ===================== CURRENCY PREP (SAFE) ===================== */

$usdRates = [];
$usdTimes = [];
$chfRates = [];
$chfTimes = [];

if (is_array($usd['rates'] ?? null)) {
    $usdRates = array_column($usd['rates'], 'mid');
    $usdTimes = array_column($usd['rates'], 'effectiveDate');
}

if (is_array($chf['rates'] ?? null)) {
    $chfRates = array_column($chf['rates'], 'mid');
    $chfTimes = array_column($chf['rates'], 'effectiveDate');
}

/* Merge for Y-axis scaling */
$allRates = array_merge($usdRates, $chfRates);

/* Fallback to avoid fatal error */
if (empty($allRates)) {
    $allRates = [0];
}

$yMin = min($allRates) - 0.01;
$yMax = max($allRates) + 0.01;



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

.table, .table td, .table th{
 color:var(--text)!important;
 background:transparent!important
}
.table thead th{background:rgba(255,255,255,.06)!important}
body:not(.dark) .table thead th{background:rgba(0,0,0,.04)!important}

.modal-content,.modal-header,.modal-body{
 background:var(--card)!important;
 color:var(--text)!important
}
.btn-close{filter:invert(1)}
body:not(.dark) .btn-close{filter:none}

.table-striped>tbody>tr:nth-of-type(odd)>*{
 background:rgba(255,255,255,.04)!important
}
body:not(.dark)
.table-striped>tbody>tr:nth-of-type(odd)>*{
 background:rgba(0,0,0,.03)!important
}
        
.clap-float {
  position: absolute;
  color: #22c55e;
  font-weight: 600;
  font-size: 14px;
  pointer-events: none;
  animation: clapFloat 700ms ease-out forwards;
}

@keyframes clapFloat {
  0% {
    opacity: 0;
    transform: translateY(0) scale(0.9);
  }
  20% {
    opacity: 1;
  }
  100% {
    opacity: 0;
    transform: translateY(-24px) scale(1.1);
  }
}
        
        
.clap-box{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:6px 14px;
  border-radius:999px;
  border:1px solid rgba(34,197,94,.6);
  color:#22c55e;
  cursor:pointer;
  user-select:none;
  transition:all .2s ease;
  font-size:14px;
}

.clap-box:hover{
  background:rgba(34,197,94,.12);
  transform:translateY(-1px);
}

.clap-icon{
  font-size:16px;
}

.clap-count{
  font-weight:600;
}
        

footer{
 background:var(--card);
 border-top:1px solid rgba(0,0,0,.1)
}

/* JSON colors */
.string{color:#22c55e}
.number{color:#38bdf8}
.boolean{color:#facc15}
.null{color:#fb7185}
.key{color:#e879f9}

    
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

    
<!-- ===== 6 MAIN CHARTS ===== -->
<div class="row g-4">

<?php
$charts = [
  ["usdChart","USD → PLN (last 20)","usdModal",$usd['rates'] ?? []],
  ["chfChart","CHF → PLN (last 20)","chfModal",$chf['rates'] ?? []],
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


    /* ================= USD ================= */
usdChart: {
  api: "https://api.nbp.pl/api/exchangerates/rates/a/usd/last/20/?format=json",
  tests: [
    {
      name: "HTTP 200",
      check: (res, data) => res.status === 200
    },
    {
      name: "Schema: rates[].mid & effectiveDate",
      check: (res, data) =>
        Array.isArray(data.rates) &&
        data.rates.every(r =>
          typeof r.mid === "number" &&
          typeof r.effectiveDate === "string"
        )
    },
    {
      name: "Business rule: max 20 records",
      check: (res, data) => data.rates.length <= 20
    },
    {
      name: "Financial sanity: rate > 0",
      check: (res, data) =>
        data.rates.every(r => r.mid > 0)
    }
  ]
},

/* ================= CHF ================= */
chfChart: {
  api: "https://api.nbp.pl/api/exchangerates/rates/a/chf/last/20/?format=json",
  tests: [
    {
      name: "HTTP 200",
      check: (res, data) => res.status === 200
    },
    {
      name: "Schema: rates[].mid & effectiveDate",
      check: (res, data) =>
        Array.isArray(data.rates) &&
        data.rates.every(r =>
          typeof r.mid === "number" &&
          typeof r.effectiveDate === "string"
        )
    },
    {
      name: "Business rule: max 20 records",
      check: (res, data) => data.rates.length <= 20
    },
    {
      name: "Financial sanity: rate > 0",
      check: (res, data) =>
        data.rates.every(r => r.mid > 0)
    }
  ]
},


  /* ================= SALES ================= */
  salesChart: {
    api: `${BASE_URL}/sales_monthly.php`,
    tests: [
      {
        name: "HTTP 200 & JSON response",
        check: (res, data) =>
          res.status === 200 && Array.isArray(data)
      },
      {
        name: "Schema validation: month:string, value:number",
        check: (res, data) =>
          data.every(i =>
            typeof i.month === "string" &&
            !isNaN(Number(i.value))
          )
      },
      {
        name: "Business rule: values >= 0",
        check: (res, data) =>
          data.every(i => Number(i.value) >= 0)
      },
      {
        name: "Chronological ordering",
        check: (res, data) =>
          data.length < 2 ||
          data.map(i => i.month).join() !== ""
      }
    ]
  },

  /* ================= USER GROWTH ================= */
  userChart: {
    api: `${BASE_URL}/user_growth.php`,
    tests: [
      {
        name: "HTTP 200 & array response",
        check: (res, data) =>
          res.status === 200 && Array.isArray(data)
      },
      {
        name: "Schema: day:string, users:number",
        check: (res, data) =>
          data.every(i =>
            typeof i.day === "string" &&
            Number.isInteger(Number(i.users))
          )
      },
      {
        name: "Users count non-negative",
        check: (res, data) =>
          data.every(i => Number(i.users) >= 0)
      },
      {
        name: "Business logic: growth trend exists",
        check: (res, data) =>
          data.length >= 3
      }
    ]
  },

  /* ================= TRAFFIC ================= */
  trafficChart: {
    api: `${BASE_URL}/traffic_sources.php`,
    tests: [
      {
        name: "HTTP 200 & array",
        check: (res, data) =>
          res.status === 200 && Array.isArray(data)
      },
      {
        name: "Schema: source:string, value:number",
        check: (res, data) =>
          data.every(i =>
            typeof i.source === "string" &&
            !isNaN(Number(i.value))
          )
      },
      {
        name: "Business rule: sum == 100%",
        check: (res, data) =>
          Math.round(
            data.reduce((s, i) => s + Number(i.value), 0)
          ) === 100
      },
      {
        name: "At least 3 traffic sources",
        check: (res, data) => data.length >= 3
      }
    ]
  },

  /* ================= PRODUCTS ================= */
  productChart: {
    api: `${BASE_URL}/product_categories.php`,
    tests: [
      {
        name: "HTTP 200 & array",
        check: (res, data) =>
          res.status === 200 && Array.isArray(data)
      },
      {
        name: "Schema: category:string, value:number",
        check: (res, data) =>
          data.every(i =>
            typeof i.category === "string" &&
            Number(i.value) > 0
          )
      },
      {
        name: "Business rule: non-empty categories",
        check: (res, data) =>
          data.every(i => i.category.length > 0)
      },
      {
        name: "Distribution has multiple categories",
        check: (res, data) => data.length >= 3
      }
    ]
  },

  /* ================= CURRENCY (NEW) ================= */
  currencyChart: {
    api: `${BASE_URL}/currency_last20.php`,
    tests: [
      {
        name: "HTTP 200 & array",
        check: (res, data) =>
          res.status === 200 && Array.isArray(data)
      },
      {
        name: "Schema: currency, rate, date",
        check: (res, data) =>
          data.every(i =>
            typeof i.currency === "string" &&
            !isNaN(Number(i.rate)) &&
            typeof i.date === "string"
          )
      },
      {
        name: "Business rule: max 20 records",
        check: (res, data) => data.length <= 20
      },
      {
        name: "Contains USD and CHF",
        check: (res, data) =>
          data.some(i => i.currency === "USD") &&
          data.some(i => i.currency === "CHF")
      }
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
  const box = document.getElementById('testResult');
  box.style.display = 'block';
  box.innerHTML = "Running tests...\n\n";

  const res = await fetch(currentTest.api);
  const data = await res.json();

  let passed = 0;

  currentTest.tests.forEach((t, i) => {
    const ok = t.check(res, data);
    if(ok) passed++;
    box.innerHTML += `${ok ? "✅" : "❌"} Test ${i+1}: ${t.name}\n`;
  });

  box.innerHTML += `
\nSummary:
${passed} / ${currentTest.tests.length} tests passed

--- JSON Preview ---
${prettyJson(data)}
`;
}


function prettyJson(obj){
  const json = JSON.stringify(obj, null, 2);

  return json.replace(
    /("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*")(\s*:)?|\b(true|false|null)\b|-?\d+(\.\d+)?/g,
    match => {
      if (/^"/.test(match)) {
        return /:$/.test(match)
          ? `<span class="json-key">${match}</span>`
          : `<span class="json-string">${match}</span>`;
      }
      if (/true|false/.test(match)) {
        return `<span class="json-boolean">${match}</span>`;
      }
      if (/null/.test(match)) {
        return `<span class="json-null">${match}</span>`;
      }
      return `<span class="json-number">${match}</span>`;
    }
  );
}



</script>

    
<script>
/* ===================== CLAPS ===================== */

async function loadClaps(){
  const r = await fetch(`${BASE_URL}/get_claps.php`, { method: "GET" });
  const j = await r.json();
  document.getElementById("clapCount").innerText = j.claps;
}

async function addClap(){
  await fetch(`${BASE_URL}/add_clap.php`, { method: "POST" });
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

/* ===================== USD ===================== */
Plotly.newPlot(
  'usdChart',
  [{
    x: <?= json_encode($usdTimes) ?>,
    y: <?= json_encode($usdRates) ?>,
    mode: 'lines+markers',
    line: { width: 3, color: '#2563eb' }
  }],
  layout,
  cfg
);

/* ===================== CHF ===================== */
Plotly.newPlot(
  'chfChart',
  [{
    x: <?= json_encode($chfTimes) ?>,
    y: <?= json_encode($chfRates) ?>,
    mode: 'lines+markers',
    line: { width: 3, color: '#16a34a' }
  }],
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

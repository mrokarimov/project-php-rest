<?php
date_default_timezone_set("Europe/Warsaw");

/* ===================== API FETCH ===================== */
function fetchApi($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

$salesData    = fetchApi("http://ozodbekphp.myartsonline.com/FINAL_PROJECT/api/sales_monthly.php");
$userData     = fetchApi("http://ozodbekphp.myartsonline.com/FINAL_PROJECT/api/user_growth.php");
$trafficData  = fetchApi("http://ozodbekphp.myartsonline.com/FINAL_PROJECT/api/traffic_sources.php");
$productData  = fetchApi("http://ozodbekphp.myartsonline.com/FINAL_PROJECT/api/product_categories.php");
$currencyData = fetchApi("http://ozodbekphp.myartsonline.com/FINAL_PROJECT/api/currency_last20.php");

/* ===================== CURRENCY SPLIT ===================== */
$usdRates = $usdTimes = $chfRates = $chfTimes = [];
foreach ($currencyData as $r) {
    if ($r['currency'] === 'USD') { $usdRates[]=$r['rate']; $usdTimes[]=$r['timestamp']; }
    if ($r['currency'] === 'CHF') { $chfRates[]=$r['rate']; $chfTimes[]=$r['timestamp']; }
}

/* ===================== DATA PREP ===================== */
$salesMonths   = array_column($salesData,'month');
$salesValues   = array_map('intval',array_column($salesData,'value'));
$userDays      = array_column($userData,'day');
$userCounts    = array_map('intval',array_column($userData,'users'));
$trafficLabels = array_column($trafficData,'source');
$trafficValues = array_map('intval',array_column($trafficData,'percent'));
$productLabels = array_column($productData,'category');
$productValues = array_map('intval',array_column($productData,'count_value'));

/* ===================== KPI EXTRA LOGIC ===================== */
$salesDelta = count($salesValues) >= 2
    ? $salesValues[count($salesValues)-1] - $salesValues[count($salesValues)-2]
    : 0;

$userDelta = count($userCounts) >= 2
    ? $userCounts[count($userCounts)-1] - $userCounts[count($userCounts)-2]
    : 0;

$topTrafficPercent = max($trafficValues);
$topProductCount  = max($productValues);

$totalSales = array_sum($salesValues);
$totalUsers = array_sum($userCounts);
$lastUpdate = date("d M Y, H:i");

$topTraffic = $trafficLabels[array_search(max($trafficValues), $trafficValues)];
$topProduct = $productLabels[array_search(max($productValues), $productValues)];
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
<b>REST APIs • cURL • Plotly • Bootstrap</b><br>
Last updated: <?=$lastUpdate?>
</p>
</div>

<!-- KPI -->
<div class="row g-4 mb-5">
<div class="col-lg-3">
<div class="card p-3 text-center shadow-sm">
<small><b>Total Sales</b></small>
<h4><b><?=$totalSales?> PLN</b></h4>
<small class="text-muted">
<b><?= $salesDelta >= 0 ? '+' : '' ?><?=$salesDelta?> vs prev month</b>
</small>
</div>
</div>

<div class="col-lg-3">
<div class="card p-3 text-center shadow-sm">
<small><b>Total Users</b></small>
<h4><b><?=$totalUsers?></b></h4>
<small class="text-muted">
<b><?= $userDelta >= 0 ? '+' : '' ?><?=$userDelta?> new users</b>
</small>
</div>
</div>

<div class="col-lg-3">
<div class="card p-3 text-center shadow-sm">
<small><b>Traffic Sources</b></small>
<h4><b><?=count($trafficLabels)?></b></h4>
<small class="text-muted">
<b>Top: <?=$topTraffic?> (<?=$topTrafficPercent?>%)</b>
</small>
</div>
</div>

<div class="col-lg-3">
<div class="card p-3 text-center shadow-sm">
<small><b>Product Categories</b></small>
<h4><b><?=count($productLabels)?></b></h4>
<small class="text-muted">
<b>Top: <?=$topProduct?> (<?=$topProductCount?>)</b>
</small>
</div>
</div>
</div>

<!-- ===== LINE CHART (TOP) ===== -->
<div class="row mb-5">
<div class="col-lg-12">
<div class="card p-3 shadow-sm">
<div class="d-flex justify-content-between mb-2 gap-2">
<strong>Currency Exchange – Last 20</strong>
<div class="d-flex gap-2">
<button class="btn btn-sm btn-outline-primary"
 data-bs-toggle="modal"
 data-bs-target="#currencyModal">Show table</button>
<button class="btn btn-sm btn-outline-success"
 onclick="openTestDialog('Currency Exchange – Last 20','currencyChart')">
 Show test
</button>
</div>
</div>
<div id="currencyChart" class="chart-box"></div>
</div>
</div>
</div>

<!-- ===== 4 CHARTS ===== -->
<div class="row g-4">
<?php
$charts=[
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
<?php endforeach;?>
</div>

</div>

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

<!-- TEST CASE INFO -->
<div class="card p-3 mb-3">
  <strong>Test Case</strong>
  <pre id="testCaseText" class="mb-2"></pre>

  <hr class="my-2">

  <small class="text-muted">
    <b>HTTP Method:</b> GET<br>
    <b>Endpoint:</b><br>
    <code id="testApiUrl"></code>
  </small>
</div>

<button class="btn btn-success mb-3" onclick="runApiTest()">
▶ Run Test
</button>

<!-- RESULT -->
<div id="testResult"
     style="display:none;
            background:#0b1220;
            color:#e5e7eb;
            padding:16px;
            border-radius:12px;
            font-family:monospace;
            white-space:pre">
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
<thead><tr>
<?php foreach(array_keys($c[3][0]) as $h) echo "<th>$h</th>"; ?>
</tr></thead>
<tbody>
<?php foreach($c[3] as $r){
 echo "<tr>";
 foreach($r as $v) echo "<td>$v</td>";
 echo "</tr>";
}?>
</tbody>
</table>
</div>
</div>
</div>
</div>
<?php endforeach; ?>

<div class="modal fade" id="currencyModal" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
<div class="modal-content">
<div class="modal-header">
<h5>Currency – Last 20 Quotes</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<table class="table table-striped">
<thead><tr><th>Currency</th><th>Rate</th><th>Time</th></tr></thead>
<tbody>
<?php foreach($currencyData as $r){
 echo "<tr><td>{$r['currency']}</td><td>{$r['rate']}</td><td>{$r['timestamp']}</td></tr>";
}?>
</tbody>
</table>
</div>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>

/* ===== API TEST LOGIC ===== */

const BASE_URL = "http://ozodbekphp.myartsonline.com/FINAL_PROJECT/api";        

const testCases = {

  /* ===== SALES API ===== */
  salesChart: {
    api: `${BASE_URL}/sales_monthly.php`,
    tests: [
      {
        name: "HTTP status is 200",
        check: (res, data) => res.status === 200
      },
      {
        name: "Response is non-empty array",
        check: (res, data) => Array.isArray(data) && data.length > 0
      },
      {
        name: "Required fields: month, value",
        check: (res, data) =>
          "month" in data[0] && "value" in data[0]
      },
      {
        name: "Sales values are numeric",
        check: (res, data) =>
          data.every(i => !isNaN(Number(i.value)))
      }
    ]
  },

  /* ===== USER API ===== */
  userChart: {
    api: `${BASE_URL}/user_growth.php`,
    tests: [
      {
        name: "HTTP status is 200",
        check: (res, data) => res.status === 200
      },
      {
        name: "Response is non-empty array",
        check: (res, data) => Array.isArray(data) && data.length > 0
      },
      {
        name: "Required fields: day, users",
        check: (res, data) =>
          "day" in data[0] && "users" in data[0]
      },
      {
        name: "Users count is >= 0",
        check: (res, data) =>
          data.every(i => Number(i.users) >= 0)
      }
    ]
  },

  /* ===== TRAFFIC API ===== */
  trafficChart: {
    api: `${BASE_URL}/traffic_sources.php`,
    tests: [
      {
        name: "HTTP status is 200",
        check: (res, data) => res.status === 200
      },
      {
        name: "Response is array",
        check: (res, data) => Array.isArray(data)
      },
      {
        name: "Required fields: source, percent",
        check: (res, data) =>
          "source" in data[0] && "percent" in data[0]
      },
      {
        name: "Percent sum equals 100",
        check: (res, data) =>
          Math.round(
            data.reduce((s, i) => s + Number(i.percent), 0)
          ) === 100
      }
    ]
  },

  /* ===== PRODUCT API ===== */
  productChart: {
    api: `${BASE_URL}/product_categories.php`,
    tests: [
      {
        name: "HTTP status is 200",
        check: (res, data) => res.status === 200
      },
      {
        name: "Response is non-empty array",
        check: (res, data) => Array.isArray(data) && data.length > 0
      },
      {
        name: "Required fields: category, count_value",
        check: (res, data) =>
          "category" in data[0] && "count_value" in data[0]
      },
      {
        name: "All count values > 0",
        check: (res, data) =>
          data.every(i => Number(i.count_value) > 0)
      }
    ]
  },

  /* ===== CURRENCY API ===== */
  currencyChart: {
    api: `${BASE_URL}/currency_last20.php`,
    tests: [
      {
        name: "HTTP status is 200",
        check: (res, data) => res.status === 200
      },
      {
        name: "Max 20 records",
        check: (res, data) => data.length <= 20
      },
      {
        name: "Contains USD or CHF",
        check: (res, data) =>
          data.some(i => i.currency === "USD") &&
          data.some(i => i.currency === "CHF")
      },
      {
        name: "Rates are numeric",
        check: (res, data) =>
          data.every(i => !isNaN(Number(i.rate)))
      }
    ]
  }

};

       

let currentTest = null;

function openTestDialog(title, chartId){
  currentTest = testCases[chartId];

  document.getElementById('testTitle').innerText =
    title + " – API Tests";

  document.getElementById('testCaseText').innerText =
    currentTest.tests
      .map((t, i) => `${i+1}. ${t.name}`)
      .join("\n");

  document.getElementById('testApiUrl').innerText =
    currentTest.api;

  const box = document.getElementById('testResult');
  box.style.display = 'none';
  box.innerHTML = '';

  new bootstrap.Modal(
    document.getElementById('testModal')
  ).show();
}


        
function syntaxHighlight(json){
  json = JSON.stringify(json, null, 2)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;');

  return json.replace(
    /("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+)/g,
    m => {
      let cls='number';
      if(/^"/.test(m)) cls = /:$/.test(m) ? 'key' : 'string';
      else if(/true|false/.test(m)) cls='boolean';
      else if(/null/.test(m)) cls='null';
      return `<span class="${cls}">${m}</span>`;
    }
  );
}
        

async function runApiTest(){
  const box = document.getElementById('testResult');
  box.style.display = 'block';
  box.innerHTML = "Running tests...\n\n";

  try{
    const res = await fetch(currentTest.api);
    const data = await res.json();

    let passed = 0;

    currentTest.tests.forEach((t, i) => {
      let ok = false;
      try {
        ok = t.check(res, data);
      } catch(e){ ok = false; }

      if(ok) passed++;

      box.innerHTML +=
        `${ok ? "✅" : "❌"} Test ${i+1}: ${t.name}\n`;
    });

    box.innerHTML +=
`\nSummary:
${passed} / ${currentTest.tests.length} tests passed

--- JSON Preview ---
${syntaxHighlight(data)}`;

  }catch(e){
    box.innerHTML =
`❌ API call failed
${e.message}`;
  }
}
        
async function loadClaps(){
  const r = await fetch("/FINAL_PROJECT/api/get_claps.php");
  const j = await r.json();
  document.getElementById("clapCount").innerText = j.total;
}

async function addClap(){
  const box = document.querySelector(".clap-box");

  // +1 animation
  const float = document.createElement("span");
  float.className = "clap-float";
  float.innerText = "+1";

  const rect = box.getBoundingClientRect();
  float.style.left = rect.width / 2 + "px";
  float.style.top = "0px";

  box.style.position = "relative";
  box.appendChild(float);

  setTimeout(() => float.remove(), 700);

  // API call
  await fetch("/FINAL_PROJECT/api/add_clap.php", { method: "POST" });
  loadClaps();
}



loadClaps();
       

        
        
function drawCharts(){
 const dark=document.body.classList.contains('dark');
 const c=dark?'#e5e7eb':'#1f2937';
 const layout={paper_bgcolor:'transparent',plot_bgcolor:'transparent',font:{color:c},margin:{t:40}};
 const cfg={displayModeBar:false,displaylogo:false,responsive:true};

 Plotly.newPlot('salesChart',[{x:<?=json_encode($salesMonths)?>,y:<?=json_encode($salesValues)?>,type:'bar'}],layout,cfg);
 Plotly.newPlot('userChart',[{x:<?=json_encode($userDays)?>,y:<?=json_encode($userCounts)?>,type:'bar'}],layout,cfg);
 Plotly.newPlot('trafficChart',[{labels:<?=json_encode($trafficLabels)?>,values:<?=json_encode($trafficValues)?>,type:'pie',hole:.45}],layout,cfg);
 Plotly.newPlot('productChart',[{labels:<?=json_encode($productLabels)?>,values:<?=json_encode($productValues)?>,type:'pie',hole:.45}],layout,cfg);
 Plotly.newPlot('currencyChart',[
  {x:<?=json_encode($usdTimes)?>,y:<?=json_encode($usdRates)?>,mode:'lines+markers',name:'USD'},
  {x:<?=json_encode($chfTimes)?>,y:<?=json_encode($chfRates)?>,mode:'lines+markers',name:'CHF'}
 ],layout,cfg);
}

function toggleTheme(){
 const d=document.body.classList.toggle('dark');
 localStorage.setItem('theme',d?'dark':'light');
 document.getElementById('themeBtn').innerText=d?'☀ Light':'🌙 Dark';
 drawCharts();
}

if(localStorage.getItem('theme')==='dark') document.body.classList.add('dark');
drawCharts();
</script>

</body>
</html>

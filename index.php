<?php
session_start();
if ($_SESSION['UserID'] == 0 || $_SESSION['Status'] < 0) {
    echo("<script>location.href = '/login.php';</script>");
    exit;
}
$UserID = $_SESSION['UserID'];
$UserLevel = $_SESSION['Status'];
$EnableRecoreder = $_SESSION["EnableRecoreder"];

// Handle CSV upload (offline mode)
$csvUploaded = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvfile'])) {
    $targetDir = "/var/www/html/uploads/";
    $targetFile = $targetDir . basename($_FILES["csvfile"]["name"]);
    if (move_uploaded_file($_FILES["csvfile"]["tmp_name"], $targetFile)) {
        $csvUploaded = true;
        $csvFileName = basename($_FILES["csvfile"]["name"]);
    }
}
?>
<!DOCTYPE HTML>
<html lang="en" data-bs-theme="auto">
<head>
    <meta charset="UTF-8">
    <title>Estimate Gold</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="assets/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #101522; color: #fff; }
        .card { background: #181c2b; color: #fff; border: none; }
        .form-select, .form-control { background: #222; color: #fff; }
        .debug-log { background: #111; color: #0f0; font-size: 0.9em; height: 250px; overflow-y: auto; padding: 10px; }
        .chart-container { min-height: 400px; }
        .btn-primary { background: #fa057e; border: none; }
        .btn-primary:hover { background: #c0045e; }
        .hidden { display: none; }
    </style>
</head>
<body>
<header class="navbar sticky-top flex-md-nowrap p-0 shadow" style="background-color:#000000DD;">
    <span class="navbar-brand ms-3 h2 text-pink">Estimate Gold</span>
</header>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12 mb-3 d-flex justify-content-center gap-3">
            <button id="offlineModeBtn" class="btn btn-outline-light active">Offline Mode</button>
            <button id="onlineModeBtn" class="btn btn-outline-light">Online Mode</button>
        </div>
        <!-- Offline Mode Section -->
        <div id="offlineSection" class="col-lg-8">
            <div class="card mb-4 p-3">
                <h4>กราฟราคาซื้อขายทอง (Offline)</h4>
                <form id="csvForm" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="csvfile" class="form-label">เลือกไฟล์ CSV:</label>
                        <input type="file" class="form-control" id="csvfile" name="csvfile" accept=".csv">
                    </div>
                    <button type="submit" class="btn btn-primary">อัปโหลดและ Plot กราฟ</button>
                    <?php if ($csvUploaded): ?>
                        <div class="mt-2 text-success">อัปโหลดไฟล์สำเร็จ: <?php echo htmlspecialchars($csvFileName); ?></div>
                        <script>
                            window.csvFileName = "<?php echo htmlspecialchars($csvFileName); ?>";
                        </script>
                    <?php endif; ?>
                </form>
                <div class="chart-container mt-4">
                    <canvas id="offlineGoldChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Online Mode Section -->
        <div id="onlineSection" class="col-lg-8 hidden">
            <div class="card mb-4 p-3">
                <h4>กราฟราคาซื้อขายทอง (Online)</h4>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="apiKey" class="form-label">API Key:</label>
                        <input type="text" id="apiKey" class="form-control" placeholder="ใส่ API Key">
                    </div>
                    <div class="col-md-4">
                        <label for="apiUrl" class="form-label">API URL:</label>
                        <input type="text" id="apiUrl" class="form-control" placeholder="เช่น https://api.example.com">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button class="btn btn-primary me-2" id="connectBtn">Connect</button>
                        <button class="btn btn-secondary" id="reconnectBtn">Reconnect</button>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label for="rangeOnline" class="form-label">ช่วงเวลา:</label>
                        <select id="rangeOnline" class="form-select">
                            <option value="live">Live</option>
                            <option value="1y">1 ปี</option>
                            <option value="3y">3 ปี</option>
                            <option value="5y">5 ปี</option>
                            <option value="7y">7 ปี</option>
                            <option value="10y">10 ปี</option>
                            <option value="30y">30 ปี</option>
                            <option value="50y">50 ปี</option>
                            <option value="100y">100 ปี</option>
                            <option value="max">Max</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button class="btn btn-success" id="estimateBtn">Estimate ส่งกลับไปที่ Qt (1000 จุด)</button>
                    </div>
                </div>
                <div class="chart-container mt-4">
                    <canvas id="onlineGoldChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Real-time Price Section -->
        <div class="col-lg-4">
            <div class="card mb-4 p-3">
                <h4>ราคาปัจจุบัน (Realtime)</h4>
                <div class="mb-2">
                    <span id="currentPrice" class="h2 text-warning">--</span>
                    <span id="currentTime" class="ms-2 text-secondary"></span>
                </div>
                <hr>
                <h5>Debug</h5>
                <div class="debug-log" id="debugLog"></div>
            </div>
        </div>
        <!-- QT Estimate Section -->
        <div class="col-lg-6">
            <div class="card mb-4 p-3">
                <h5>กราฟประมาณราคาที่ได้มาจาก QT</h5>
                <canvas id="qtEstimateChart"></canvas>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-4 p-3">
                <h5>ข้อมูลความน่าจะเป็น (%) ว่า estimate ตรงไหน</h5>
                <div id="estimateProb"></div>
            </div>
        </div>
    </div>
</div>
<script src="main.js?v=<?php echo time();?>"></script>
<script src="assets/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// === BACKEND HANDLER ===
$action = $_GET['action'] ?? '';

// 1. Endpoint Ping
if ($action === 'ping') {
    header('Content-Type: text/plain');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo 'pong';
    exit;
}

// 2. Endpoint Download (Mengirim data dummy ~10 MB)
if ($action === 'download') {
    header('Content-Type: application/octet-stream');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    $chunk = str_repeat('x', 1024 * 1024); // Chunk 1 MB
    for ($i = 0; $i < 10; $i++) {          // Total 10 MB
        echo $chunk;
        flush();
    }
    exit;
}

// 3. Endpoint Upload (Menerima payload data dari client)
if ($action === 'upload') {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    $bytes = strlen(file_get_contents('php://input'));
    echo json_encode(['status' => 'success', 'bytes' => $bytes]);
    exit;
}
?>

<!-- === FRONTEND INTERFACE & JAVASCRIPT === -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Speedtest Sederhana PHP</title>
    <style>
        body { font-family: Arial, sans-serif; background: #0f172a; color: #fff; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: #1e293b; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); text-align: center; width: 320px; }
        h1 { font-size: 1.5rem; margin-bottom: 1.5rem; color: #38bdf8; }
        .result-box { display: flex; justify-content: space-between; margin: 10px 0; padding: 10px; background: #0f172a; border-radius: 8px; }
        .val { font-weight: bold; color: #4ade80; }
        button { background: #38bdf8; color: #0f172a; border: none; padding: 12px; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 15px; font-size: 1rem; }
        button:disabled { background: #64748b; cursor: not-allowed; }
    </style>
</head>
<body>

<div class="card">
    <h1>Web Speedtest</h1>
    <div class="result-box"><span>Ping</span><span id="ping" class="val">- ms</span></div>
    <div class="result-box"><span>Download</span><span id="download" class="val">- Mbps</span></div>
    <div class="result-box"><span>Upload</span><span id="upload" class="val">- Mbps</span></div>
    <button id="btnStart" onclick="runSpeedtest()">Mulai Tes</button>
</div>

<script>
async function runSpeedtest() {
    const btn = document.getElementById('btnStart');
    btn.disabled = true;
    btn.innerText = 'Menguji...';

    document.getElementById('ping').innerText = '...';
    document.getElementById('download').innerText = '...';
    document.getElementById('upload').innerText = '...';

    // 1. Tes Ping
    const startPing = performance.now();
    await fetch('index.php?action=ping&t=' + Date.now());
    const pingTime = Math.round(performance.now() - startPing);
    document.getElementById('ping').innerText = `${pingTime} ms`;

    // 2. Tes Download
    const startDL = performance.now();
    const response = await fetch('index.php?action=download&t=' + Date.now());
    const reader = response.body.getReader();
    let downloadedBytes = 0;

    while (true) {
        const { done, value } = await reader.read();
        if (done) break;
        downloadedBytes += value.length;
    }

    const durationDL = (performance.now() - startDL) / 1000; // detik
    const speedDL = ((downloadedBytes * 8) / (durationDL * 1000000)).toFixed(2); // Mbps
    document.getElementById('download').innerText = `${speedDL} Mbps`;

    // 3. Tes Upload (Mengirim 5 MB dummy data)
    const uploadSizeMB = 5;
    const dummyData = new Uint8Array(uploadSizeMB * 1024 * 1024);
    const startUL = performance.now();

    await fetch('index.php?action=upload&t=' + Date.now(), {
        method: 'POST',
        body: dummyData
    });

    const durationUL = (performance.now() - startUL) / 1000; // detik
    const speedUL = (((uploadSizeMB * 1024 * 1024) * 8) / (durationUL * 1000000)).toFixed(2); // Mbps
    document.getElementById('upload').innerText = `${speedUL} Mbps`;

    btn.disabled = false;
    btn.innerText = 'Mulai Tes Kembali';
}
</script>

</body>
</html>
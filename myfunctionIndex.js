var wsUri;
var ws;
WebSocketTest();
function WebSocketTest() {
var currentRole;
if ("WebSocket" in window) {
   // Let us open a web socket
   wsUri = "ws://" + location.host + ":1234";
   ws = new WebSocket(wsUri);

   ws.onopen = function() {
	  // Web Socket is connected, send data using send()
	  ws.send("web:home");
   };

   ws.onmessage = function (evt) { 
	  var received_msg = evt.data;
	  processMsg(received_msg);
   };

   ws.onclose = function() { 
	  // websocket is closed.
	  alert("Connection is closed..."); 
   };
} else {

   // The browser doesn't support WebSocket
   alert("WebSocket NOT supported by your Browser!");
}
}


document.addEventListener("DOMContentLoaded", function () {
    // Mode switching
    const offlineBtn = document.getElementById("offlineModeBtn");
    const onlineBtn = document.getElementById("onlineModeBtn");
    const offlineSection = document.getElementById("offlineSection");
    const onlineSection = document.getElementById("onlineSection");

    offlineBtn.onclick = function () {
        offlineBtn.classList.add("active");
        onlineBtn.classList.remove("active");
        offlineSection.classList.remove("hidden");
        onlineSection.classList.add("hidden");
    };
    onlineBtn.onclick = function () {
        onlineBtn.classList.add("active");
        offlineBtn.classList.remove("active");
        onlineSection.classList.remove("hidden");
        offlineSection.classList.add("hidden");
    };

    // Offline Mode: Plot CSV after upload
    if (window.csvFileName) {
        fetch(`/uploads/${window.csvFileName}`)
            .then(res => res.text())
            .then(csv => {
                const rows = csv.split('\n').map(r => r.split(','));
                const labels = rows.map(r => r[0]);
                const prices = rows.map(r => parseFloat(r[1]));
                renderSmoothChart("offlineGoldChart", labels, prices, "ราคาทองจาก CSV");
            });
    }

    // Online Mode: Connect & fetch data
    document.getElementById("connectBtn").onclick = function () {
        const apiKey = document.getElementById("apiKey").value;
        const apiUrl = document.getElementById("apiUrl").value;
        const range = document.getElementById("rangeOnline").value;
        fetchOnlineGold(apiUrl, apiKey, range);
    };
    document.getElementById("reconnectBtn").onclick = function () {
        document.getElementById("connectBtn").click();
    };

    // Estimate button (dummy)
    document.getElementById("estimateBtn").onclick = function () {
        alert("Estimate ส่งกลับไปที่ Qt (1000 จุด)");
        // TODO: ส่งข้อมูลไป Qt ผ่าน WS หรือ API
    };

    // Smooth chart rendering
    function renderSmoothChart(canvasId, labels, prices, labelText) {
        const ctx = document.getElementById(canvasId).getContext("2d");
        if (window[canvasId + "_chart"]) window[canvasId + "_chart"].destroy();
        window[canvasId + "_chart"] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: labelText,
                    data: prices,
                    borderColor: '#fa057e',
                    backgroundColor: 'rgba(250,5,126,0.1)',
                    fill: true,
                    tension: 0.4 // smooth curve
                }]
            },
            options: {
                scales: {
                    x: { ticks: { color: '#fff' } },
                    y: { ticks: { color: '#fff' } }
                },
                plugins: {
                    legend: { labels: { color: '#fff' } }
                }
            }
        });
    }

    // Online fetch (example for Metals-API, replace with your API)
    function fetchOnlineGold(apiUrl, apiKey, range) {
        // Example: GET `${apiUrl}?access_key=${apiKey}&base=USD&symbols=XAU`
        fetch(`${apiUrl}?access_key=${apiKey}&base=USD&symbols=XAU`)
            .then(res => res.json())
            .then(data => {
                // Assume data.rates.XAU is price, and data.timestamp is time
                const price = data.rates?.XAU || "--";
                const time = data.timestamp ? new Date(data.timestamp * 1000).toLocaleString() : "--";
                document.getElementById("currentPrice").textContent = price;
                document.getElementById("currentTime").textContent = time;
                // For demo, plot single point
                renderSmoothChart("onlineGoldChart", [time], [price], "ราคาทอง (Online)");
                logDebug("OnlineAPI: " + JSON.stringify(data));
            })
            .catch(e => logDebug("Error: " + e));
    }

    // Debug log
    function logDebug(msg) {
        const log = document.getElementById("debugLog");
        log.textContent += msg + "\n";
        log.scrollTop = log.scrollHeight;
    }
});

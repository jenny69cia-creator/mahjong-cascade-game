<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulasi Mahjong Ways - Core Mechanic</title>
    <style>
        body {
            background-color: #121212;
            color: white;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }

        h2 { margin-bottom: 5px; color: #ffd700; }
        
        .game-container {
            background: linear-gradient(145deg, #1a4329, #0d2618);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.7);
            border: 3px solid #ffd700;
            text-align: center;
        }

        /* Indikator Multiplier atas */
        .multiplier-bar {
            display: flex;
            justify-content: space-around;
            background: rgba(0, 0, 0, 0.5);
            padding: 8px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .mult { color: #555; transition: all 0.3s; }
        .mult.active { color: #ff3e3e; transform: scale(1.2); text-shadow: 0 0 10px #ff3e3e; }

        /* Grid Slot 5 Kolom x 4 Baris */
        .grid {
            display: grid;
            grid-template-columns: repeat(5, 70px);
            grid-template-rows: repeat(4, 85px);
            gap: 8px;
            background: #06140c;
            padding: 10px;
            border-radius: 10px;
            overflow: hidden;
        }

        /* Desain Ubin / Tile Mahjong */
        .tile {
            background: #fcfbf7;
            color: #111;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: bold;
            box-shadow: inset 0 -5px 0 #dcd9cd, 0 4px 6px rgba(0,0,0,0.3);
            border-bottom: 4px solid #b3b0a5;
            transition: transform 0.2s, opacity 0.3s;
            cursor: default;
            user-select: none;
        }

        /* Warna khusus ubin Mahjong tertentu */
        .tile[data-sym="發"] { color: #008837; }
        .tile[data-sym="中"] { color: #dd2c2c; }
        .tile[data-sym="WILD"] { 
            background: linear-gradient(135deg, #ffd700, #ffa500); 
            color: #fff;
            text-shadow: 1px 1px 2px #000;
            border-bottom: 4px solid #cc8400;
        }

        .tile.pop {
            transform: scale(0);
            opacity: 0;
            transition: transform 0.3s, opacity 0.3s;
        }

        /* Panel Kontrol & Info */
        .dashboard {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0,0,0,0.4);
            padding: 10px 20px;
            border-radius: 8px;
        }
        .info-box { text-align: left; font-size: 0.9rem; }
        .info-box span { font-weight: bold; color: #ffd700; font-size: 1.1rem; }

        button {
            background: linear-gradient(to bottom, #ffe066, #f5b000);
            border: none;
            padding: 12px 30px;
            font-size: 1.2rem;
            font-weight: bold;
            color: #3a2200;
            border-radius: 25px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.4);
            transition: transform 0.1s;
        }
        button:active { transform: scale(0.95); }
        button:disabled { background: #555; color: #888; cursor: not-allowed; }
    </style>
</head>
<body>

<h2>MAHJONG SIMULATOR</h2>
<div class="game-container">
    <div class="multiplier-bar">
        <div class="mult" id="m2">x2</div>
        <div class="mult" id="m4">x4</div>
        <div class="mult" id="m6">x6</div>
        <div class="mult" id="m10">x10</div>
    </div>

    <div class="grid" id="slot-grid"></div>

    <div class="dashboard">
        <div class="info-box">
            Kemenangan: <span id="win-display">0</span><br>
            Total Saldo: <span id="balance-display">10.000</span>
        </div>
        <button id="spin-btn" onclick="startSpin()">SPIN</button>
    </div>
</div>

<script>
    const ROWS = 4;
    const COLS = 5;
    // Daftar simbol simulasi (Karakter Mahjong & Wild)
    const SYMBOLS = ["發", "中", "🀄", "①", "九", "WILD"];
    
    let gridData = [];
    let currentMultiplierIndex = 0;
    const multipliers = [2, 4, 6, 10]; // Sesuai mode free spin di video Anda
    let balance = 10000;
    let isAnimating = false;

    const gridElement = document.getElementById("slot-grid");
    const spinBtn = document.getElementById("spin-btn");

    // Inisialisasi awal saat game dimuat
    function initGame() {
        gridElement.innerHTML = "";
        gridData = [];
        for (let r = 0; r < ROWS; r++) {
            gridData[r] = [];
            for (let c = 0; c < COLS; c++) {
                let randomSym = getRandomSymbol();
                gridData[r][c] = randomSym;
                
                let tile = document.createElement("div");
                tile.className = "tile";
                tile.innerText = randomSym;
                tile.setAttribute("data-sym", randomSym);
                tile.id = `tile-${r}-${c}`;
                gridElement.appendChild(tile);
            }
        }
        updateMultiplierUI(0);
    }

    function getRandomSymbol() {
        // Probabilitas sederhana (WILD dibuat lebih jarang keluar)
        let rand = Math.random();
        if (rand < 0.08) return "WILD";
        return SYMBOLS[Math.floor(Math.random() * (SYMBOLS.length - 1))];
    }

    // Fungsi Utama ketika Tombol Spin Ditekan
    async function startSpin() {
        if (isAnimating) return;
        isAnimating = true;
        spinBtn.disabled = true;
        document.getElementById("win-display").innerText = "0";
        
        // Taruhan (Bet) dipotong per spin
        balance -= 200; 
        document.getElementById("balance-display").innerText = balance;

        currentMultiplierIndex = 0;
        updateMultiplierUI(0);

        // Mengacak ulang isi papan
        await randomizeGridAnimation();
        
        // Loop Mekanisme Kaskade (Pecah -> Turun -> Cek lagi)
        await processCascadeLoop();

        spinBtn.disabled = false;
        isAnimating = false;
    }

    async function randomizeGridAnimation() {
        return new Promise((resolve) => {
            let shuffles = 0;
            let interval = setInterval(() => {
                for (let r = 0; r < ROWS; r++) {
                    for (let c = 0; c < COLS; c++) {
                        let sym = getRandomSymbol();
                        gridData[r][c] = sym;
                        let tile = document.getElementById(`tile-${r}-${c}`);
                        tile.innerText = sym;
                        tile.setAttribute("data-sym", sym);
                    }
                }
                shuffles++;
                if (shuffles > 5) {
                    clearInterval(interval);
                    resolve();
                }
            }, 100);
        });
    }

    // Proses evaluasi kecocokan secara berulang
    async function processCascadeLoop() {
        let hasWin = true;
        while (hasWin) {
            let winningTiles = checkWinningMatches();
            
            if (winningTiles.length > 0) {
                // Tentukan pengali saat ini
                let activeMultiplier = currentMultiplierIndex < multipliers.length ? 
                                       multipliers[currentMultiplierIndex] : multipliers[multipliers.length - 1];
                
                updateMultiplierUI(activeMultiplier);

                // Hitung bayaran kemenangan simulasi
                let winAmount = winningTiles.length * 10 * activeMultiplier;
                balance += winAmount;
                document.getElementById("win-display").innerText = winAmount;
                document.getElementById("balance-display").innerText = balance;

                // Animasi Ubin Pecah/Hilang
                winningTiles.forEach(pos => {
                    let tile = document.getElementById(`tile-${pos.r}-${pos.c}`);
                    tile.classList.add("pop");
                    gridData[pos.r][pos.c] = null; // Kosongkan di data array
                });

                await new Promise(r => setTimeout(r, 400)); // Tunggu animasi pecah

                // Jatuhkan ubin di atasnya ke ruang kosong
                dropTiles();
                await new Promise(r => setTimeout(r, 300)); // Tunggu ubin jatuh

                currentMultiplierIndex++; // Naikkan tingkat pengali untuk kaskade berikutnya
            } else {
                hasWin = false;
            }
        }
    }

    // Algoritma Pencocokan Sederhana (3 atau lebih simbol sama yang bersebelahan secara horizontal/vertikal)
    function checkWinningMatches() {
        let toExplode = [];
        let checked = Array.from({ length: ROWS }, () => Array(COLS).fill(false));

        // Helper untuk mencari kelompok simbol yang sama (Flood Fill)
        function getCluster(r, c, matchSym, cluster) {
            if (r < 0 || r >= ROWS || c < 0 || c >= COLS) return;
            if (checked[r][c] || gridData[r][c] === null) return;
            
            // Logika pencocokan simbol reguler atau dengan bantuan WILD
            if (gridData[r][c] === matchSym || gridData[r][c] === "WILD" || matchSym === "WILD") {
                checked[r][c] = true;
                cluster.push({r, c});
                
                // Jika titik mulai adalah WILD, ganti target kecocokan dengan simbol nyata pertama yang ditemui
                let nextMatch = matchSym === "WILD" ? gridData[r][c] : matchSym;

                getCluster(r + 1, c, nextMatch, cluster);
                getCluster(r - 1, c, nextMatch, cluster);
                getCluster(r, c + 1, nextMatch, cluster);
                getCluster(r, c - 1, nextMatch, cluster);
            }
        }

        for (let r = 0; r < ROWS; r++) {
            for (let c = 0; c < COLS; c++) {
                if (!checked[r][c] && gridData[r][c] !== null) {
                    let cluster = [];
                    getCluster(r, c, gridData[r][c], cluster);
                    // Jika ada 3 atau lebih ubin yang terhubung, kategorikan sebagai menang
                    if (cluster.length >= 3) {
                        toExplode = toExplode.concat(cluster);
                    }
                }
            }
        }
        return toExplode;
    }

    // Mekanisme Menjatuhkan Simbol (Kaskade)
    function dropTiles() {
        // Cek per kolom dari bawah ke atas
        for (let c = 0; c < COLS; c++) {
            let emptySpaces = 0;
            for (let r = ROWS - 1; r >= 0; r--) {
                if (gridData[r][c] === null) {
                    emptySpaces++;
                } else if (emptySpaces > 0) {
                    // Geser data ubin ke bawah sebanyak ruang kosong di bawahnya
                    gridData[r + emptySpaces][c] = gridData[r][c];
                    gridData[r][c] = null;
                }
            }
            // Isi ruang kosong teratas dengan simbol baru baru
            for (let r = 0; r < emptySpaces; r++) {
                gridData[r][c] = getRandomSymbol();
            }
        }

        // Render ulang tampilan visual setelah perubahan data struktur grid
        for (let r = 0; r < ROWS; r++) {
            for (let c = 0; c < COLS; c++) {
                let tile = document.getElementById(`tile-${r}-${c}`);
                tile.innerText = gridData[r][c];
                tile.setAttribute("data-sym", gridData[r][c]);
                tile.classList.remove("pop"); // Reset class animasi
            }
        }
    }

    // Update status warna indikator multiplier di bagian atas layar
    function updateMultiplierUI(activeVal) {
        document.querySelectorAll(".mult").forEach(el => el.classList.remove("active"));
        if (activeVal > 0) {
            let activeEl = document.getElementById(`m${activeVal}`);
            if (activeEl) activeEl.classList.add("active");
        }
    }

    // Jalankan saat halaman pertama kali dibuka
    initGame();
</script>

</body>
</html>

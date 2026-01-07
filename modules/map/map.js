// Функции для модального окна
const numbers = [
    "009 302", "123 045", "065 991", "881 230", "760 003", "510 806", "032 724", "906 111",
    "419 888", "374 500", "088 789", "640 229", "111 111", "222 222", "333 333", "444 444",
    "555 555", "666 666", "777 777", "888 888", "999 999", "000 000", "101 101", "202 202",
    "303 303", "404 404", "505 505", "606 606", "707 707", "808 808", "909 909", "010 010",
    "121 121", "232 232", "343 343", "454 454"
];

let page = 0;
const perPage = 12;

function toggleNumberDropdown() {
    const dropdown = document.getElementById('numberDropdown');
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    if (dropdown.style.display === 'block') renderNumbers();
}

function renderNumbers() {
    const list = document.getElementById('numberList');
    const display = document.getElementById('numberSelectValue');
    const indicator = document.getElementById('pageIndicator');
    list.innerHTML = '';
    const start = page * perPage;
    const slice = numbers.slice(start, start + perPage);

    for (let i = 0; i < slice.length; i += 3) {
        const row = document.createElement('div');
        row.style.display = 'flex';
        row.style.justifyContent = 'space-between';
        row.style.gap = '10px';

        for (let j = 0; j < 3 && i + j < slice.length; j++) {
            const num = slice[i + j];
            const cell = document.createElement('div');
            cell.textContent = num;
            cell.style.cursor = 'pointer';
            cell.style.flex = '1';
            cell.style.textAlign = 'center';
            cell.style.color = '#000';
            cell.onclick = () => {
                display.textContent = num;
                display.style.color = '#000';
                document.getElementById('numberDropdown').style.display = 'none';
            };
            row.appendChild(cell);
        }

        list.appendChild(row);
    }

    indicator.textContent = `Page ${page + 1}`;
}

function prevPage() {
    if (page > 0) {
        page--;
        renderNumbers();
    }
}

function nextPage() {
    if ((page + 1) * perPage < numbers.length) {
        page++;
        renderNumbers();
    }
}

function toggleMapInfo() {
    const modal = document.getElementById('mapInfoModal');
    modal.style.display = modal.style.display === 'block' ? 'none' : 'block';
}

function closeMapInfo() {
    document.getElementById('mapInfoModal').style.display = 'none';
}

function saveMapInfo() {
    const name = document.getElementById("inputName").value;
    const number = document.getElementById("numberSelectValue").textContent;
    const visible = document.getElementById("showMarkerCheckbox").checked;
    console.log("Name:", name);
    console.log("Number:", number);
    console.log("Show marker:", visible);
    closeMapInfo();
}

// Закрытие модального окна при клике вне его
document.addEventListener('click', function(event) {
    const modal = document.getElementById('mapInfoModal');
    const btn = document.querySelector('.open-mapinfo-btn');
    if (modal.style.display === 'block' && 
        !modal.contains(event.target) && 
        !btn.contains(event.target)) {
        closeMapInfo();
    }
});

// Полный код карты
function initMap() {
    // --- config ---
    const CFG = {
        leadTooltip: `<div style="padding:10px 14px; font-family: Arial, 'IBM Plex Sans', sans-serif; font-size:13px; line-height:1.4; color:#333; text-align:center;">
  <div>Customize your marker</div>
  <div>and enjoy more features</div>
  <div>after upgrading to any participant level.</div>
</div>`,
        clusterColors: { fill: "#7B61FF", text: "#FFFFFF", stroke: "#5D47E7" },
        mapHeightPx: 500
    };

    // Hide InfoWindow close (X) globally
    (function() {
        try {
            const s = document.createElement('style');
            s.textContent = '.gm-ui-hover-effect{display:none!important;}';
            document.head.appendChild(s);
        } catch(_) {}
    })();

    // --- SVG icons (fixed; only change marker coordinates) ---
    const SVG = {
        // Lead
        lead: `<svg xmlns="http://www.w3.org/2000/svg" width="36" height="56" viewBox="16 9 42 65">
  <defs>
    <radialGradient id="gL" cx="30%" cy="30%" r="75%">
      <stop offset="0%" stop-color="#6ddf6d"/>
      <stop offset="75%" stop-color="#2e7d32"/>
    </radialGradient>
    <linearGradient id="lL" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%" stop-color="white" stop-opacity="0.4"/>
      <stop offset="50%" stop-color="white" stop-opacity="0"/>
    </linearGradient>
    <filter id="sL" x="-50%" y="-50%" width="200%" height="200%">
      <feDropShadow dx="0" dy="1.5" stdDeviation="1.5" flood-color="rgba(0,0,0,0.3)"/>
    </filter>
  </defs>
  <circle cx="37" cy="30" r="20" fill="url(#gL)" stroke="rgba(255,255,255,0.8)" stroke-width="1" filter="url(#sL)"/>
  <circle cx="37" cy="30" r="20" fill="url(#lL)"/>
  <path d="M25 20 A20 20 0 0 1 41 16" fill="none" stroke="white" stroke-width="4" stroke-linecap="round"/>
  <text x="37" y="38" text-anchor="middle" fill="#ffffff" font-size="16" font-family="Arial" font-weight="bold">YOU</text>
  <rect x="35" y="51" width="3" height="22" fill="#000000"/>
</svg>`,

        // Standard
        standard: `<svg xmlns="http://www.w3.org/2000/svg" width="36" height="56" viewBox="16 9 42 65">
  <defs>
    <radialGradient id="gS" cx="30%" cy="30%" r="75%">
      <stop offset="0%" stop-color="#6faeff"/>
      <stop offset="75%" stop-color="#0036a3"/>
    </radialGradient>
    <linearGradient id="lS" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%" stop-color="white" stop-opacity="0.4"/>
      <stop offset="50%" stop-color="white" stop-opacity="0"/>
    </linearGradient>
    <filter id="sS" x="-50%" y="-50%" width="200%" height="200%">
      <feDropShadow dx="0" dy="1.5" stdDeviation="1.5" flood-color="rgba(0,0,0,0.3)"/>
    </filter>
  </defs>
  <circle cx="37" cy="30" r="20" fill="url(#gS)" stroke="rgba(255,255,255,0.8)" stroke-width="1" filter="url(#sS)"/>
  <circle cx="37" cy="30" r="20" fill="url(#lS)"/>
  <path d="M25 20 A20 20 0 0 1 41 16" fill="none" stroke="white" stroke-width="4" stroke-linecap="round"/>
  <rect x="35" y="51" width="3" height="22" fill="#000000"/>
</svg>`,

        // Premium
        premium: `<svg xmlns="http://www.w3.org/2000/svg" width="36" height="56" viewBox="16 9 42 65">
  <defs>
    <radialGradient id="gP" cx="30%" cy="30%" r="75%">
      <stop offset="0%" stop-color="#ffb066"/>
      <stop offset="75%" stop-color="#cc5200"/>
    </radialGradient>
    <linearGradient id="lP" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%" stop-color="white" stop-opacity="0.4"/>
      <stop offset="50%" stop-color="white" stop-opacity="0"/>
    </linearGradient>
    <filter id="sP" x="-50%" y="-50%" width="200%" height="200%">
      <feDropShadow dx="0" dy="1.5" stdDeviation="1.5" flood-color="rgba(0,0,0,0.3)"/>
    </filter>
  </defs>
  <circle cx="37" cy="30" r="20" fill="url(#gP)" stroke="rgba(255,255,255,0.8)" stroke-width="1" filter="url(#sP)"/>
  <circle cx="37" cy="30" r="20" fill="url(#lP)"/>
  <path d="M25 20 A20 20 0 0 1 41 16" fill="none" stroke="white" stroke-width="4" stroke-linecap="round"/>
  <rect x="35" y="51" width="3" height="22" fill="#000000"/>
</svg>`,

        // Cluster
        cluster: (count) => `<svg xmlns="http://www.w3.org/2000/svg" width="36" height="56" viewBox="16 9 42 65">
  <defs>
    <radialGradient id="gC" cx="30%" cy="30%" r="75%">
      <stop offset="0%" stop-color="#e5c2ff"/>
      <stop offset="75%" stop-color="#7b1fa2"/>
    </radialGradient>
    <linearGradient id="lC" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%" stop-color="white" stop-opacity="0.4"/>
      <stop offset="50%" stop-color="white" stop-opacity="0"/>
    </linearGradient>
    <filter id="sC" x="-50%" y="-50%" width="200%" height="200%">
      <feDropShadow dx="0" dy="1.5" stdDeviation="1.5" flood-color="rgba(0,0,0,0.3)"/>
    </filter>
  </defs>
  <circle cx="37" cy="30" r="20" fill="url(#gC)" stroke="rgba(255,255,255,0.8)" stroke-width="1" filter="url(#sC)"/>
  <circle cx="37" cy="30" r="20" fill="url(#lC)"/>
  <path d="M25 20 A20 20 0 0 1 41 16" fill="none" stroke="white" stroke-width="4" stroke-linecap="round"/>
  <text x="37" y="39" text-anchor="middle" fill="#ffffff" font-size="24" font-family="Arial" font-weight="bold">${count}</text>
  <rect x="35" y="51" width="3" height="22" fill="#000000"/>
</svg>`
    };

    const svgUrl = s => "data:image/svg+xml;charset=UTF-8," + encodeURIComponent(s);
    const iconFor = lvl => {
        const base = (w, h, ax, ay) => ({
            scaledSize: new google.maps.Size(w, h),
            anchor: new google.maps.Point(ax, ay)
        });

        // размеры как в старом коде (24×37, якорь по низу)
        switch((lvl || "").toLowerCase()) {
            case "premium":
                return Object.assign({url: svgUrl(SVG.premium)}, base(24, 37, 11.7, 36.8));
            case "standard":
                return Object.assign({url: svgUrl(SVG.standard)}, base(24, 37, 11.7, 36.8));
            default:
                return Object.assign({url: svgUrl(SVG.lead)}, base(36, 56, 17.57, 55.14));
        }
    };

    // --- participants data (non-uniform distribution) ---
    const PARTICIPANTS = [
        {lat: 37.80, lng: -122.45, level: "standard", name: "Jack Miller", serial: "S/N 303 303"},
        {lat: 40.68, lng: -73.90, level: "standard", name: "Liam Johnson", serial: "S/N 401 401"},
        {lat: 29.69, lng: -95.54, level: "standard", name: "RunwayRebel", serial: "S/N 777 777"},
        {lat: 25.84, lng: -80.30, level: "standard", name: "Sophia Garcia", serial: "S/N 149 149"},
        {lat: 41.83, lng: -87.75, level: "standard", name: "Ethan Wilson", serial: "S/N 313 313"},
        {lat: 42.41, lng: -71.20, level: "premium", name: "WingedBandit", serial: "S/N 909 909"},
        {lat: 37.28, lng: -121.73, level: "premium", name: "Chloe Nguyen", serial: "S/N 402 402"},
        {lat: 52.56, lng: 13.27, level: "standard", name: "Giovanni Rossi", serial: "S/N 301 301"},
        {lat: 45.50, lng: 9.05, level: "standard", name: "Björn Schneider", serial: "S/N 212 212"},
        {lat: 51.55, lng: -0.22, level: "premium", name: "Amelia Clarke", serial: "S/N 123 456"},
        {lat: 41.7000, lng: 45.1000, level: "standard", name: "Giorgi Beridze", serial: "S/N 127 127"},
        {lat: 42.30, lng: 42.62, level: "standard", name: "Irakli Gelashvili", serial: "S/N 110 110"},
        {lat: 31.19, lng: 121.35, level: "standard", name: "Li Wei", serial: "S/N 213 213"},
        {lat: 39.94, lng: 116.28, level: "premium", name: "Zhang Min", serial: "S/N 119 119"},
    ];

    // --- geolocation helpers ---
    const fetchJSON = async (u) => {
        try {
            const r = await fetch(u);
            if (!r.ok) return null;
            return await r.json();
        } catch {
            return null;
        }
    };
    const getIPCenter = async () => {
        const j = await fetchJSON("https://ipapi.co/json");
        return (j && j.latitude && j.longitude) ? {lat: j.latitude, lng: j.longitude} : null;
    };
    const delay = ms => new Promise(r => setTimeout(r, ms));
    const getPrecise = (timeout = 6000) => new Promise(res => {
        if (!navigator.geolocation) return res(null);
        const t = setTimeout(() => res(null), timeout);
        navigator.geolocation.getCurrentPosition(
            p => {
                clearTimeout(t);
                res({lat: p.coords.latitude, lng: p.coords.longitude});
            },
            _ => {
                clearTimeout(t);
                res(null);
            },
            {enableHighAccuracy: true, maximumAge: 0, timeout}
        );
    });

    // --- clustering (purple clusters) ---
    function clusterize(map, markers) {
        if (!(window.markerClusterer && markerClusterer.MarkerClusterer)) return null;
        const renderer = {
            render({count, position}) {
                return new google.maps.Marker({
                    position,
                    icon: {
                        url: svgUrl(SVG.cluster(count)),
                        anchor: new google.maps.Point(17.57, 55.14),
                        scaledSize: new google.maps.Size(36, 56)
                    },
                    optimized: false,
                    zIndex: Number(google.maps.Marker.MAX_ZINDEX) + count
                });
            }
        };
        return new markerClusterer.MarkerClusterer({markers, map, renderer});
    }

    // --- init ---
    (async () => {
        const mapEl = document.getElementById("map");

        // initial center: IP (fallback — Europe)
        let center = {lat: 48.5, lng: 9.0};
        const ip = await getIPCenter();
        if (ip) center = ip;

        const map = new google.maps.Map(mapEl, {
            center, zoom: 5,
            mapTypeControl: false, streetViewControl: false, fullscreenControl: false
        });

        // after 3s: try precise geolocation and update 'YOU' + center
        delay(3000).then(async () => {
            const p = await getPrecise();
            if (p) {
                if (typeof you !== 'undefined' && you && you.setPosition) you.setPosition(p);
                map.setCenter(p);
            }
        });

        // participants (anonymized in Lead mode)
        const others = PARTICIPANTS.map(row => {
            const m = new google.maps.Marker({
                position: {lat: row.lat, lng: row.lng},
                icon: iconFor(row.level),
                map
            });
            const html = `<div style="padding:10px 14px; font-family: Arial, 'IBM Plex Sans', sans-serif; font-size:13px; line-height:1.35; color:#333; text-align:center;">
        <div style="font-weight:600;">${row.name || ''}</div>
        <div style="opacity:.85;">${row.serial || ''}</div>
      </div>`;
            const iw = new google.maps.InfoWindow({content: html, disableAutoPan: true, shouldFocus: false});
            iw.addListener('domready', () => {
                const btn = document.querySelector('.gm-ui-hover-effect');
                if (btn) btn.style.display = 'none';
            });

            // Десктоп: открытие по наведению
            m.addListener('mouseover', () => iw.open({anchor: m, map}));
            m.addListener('mouseout', () => iw.close());

            // Мобайл: открытие по клику / тапу
            m.addListener('click', () => {
                if (iw.getMap()) {
                    iw.close();
                } else {
                    iw.open({anchor: m, map});
                }
            });

            return m;
        });

        // clusters
        clusterize(map, others);

        // gray "YOU" marker for lead + tooltip
        const youPos = ip || center;
        const you = new google.maps.Marker({
            position: youPos,
            icon: iconFor("lead"),
            map,
            zIndex: Number(google.maps.Marker.MAX_ZINDEX) + 1000
        });

        const iw = new google.maps.InfoWindow({
            content: CFG.leadTooltip,
            shouldFocus: false,
            disableAutoPan: true
        });

        iw.addListener('domready', () => {
            const btn = document.querySelector('.gm-ui-hover-effect');
            if (btn) btn.style.display = 'none';
        });

        // Десктоп: показываем по наведению
        you.addListener("mouseover", () => iw.open({anchor: you, map}));
        you.addListener("mouseout", () => iw.close());

        // Мобайл: показываем по клику / тапу
        you.addListener("click", () => {
            if (iw.getMap()) {
                iw.close();
            } else {
                iw.open({anchor: you, map});
            }
        });
    })();
}





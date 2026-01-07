<!-- Тайм-лайн (Десктопная версия) -->
<div id="timeline-card-12345">
  <style>
/* Показывать только при ширине экрана ≥1000px */
@media (max-width: 999px) {
  #timeline-card-12345 { display: none !important; }
}

/* Десктопные стили */
#timeline-card-12345 {
  position: relative;
  height: 50vh;
  width: 100%;
  margin: 0;
  padding: 30px;
  font-family: 'IBM Plex Sans', sans-serif;
  background: #f8f9fa;
  overflow: hidden;
}

#timeline-card-12345::before {
  content:"";
  position:absolute;
  left:0%;
  top:0;
  transform:translateX(-50%);
  width:100%;
  height:100%;
  background:#f8f9fa;
  z-index:0;
}

#timeline-card-12345 .tl-cards-container {
  max-width: 1600px;
  margin: 0 auto;
  position: relative;
  z-index: 1;
  height: 100%;
  display: flex;
  gap: 80px;
  justify-content: center;
  align-items: stretch;
  margin-top: 40px;
}

#timeline-card-12345 .tl-card {
  flex: 1;
  background: #f8f9fa;
  box-shadow: none;
  border-radius: 12px;
  padding: 40px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  overflow: hidden;
}

#timeline-card-12345 .tl-card-inner {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
}

#timeline-card-12345 .tl-card h2 {
  font-family: 'Playfair Display', serif;
  font-size: 32px;
  font-weight: 600;
  color: #000;
  text-align: center;
  margin: 0 0 30px;
}

#timeline-card-12345 .tl-timeline-outer-container {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: 10px;
}

#timeline-card-12345 .tl-timeline-wrapper {
  transform: scale(0.8);
  transform-origin: top center;
  width: 830px;
  margin: 0 auto;
}

#timeline-card-12345 .tl-timeline-container {
  position: relative;
  width: 830px;
  height: 320px;
  overflow-y: auto;
  overflow-x: hidden; /* Изменено с visible на hidden */
  margin: 0 auto;
  border-radius: 8px;
}

#timeline-card-12345 .tl-timeline-list {
  height: 100%;
  overflow-y: scroll;
  overflow-x: hidden; /* Добавлено для предотвращения горизонтального скролла */
  scroll-snap-type: y mandatory;
  margin: 0;
  padding: 140px 0;
  box-sizing: border-box;
  scroll-behavior: smooth;
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  scrollbar-width: thin;
  scrollbar-color: #ccc transparent;
  scrollbar-gutter: stable both-edges;
  position: relative;
  z-index: 0;
}

#timeline-card-12345 .tl-timeline-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  scroll-snap-align: center;
  font-size: 20px;
  color: #888;
  font-weight: 300;
  width: 800px;
  margin: 0 auto;
  box-sizing: border-box; /* Добавлено для правильного расчета размеров */
}

#timeline-card-12345 .tl-timeline-item .tl-label-col {
  width: 160px;
  font-size: 14px;
  color: green;
  font-weight: 600;
  padding-right: 10px;
  text-align: left;
  display: flex;
  align-items: center;
}

#timeline-card-12345 .tl-timeline-item .tl-text-col {
  flex: 1;
  text-align: right;
  padding-right: 20px;
  white-space: nowrap;
}

#timeline-card-12345 .tl-timeline-item .tl-price-col {
  width: 120px;
  text-align: right;
  visibility: hidden;
  color: #888;
}

#timeline-card-12345 .tl-timeline-item.tl-active {
  font-size: 22px;
  font-weight: 600;
  color: #000;
}

#timeline-card-12345 .tl-timeline-item.tl-active .tl-price-col {
  visibility: visible;
}

#timeline-card-12345 #tl-current-stage {
  background-color: #e7f8e2 !important;
  border: 1px solid #ccc;
  border-radius: 2mm;
  width: 780px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 0 auto;
  position: relative;
  z-index: 1;
  box-sizing: border-box; /* Добавлено для правильного расчета размеров */
}

#timeline-card-12345 .tl-green-indicator {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background:
    radial-gradient(circle at 30% 30%, #6ddf6d 0%, #2e7d32 75%),
    linear-gradient(to bottom, rgba(255,255,255,0.4) 0%, rgba(255,255,255,0) 50%);
  border: 1px solid rgba(255,255,255,0.8);
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.25),
              0 2px 4px rgba(0, 0, 0, 0.3);
  margin-right: 10px;
  position: relative;
}

#timeline-card-12345 .tl-green-indicator::after {
  content: "";
  position: absolute;
  top: 3px;
  left: 4px;
  width: 10px;
  height: 5px;
  background: white;
  border-radius: 50% / 60%;
}

#timeline-card-12345 .tl-highlight-bar {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 780px;
  height: 64px;
  margin-top: -32px;
  margin-left: -390px;
  pointer-events: none;
  border: 1px solid #ccc;
  border-radius: 2mm;
  z-index: 3;
  box-sizing: border-box; /* Добавлено для правильного расчета размеров */
}

/* Стили скроллбара */
#timeline-card-12345 .tl-timeline-list::-webkit-scrollbar { width: 8px; }
#timeline-card-12345 .tl-timeline-list::-webkit-scrollbar-thumb { background-color: #bbb; border-radius: 4px; }
#timeline-card-12345 .tl-timeline-list::-webkit-scrollbar-track { background: transparent; }
  </style>

  <div class="tl-cards-container">
    <!-- Карточка: Таймлайн -->
    <div class="tl-card">
      <div class="tl-card-inner">
        <h2>Kit price vs. progress</h2>
        <div class="tl-timeline-outer-container">
          <div class="tl-timeline-wrapper">
            <div class="tl-timeline-container">
              <div class="tl-highlight-bar"></div>
              <div class="tl-timeline-list" id="tl-timeline">
                <div class="tl-timeline-item"><div class="tl-label-col"></div><div class="tl-text-col">Idea to create Sinbad</div><div class="tl-price-col">$45,000</div></div>
                <div class="tl-timeline-item"><div class="tl-label-col"></div><div class="tl-text-col">Team assembly</div><div class="tl-price-col">$45,000</div></div>
                <div class="tl-timeline-item"><div class="tl-label-col"></div><div class="tl-text-col">Design and engineering</div><div class="tl-price-col">$45,000</div></div>
                <div class="tl-timeline-item"><div class="tl-label-col"></div><div class="tl-text-col">Construction of the first aluminum prototype</div><div class="tl-price-col">$45,000</div></div>
                <div class="tl-timeline-item"><div class="tl-label-col"></div><div class="tl-text-col">Testing of the first prototype</div><div class="tl-price-col">$45,000</div></div>
                <div class="tl-timeline-item"><div class="tl-label-col"></div><div class="tl-text-col">Sale of the metal aircraft project</div><div class="tl-price-col">$45,000</div></div>
                <div class="tl-timeline-item"><div class="tl-label-col"></div><div class="tl-text-col">Re-design of Sinbad's fuselage in composite</div><div class="tl-price-col">$45,000</div></div>
                <div class="tl-timeline-item"><div class="tl-label-col"></div><div class="tl-text-col">Creation of the Sinbad website: gradaeronaut.com</div><div class="tl-price-col">$45,000</div></div>
                <div class="tl-timeline-item"><div class="tl-label-col"></div><div class="tl-text-col">Registration of GRAD Aeronaut LLC</div><div class="tl-price-col">$45,000</div></div>
                <div class="tl-timeline-item"><div class="tl-label-col"></div><div class="tl-text-col">Choosing a factory location in Georgia</div><div class="tl-price-col">$45,000</div></div>
                <div class="tl-timeline-item"><div class="tl-label-col"></div><div class="tl-text-col">Start of composite prototype construction</div><div class="tl-price-col">$45,000</div></div>

                <div class="tl-timeline-item" id="tl-current-stage">
                  <div class="tl-label-col"><span class="tl-green-indicator"></span>WE ARE HERE</div>
                  <div class="tl-text-col">Sales launch: price for the first 100 buyers</div>
                  <div class="tl-price-col">$45,000</div>
                </div>

                <div class="tl-timeline-item"><div class="tl-label-col"></div><div class="tl-text-col">Price for the second 100 buyers</div><div class="tl-price-col">$65,000</div></div>
                <div class="tl-timeline-item"><div class="tl-label-col"></div><div class="tl-text-col">Price for the third 100 buyers</div><div class="tl-price-col">$85,000</div></div>
                <div class="tl-timeline-item"><div class="tl-label-col"></div><div class="tl-text-col">Price for the fourth 100 buyers</div><div class="tl-price-col">$105,000</div></div>
                <div class="tl-timeline-item"><div class="tl-label-col"></div><div class="tl-text-col">Price for all subsequent buyers</div><div class="tl-price-col">$125,000</div></div>
                <div class="tl-timeline-item"><div class="tl-label-col"></div><div class="tl-text-col">Prototype demonstration at an exhibition</div><div class="tl-price-col">$125,000</div></div>
                <div class="tl-timeline-item"><div class="tl-label-col"></div><div class="tl-text-col">Start of serial production</div><div class="tl-price-col">$125,000</div></div>
                <div class="tl-timeline-item"><div class="tl-label-col"></div><div class="tl-text-col">Ramp-up to 2,000 kits per year</div><div class="tl-price-col">$125,000</div></div>
                <div class="tl-timeline-item"><div class="tl-label-col"></div><div class="tl-text-col">Type certification of Sinbad aircraft</div><div class="tl-price-col">$125,000</div></div>
                <div class="tl-timeline-item"><div class="tl-label-col"></div><div class="tl-text-col">Sales launch of certified type Sinbad</div><div class="tl-price-col">$540,000</div></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Скрипт таймлайна
    (function() {
      const container = document.getElementById('timeline-card-12345');
      if (!container) return;
      const list = container.querySelector('#tl-timeline');
      const items = list ? list.querySelectorAll('.tl-timeline-item') : [];
      let timeout;
      
      function updateActive() {
        const timelineContainer = container.querySelector('.tl-timeline-container');
        if (!timelineContainer) return;
        
        const containerRect = timelineContainer.getBoundingClientRect();
        const centerY = containerRect.top + containerRect.height / 2;
        let minDist = Infinity;
        let activeIndex = -1;
        
        items.forEach((item, index) => {
          const rect = item.getBoundingClientRect();
          const itemCenter = rect.top + rect.height / 2;
          const dist = Math.abs(centerY - itemCenter);
          item.classList.remove('tl-active');
          item.style.background = 'transparent';
          
          if (dist < minDist) {
            minDist = dist;
            activeIndex = index;
          }
        });
        
        items.forEach((item, index) => {
          const priceCol = item.querySelector('.tl-price-col');
          if (index === activeIndex) {
            item.classList.add('tl-active');
            if (item.id === 'tl-current-stage') {
              item.style.background = '#e7f8e2';
            }
            if (index >= 11) {
              priceCol.style.color = '#000';
              priceCol.style.fontSize = '22px';
              priceCol.style.fontWeight = '600';
            } else {
              priceCol.style.color = '#888';
              priceCol.style.fontSize = '20px';
              priceCol.style.fontWeight = '300';
            }
          } else {
            priceCol.style.color = '#888';
            priceCol.style.fontSize = '20px';
            priceCol.style.fontWeight = '300';
          }
        });
        
        clearTimeout(timeout);
        timeout = setTimeout(() => {
          const current = container.querySelector('#tl-current-stage');
          if (current && list) {
            const scrollPosition = current.offsetTop - (list.clientHeight / 2) + (current.offsetHeight / 2);
            list.scrollTo({ top: scrollPosition, behavior: 'smooth' });
          }
        }, 10000);
      }
      
      if (list) list.addEventListener('scroll', () => requestAnimationFrame(updateActive));
      
      window.addEventListener('load', () => {
        updateActive();
        setTimeout(() => {
          const current = container.querySelector('#tl-current-stage');
          if (current && list) {
            const scrollPosition = current.offsetTop - (list.clientHeight / 2) + (current.offsetHeight / 2);
            list.scrollTo({ top: scrollPosition, behavior: 'smooth' });
          }
        }, 500);
      });
      
      window.addEventListener('resize', updateActive);
    })();
  </script>
</div>





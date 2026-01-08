<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@100;300;400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@splidejs/splide@latest/dist/css/splide.min.css">
    <style>
        @font-face {
            font-family: 'SinbadExclusive';
            src: url('/assets/fonts/sinbad/SinbadExclusive-Regular.woff2') format('woff2');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #ffffff;
            min-height: 100vh;
            overflow-x: hidden;
            font-weight: normal;
        }

        /* Стили скроллбара */
        ::-webkit-scrollbar-track {
            background: #ffffff;
        }

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #aaa;
        }

        /* Firefox */
        * {
            scrollbar-width: thin;
            scrollbar-color: #ccc #ffffff;
        }

        /* Модульная структура страницы */
        .page-modules {
            display: block;
        }

        .module {
            position: relative;
            width: 100%;
            overflow: hidden;
        }

        .module-scope {
            position: relative;
        }

        /* Hero блок на весь viewport */
        .hero-section {
            position: relative;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
        }

        /* Видео контейнер */
        .hero-video-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            clip-path: inset(0);
            -webkit-clip-path: inset(0);
        }

        .hero-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        /* Текст "Sinbad" - над видео с самолётом */
        .video-content {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
            pointer-events: none;
        }

        .title-top {
            position: absolute;
            top: calc(150px - 3vh);
            left: 50%;
            transform: translateX(-50%);
            width: auto;
            min-width: 100%;
            text-align: center;
        }

        .sinbad-text {
            font-family: 'SinbadExclusive', sans-serif;
            font-size: 180px;
            font-weight: normal;
            color: #b1b1b1;
            letter-spacing: 2px;
            line-height: 1;
            opacity: 0.79;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            white-space: nowrap;
            display: inline-block;
            transform: translateZ(0);
            -webkit-transform: translateZ(0);
            padding-right: 0.4em;
            box-sizing: content-box;
            /* Background signature — low contrast, minimal blur by design */
        }

        @media (max-width: 1024px) {
            .sinbad-text {
                font-size: 135px;
            }
        }

        @media (max-width: 768px) {
            .title-top {
                top: calc(130px - 3vh);
            }
            .sinbad-text {
                font-size: 105px;
            }
        }

        @media (max-width: 480px) {
            .sinbad-text {
                font-size: 75px;
            }
            .title-top {
                top: calc(110px - 3vh);
            }
        }

        /* Второй экран с видео - паспортный стиль */
        .second-screen {
            position: relative;
            width: 100vw;
            height: 90vh;
            background-color: #ffffff;
            overflow: hidden;
        }

        .second-video-container {
            position: absolute;
            top: 15vh;
            right: 0;
            width: 75%;
            height: calc(90vh - 15vh - 11vh);
            z-index: 1;
        }

        .second-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        /* Подпись под вторым видео */
        .second-screen .second-video-caption {
            position: absolute;
            bottom: 5vh;
            left: 0;
            width: 85%;
            text-align: right;
            font-family: 'Inter', sans-serif;
            font-weight: 300 !important;
            font-size: 30px;
            color: #888888;
            margin: 0;
            padding: 0;
            letter-spacing: 0.05em;
            word-spacing: 0.1em;
        }

        /* Блок с рендером */
        .render-section {
            width: 100vw;
            height: 65vh;
            background-color: #ffffff;
            position: relative;
            overflow: hidden;
        }

        .render-image {
            width: 85%;
            height: 100%;
            object-fit: cover;
            object-position: center 33%;
            clip-path: inset(10% 0 0 0);
        }

        /* Контейнер для подписи под рендером */
        .module-render-caption {
            margin-bottom: 15vh;
        }

        .render-caption-wrapper {
            width: 100vw;
            background-color: #ffffff;
            padding: 0;
            margin: 0;
        }

        /* Подпись под рендером */
        .render-caption {
            position: relative;
            width: 85%;
            margin-left: 0;
            text-align: right;
            font-family: 'Inter', sans-serif;
            font-weight: 300;
            font-size: 30px;
            color: #888888;
            padding: 0;
            letter-spacing: 0.05em;
            word-spacing: 0.1em;
        }


        /* Стили для слайдера Splide */
        .splide__slide {
            position: relative;
        }

        .splide__slide::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.35);
            pointer-events: none;
        }

        .splide__slide.is-active::after {
            background: rgba(255, 255, 255, 0);
        }

        .splide__slide img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        #splide-slider .splide__slide p {
            margin-top: 15px;
            font-family: 'Inter', sans-serif;
            font-weight: 300;
            font-size: 18px;
            color: #888888;
            text-align: center;
        }

        /* Якорь точек относительно track */
        .splide__track {
            position: relative;
            max-height: 80vh;
            --dot-scale: clamp(0.6, 1vw, 1);
        }

        /* Стрелки управления - увеличены в 2 раза */
        .splide__arrow {
            width: 4em;
            height: 4em;
        }

        .splide__arrow svg {
            width: 2em;
            height: 2em;
        }

        .splide__pagination {
            position: absolute;
            bottom: 1vh;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 20px;
            z-index: 5;
        }

        .splide__pagination__page {
            width: 16px;
            height: 16px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            box-shadow: 0 0 6px rgba(60, 120, 255, 0.6);
            border: 2px solid #ffffff;
            padding: 0;
            cursor: pointer;
            transition: all 0.3s ease;
            transform: scale(var(--dot-scale));
            transform-origin: center;
        }

        .splide__pagination__page.is-active {
            background: #ffffff;
            box-shadow: 0 0 8px rgba(60, 120, 255, 0.9);
        }

        @media (max-width: 1024px) {
            .splide__track {
                --dot-scale: 0.85;
            }
        }

        @media (max-width: 768px) {
            .splide__track {
                max-height: 60vh;
                --dot-scale: 0.7;
            }
        }

        @media (max-width: 480px) {
            .splide__track {
                --dot-scale: 0.6;
            }
        }

        /* Пространственный аккордеон */
        .spatial-accordion {
            width: 100vw;
            margin-top: 15vh;
            background-color: #ffffff;
            position: relative;
        }

        .spatial-accordion-container {
            display: flex;
            width: 100%;
            padding: 5vw;
            gap: 0;
            overflow: hidden;
            position: relative;
            /* Fallback для старых браузеров */
            height: 0;
            padding-bottom: calc((100% - 10vw) * 9 / 16 + 10vw);
        }

        @supports (aspect-ratio: 16 / 9) {
            .spatial-accordion-container {
                height: auto;
                padding-bottom: 5vw;
                aspect-ratio: 16 / 9;
            }
        }

        .spatial-accordion-section {
            position: relative;
            height: 100%;
            overflow: hidden;
            cursor: pointer;
            transition: width 250ms ease-out;
            flex-shrink: 0;
        }

        .spatial-accordion-section.neutral {
            width: calc(33.333% - 0px);
        }

        .spatial-accordion-section.active {
            width: calc(65% - 0px);
        }

        .spatial-accordion-section.inactive {
            width: calc(17.5% - 0px);
        }

        .spatial-accordion-content {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        /* Контейнер для обрезки медиа */
        .media-frame {
            width: 100%;
            height: 100%;
            overflow: hidden;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Изображения и видео имеют фиксированную ширину больше контейнера */
        .spatial-accordion-image {
            width: 130%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }

        .spatial-accordion-video-wrapper {
            position: relative;
            width: 130%;
            height: 100%;
        }

        .spatial-accordion-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: none;
        }

        .spatial-accordion-video.active {
            display: block;
        }

        .spatial-accordion-poster {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            pointer-events: none;
        }

        .spatial-accordion-section.active .spatial-accordion-poster {
            display: none;
        }

    </style>
</head>
<body>
<?php
include $_SERVER['DOCUMENT_ROOT'].'/menu/menu.php';
?>

<div class="page-modules">
    <!-- Hero блок с видео и текстом -->
    <section class="module module-hero hero-section">
    <!-- Видео самолёта -->
    <div class="hero-video-container">
        <video 
            id="hero-video"
            class="hero-video" 
            autoplay 
            loop 
            muted 
            playsinline
            webkit-playsinline
            preload="auto"
            x5-playsinline
            x5-video-player-type="h5"
            x5-video-player-fullscreen="true"
            poster="https://sinbad-cdn.b-cdn.net/videos/sinbad_video_ex_firstframe.webp">
            <source src="https://sinbad-cdn.b-cdn.net/videos/sinbad_video_ex.mp4" type="video/mp4">
            <source src="https://sinbad-cdn.b-cdn.net/videos/sinbad_video_ex.webm" type="video/webm">
        </video>
    </div>
    
    <!-- Текст "Sinbad" над видео с самолётом -->
    <div class="video-content">
        <div class="title-top">
            <span class="sinbad-text">Sinbad</span>
        </div>
    </div>
    </section>

    <!-- Второй экран с видео -->
    <section class="module module-video second-screen">
    <div class="second-video-container">
        <video
            class="second-video"
            muted
            autoplay
            loop
            playsinline
            preload="metadata"
            poster="https://sinbad-cdn.b-cdn.net/videos/frame-002.webp">
            <source src="https://sinbad-cdn.b-cdn.net/videos/video_interior_black_sinbad.mp4" type="video/mp4">
        </video>
    </div>
    <p class="second-video-caption">Sinbad embodies refined luxury, not motorcycle extremism</p>
    </section>

    <!-- Блок с рендером -->
    <section class="module module-render render-section">
    <img 
        src="https://sinbad-cdn.b-cdn.net/sinbad-renders/10_1920.webp" 
        alt="Sinbad render" 
        class="render-image">
    </section>
    <section class="module module-render-caption">
        <div class="render-caption-wrapper">
            <p class="render-caption">Once seen, Sinbad can never be mistaken for anything else</p>
        </div>
    </section>
</div>

<section style="padding: 15vh 5vw;">
  <div id="splide-slider" class="splide">
    <div class="splide__track">
      <ul class="splide__list">
        <li class="splide__slide">
          <img src="https://sinbad-cdn.b-cdn.net/sinbad-renders/08_1920.webp" alt="Слайд 1">
          <p>Подпись к слайду 1</p>
        </li>
        <li class="splide__slide">
          <img src="https://sinbad-cdn.b-cdn.net/sinbad-renders/15_1920.webp" alt="Слайд 2">
          <p>Подпись к слайду 2</p>
        </li>
        <li class="splide__slide">
          <img src="https://sinbad-cdn.b-cdn.net/sinbad-renders/05_(3)m_1920.webp" alt="Слайд 3">
          <p>Подпись к слайду 3</p>
        </li>
        <li class="splide__slide">
          <img src="https://sinbad-cdn.b-cdn.net/sinbad-renders/02_(3)_1920.webp" alt="Слайд 4">
          <p>Подпись к слайду 4</p>
        </li>
        <li class="splide__slide">
          <img src="https://sinbad-cdn.b-cdn.net/sinbad-renders/03_(5)m_1920.webp" alt="Слайд 5">
          <p>Подпись к слайду 5</p>
        </li>
        <li class="splide__slide">
          <img src="https://sinbad-cdn.b-cdn.net/sinbad-renders/16_1920.webp" alt="Слайд 6">
          <p>Подпись к слайду 6</p>
        </li>
      </ul>
    </div>
  </div>
</section>

<!-- Изображение под слайдером -->
<section style="display: flex; justify-content: center; padding: 15vh 0 0 0;">
  <img 
    src="https://sinbad-cdn.b-cdn.net/sinbad-renders/design_collage2.webp" 
    alt="Design collage" 
    style="width: 90%; height: auto; display: block;">
</section>

<!-- Пространственный аккордеон -->
<section class="spatial-accordion">
    <div class="spatial-accordion-container">
        <!-- Секция Render 1 -->
        <div class="spatial-accordion-section neutral" data-section="0">
            <div class="spatial-accordion-content">
                <div class="media-frame">
                    <img 
                        src="https://sinbad-cdn.b-cdn.net/sinbad-renders/18_1920.webp" 
                        alt="Render 1" 
                        class="spatial-accordion-image">
                </div>
            </div>
        </div>

        <!-- Секция Video -->
        <div class="spatial-accordion-section neutral" data-section="1">
            <div class="spatial-accordion-content">
                <div class="media-frame">
                    <div class="spatial-accordion-video-wrapper">
                        <img 
                            src="https://sinbad-cdn.b-cdn.net/videos/ezgif-frame-001%20(2).webp" 
                            alt="Video poster" 
                            class="spatial-accordion-poster">
                        <video 
                            class="spatial-accordion-video"
                            muted
                            loop
                            playsinline
                            preload="none">
                            <source src="https://sinbad-cdn.b-cdn.net/videos/video_red_in_sky_.mp4" type="video/mp4">
                        </video>
                    </div>
                </div>
            </div>
        </div>

        <!-- Секция Render 2 -->
        <div class="spatial-accordion-section neutral" data-section="2">
            <div class="spatial-accordion-content">
                <div class="media-frame">
                    <img 
                        src="https://sinbad-cdn.b-cdn.net/sinbad-renders/37_1920.webp" 
                        alt="Render 2" 
                        class="spatial-accordion-image">
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/@splidejs/splide@latest/dist/js/splide.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var el = document.getElementById('splide-slider');
  if (!el) return;
  new Splide(el, {
    type: 'loop',
    perPage: 1,
    perMove: 1,
    focus: 'center',
    padding: { left: '10%', right: '10%' },
    gap: '2%',
    arrows: true,
    pagination: true,
    autoHeight: false,
    heightRatio: 9/16,
    autoplay: true,
    interval: 10000,
    pauseOnHover: false,
    pauseOnFocus: false,
    speed: 500
  }).mount();
});
</script>

<script>
    // Агрессивный запуск видео
    function forcePlayVideo(video) {
        if (!video) return;
        
        // Убеждаемся, что видео muted
        video.muted = true;
        video.volume = 0;
        
        // Множественные попытки запуска
        const attempts = [
            function() { return video.play(); },
            function() { 
                video.load();
                return video.play();
            },
            function() {
                video.currentTime = 0;
                return video.play();
            }
        ];
        
        let attemptIndex = 0;
        
        function tryPlay() {
            if (attemptIndex >= attempts.length) {
                console.log('Все попытки запуска видео исчерпаны');
                // Добавляем обработчик клика на весь документ
                const clickHandler = function() {
                    video.play().catch(function(err) {
                        console.log('Ошибка запуска по клику:', err);
                    });
                    document.removeEventListener('click', clickHandler);
                    document.removeEventListener('touchstart', clickHandler);
                    document.removeEventListener('keydown', clickHandler);
                };
                document.addEventListener('click', clickHandler, { once: true });
                document.addEventListener('touchstart', clickHandler, { once: true });
                document.addEventListener('keydown', clickHandler, { once: true });
                return;
            }
            
            attempts[attemptIndex]()
                .then(function() {
                    console.log('Видео запущено успешно');
                })
                .catch(function(error) {
                    console.log('Попытка ' + (attemptIndex + 1) + ' не удалась:', error);
                    attemptIndex++;
                    setTimeout(tryPlay, 200);
                });
        }
        
        // Ждём, пока видео готово к воспроизведению
        if (video.readyState >= 2) {
            tryPlay();
        } else {
            video.addEventListener('loadeddata', tryPlay, { once: true });
            video.addEventListener('canplay', tryPlay, { once: true });
            video.addEventListener('canplaythrough', tryPlay, { once: true });
            // Если ничего не сработало, пробуем через 500ms
            setTimeout(tryPlay, 500);
        }
    }

    function initVideos() {
        // Инициализируем только первое видео
        const heroVideo = document.getElementById('hero-video');
        if (heroVideo) {
            heroVideo.setAttribute('autoplay', '');
            heroVideo.setAttribute('loop', '');
            heroVideo.setAttribute('muted', '');
            heroVideo.setAttribute('playsinline', '');
            heroVideo.muted = true;
            heroVideo.volume = 0;
            forcePlayVideo(heroVideo);
            
            heroVideo.addEventListener('pause', function() {
                if (!document.hidden) {
                    setTimeout(function() {
                        heroVideo.play().catch(function(err) {
                            console.log('Ошибка перезапуска hero-video:', err);
                        });
                    }, 100);
                }
            });
        }
    }

    // Множественные точки входа
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initVideos);
    } else {
        initVideos();
    }

    window.addEventListener('load', function() {
        setTimeout(initVideos, 100);
        setTimeout(initVideos, 500);
        setTimeout(initVideos, 1000);
    });

</script>

<script>
    // Пространственный аккордеон
    (function() {
        const sections = document.querySelectorAll('.spatial-accordion-section');
        const videoSection = document.querySelector('.spatial-accordion-section[data-section="1"]');
        const video = videoSection ? videoSection.querySelector('.spatial-accordion-video') : null;
        const poster = videoSection ? videoSection.querySelector('.spatial-accordion-poster') : null;
        
        let activeSectionIndex = null;

        function activateSection(index) {
            // Если кликнули на уже активную секцию, ничего не делаем
            if (activeSectionIndex === index) {
                return;
            }

            // Обновляем классы всех секций
            sections.forEach((section, i) => {
                section.classList.remove('neutral', 'active', 'inactive');
                
                if (i === index) {
                    section.classList.add('active');
                } else {
                    section.classList.add('inactive');
                }
            });

            // Управление видео
            if (video && poster) {
                if (index === 1) {
                    // Активируем видео секцию
                    // Загружаем видео только при первой активации
                    if (video.readyState === 0) {
                        video.load();
                    }
                    video.classList.add('active');
                    poster.style.display = 'none';
                    
                    // Запускаем видео после небольшой задержки для плавности
                    setTimeout(function() {
                        video.currentTime = 0;
                        video.play().catch(function(err) {
                            console.log('Ошибка запуска видео:', err);
                        });
                    }, 50);
                } else {
                    // Деактивируем видео секцию
                    video.pause();
                    video.currentTime = 0;
                    video.classList.remove('active');
                    poster.style.display = 'block';
                }
            }

            activeSectionIndex = index;
        }

        // Обработчики кликов
        sections.forEach((section, index) => {
            section.addEventListener('click', function() {
                activateSection(index);
            });
        });

        // Инициализация: все секции остаются в нейтральном состоянии (по 33% каждая)
        // Видео не активируется при загрузке страницы
        sections.forEach(section => {
            section.classList.add('neutral');
        });
    })();
</script>

</body>
</html>

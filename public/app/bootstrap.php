<?php
/**
 * Bootstrap для Portal Entry API
 * 
 * Назначение:
 *   Минимальная инициализация, необходимая для корректной работы
 *   app/api/portal_entry.php по каноническому потоку логина Portal → Forum.
 * 
 * Кто и откуда подключает:
 *   - app/api/portal_entry.php (строка 8): require_once __DIR__ . '/../bootstrap.php';
 * 
 * Что запрещено добавлять в будущем:
 *   - Автозагрузку классов (composer autoload, spl_autoload_register)
 *   - Инициализацию сессий (session_start) - это делает portal_entry.php
 *   - Подключение конфигураций (config/db.php, config/sso/sso_config.php) - это делает portal_entry.php
 *   - Логику "на будущее" или "на всякий случай"
 *   - Побочные эффекты (вывод, редиректы, exit)
 *   - Глобальные переменные или константы, не используемые portal_entry.php
 * 
 * Принцип: только необходимый минимум для работы portal_entry.php.
 */

// Минимальная инициализация: ничего не делаем
// portal_entry.php использует только стандартные PHP функции и классы (PDO, session, curl)
// Все необходимые конфигурации и функции подключаются/определяются в самом portal_entry.php



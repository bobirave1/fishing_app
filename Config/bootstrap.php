<?php
/**
 * Application bootstrap — loaded once by the front controller.
 * Sets up session, database, security headers, translations,
 * DI container, logger, exception handler, and global actions.
 */

require_once __DIR__ . '/../src/autoload.php';
require_once __DIR__ . '/security.php';

secureSession();

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/avatar_helper.php';
require_once __DIR__ . '/languages.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/actions.php';

setSecurityHeaders();

// ── DI Container ──────────────────────────────────────
use App\Core\Container;
use App\Core\Logger;
use App\Core\ExceptionHandler;

$container = new Container();

// Core: PDO (reuse the $pdo created by config/database.php)
$container->instance(\PDO::class, $pdo);

// Core: Logger
$container->singleton(Logger::class, fn () => new Logger());

// Core: ExceptionHandler
$container->singleton(ExceptionHandler::class, function (Container $c) {
    return new ExceptionHandler($c->get(Logger::class));
});

// Register global exception/error handler
$container->get(ExceptionHandler::class)->register();

// ── Service registrations ────────────────────────────
$container->singleton(App\Services\AuthService::class, fn (Container $c) => new App\Services\AuthService($c->pdo()));
$container->singleton(App\Services\PostService::class, fn (Container $c) => new App\Services\PostService($c->pdo()));
$container->singleton(App\Services\FriendService::class, fn (Container $c) => new App\Services\FriendService($c->pdo()));
$container->singleton(App\Services\MessageService::class, fn (Container $c) => new App\Services\MessageService($c->pdo()));
$container->singleton(App\Services\NotificationService::class, fn (Container $c) => new App\Services\NotificationService($c->pdo()));
$container->singleton(App\Services\UserService::class, fn (Container $c) => new App\Services\UserService($c->pdo()));
$container->singleton(App\Services\SearchService::class, fn (Container $c) => new App\Services\SearchService($c->pdo()));
$container->singleton(App\Services\AdvancedSearchService::class, fn (Container $c) => new App\Services\AdvancedSearchService($c->pdo()));
$container->singleton(App\Services\WeatherService::class, fn () => new App\Services\WeatherService());
$container->singleton(App\Services\ChatService::class, fn (Container $c) => new App\Services\ChatService($c->pdo()));
$container->singleton(App\Services\GamificationService::class, fn (Container $c) => new App\Services\GamificationService($c->pdo()));
$container->singleton(App\Services\ExportService::class, fn (Container $c) => new App\Services\ExportService($c->pdo()));
$container->singleton(App\Services\PushNotificationService::class, fn (Container $c) => new App\Services\PushNotificationService($c->pdo()));
$container->singleton(App\Services\FishActivityEngine::class, fn () => new App\Services\FishActivityEngine());
$container->singleton(App\Services\ActivityCacheService::class, fn () => new App\Services\ActivityCacheService());

// Make container globally available for legacy be/ endpoints
$GLOBALS['container'] = $container;

handleGlobalActions();

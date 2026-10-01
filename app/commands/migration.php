<?php

class MigrationCommand
{
    public static $command = 'migration';
    public static $description = 'Manage database migrations';
    public static $arguments = [
        '[action]' => 'run, create-migration, rollback, rollback-all, refresh, status',
        '[name]' => 'Migration name for create-migration',
    ];

    private static $route_map = [
        'run' => 'migrate',
        'rollback' => 'rollback',
        'rollback-all' => 'rollback_all',
        'refresh' => 'refresh',
        'status' => 'status',
    ];

    public function handle($action = null, array $flags = [])
    {
        $action = $action ?: 'run';
        $name = $GLOBALS['argv'][3] ?? null;
        if ($action === 'create-migration' && (!$name || !preg_match('/^[a-z][a-z0-9_]*$/i', $name))) {
            fwrite(STDERR, "Usage: php lava migration create-migration create_products_table\n");
            exit(1);
        }
        if ($action !== 'create-migration' && !isset(self::$route_map[$action])) {
            fwrite(STDERR, 'Unknown migration action: ' . $action . PHP_EOL);
            exit(1);
        }

        $root = dirname(APP_DIR) . DIRECTORY_SEPARATOR;
        if (!defined('PREVENT_DIRECT_ACCESS')) define('PREVENT_DIRECT_ACCESS', TRUE);
        if (!defined('ROOT_DIR')) define('ROOT_DIR', $root);
        if (!defined('SYSTEM_DIR')) define('SYSTEM_DIR', ROOT_DIR . 'scheme' . DIRECTORY_SEPARATOR);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['PHP_SELF'] = '/index.php';
        ob_start();
        require_once SYSTEM_DIR . 'kernel/LavaLust.php';
        ob_end_clean();

        $migration = lava_instance()->call->library('migration');
        if ($action === 'create-migration') {
            $migration->create_migration($name);
            return;
        }
        $method = self::$route_map[$action];
        $migration->$method();
    }
}
<?php

namespace Nadi\WordPress;

use Nadi\Shipper\BinaryManager;
use Nadi\Shipper\Exceptions\ShipperException;

class Shipper
{
    private BinaryManager $manager;

    private ?string $configPath = null;

    public function __construct(?string $binaryDirectory = null)
    {
        $directory = $binaryDirectory ?? dirname(__DIR__).'/bin';
        $this->manager = new BinaryManager($directory);
    }

    /**
     * Set the configuration file path.
     */
    public function setConfigPath(string $path): self
    {
        $this->configPath = $path;

        return $this;
    }

    /**
     * Get the configuration file path.
     */
    public function getConfigPath(): ?string
    {
        return $this->configPath;
    }

    /**
     * Check if the shipper binary is installed.
     */
    public function isInstalled(): bool
    {
        return $this->manager->isInstalled();
    }

    /**
     * Get the full path to the shipper binary.
     */
    public function getBinaryPath(): string
    {
        return $this->manager->getBinaryPath();
    }

    /**
     * Get the binary directory path.
     */
    public function getBinaryDirectory(): string
    {
        return $this->manager->getBinaryDirectory();
    }

    /**
     * Get the currently installed version.
     */
    public function getInstalledVersion(): ?string
    {
        return $this->manager->getInstalledVersion();
    }

    /**
     * Send records using the shipper binary.
     *
     * @throws ShipperException
     */
    public function send(): array
    {
        if ($this->configPath === null) {
            throw new ShipperException('Config path not set. Call setConfigPath() first.');
        }

        return $this->manager->execute([
            '--config='.$this->configPath,
            '--record',
        ]);
    }

    /**
     * Static method to send records (for backward compatibility with cron).
     *
     * @throws ShipperException
     */
    public static function sendRecords(?string $configPath = null): array
    {
        $shipper = new self;

        $config = $configPath ?? self::getDefaultConfigPath();

        if ($config === null) {
            throw new ShipperException('Config path not provided and no default config found.');
        }

        return $shipper->setConfigPath($config)->send();
    }

    /**
     * Get the default config path.
     */
    public static function getDefaultConfigPath(): ?string
    {
        $pluginDir = dirname(__DIR__);
        $configPath = $pluginDir.'/config/nadi.yaml';

        return file_exists($configPath) ? $configPath : null;
    }

    /**
     * Install the shipper binary.
     *
     * @throws ShipperException
     */
    public static function install(): void
    {
        $shipper = new self;

        if ($shipper->isInstalled()) {
            return;
        }

        try {
            $shipper->manager->install();
            $shipper->setupCron();
        } catch (ShipperException $e) {
            self::logError('Failed to install shipper: '.$e->getMessage());

            throw $e;
        }
    }

    /**
     * Uninstall the shipper binary.
     */
    public static function uninstall(): void
    {
        $shipper = new self;

        $shipper->clearCron();
        $shipper->manager->uninstall();
    }

    /**
     * Deactivate the shipper (stops cron but keeps binary).
     */
    public static function deactivate(): void
    {
        $shipper = new self;
        $shipper->clearCron();
    }

    /**
     * Activate the shipper (restarts cron if binary is installed).
     */
    public static function activate(): void
    {
        $shipper = new self;

        if ($shipper->isInstalled()) {
            $shipper->setupCron();
        }
    }

    /**
     * Register cron schedule and handler on every plugin load.
     */
    public static function registerCron(): void
    {
        $shipper = new self;

        if ($shipper->isInstalled()) {
            $shipper->setupCron();
        }
    }

    /**
     * Set up the WordPress cron job.
     */
    private function setupCron(): void
    {
        if (! function_exists('add_action')) {
            return;
        }

        add_filter('cron_schedules', function ($schedules) {
            $schedules['nadi_every_minute'] = [
                'interval' => 60,
                'display' => 'Every Minute',
            ];

            return $schedules;
        });

        add_action('send_nadi_log_event', 'sendNadiLogHandler');

        if (! wp_next_scheduled('send_nadi_log_event')) {
            wp_schedule_event(time(), 'nadi_every_minute', 'send_nadi_log_event');
        }
    }

    /**
     * Clear the WordPress cron job.
     */
    private function clearCron(): void
    {
        if (! function_exists('wp_clear_scheduled_hook')) {
            return;
        }

        wp_clear_scheduled_hook('send_nadi_log_event');
    }

    /**
     * Log an error message.
     */
    private static function logError(string $message): void
    {
        if (function_exists('error_log')) {
            error_log('[Nadi Shipper] '.$message);
        }
    }

    /**
     * Show admin notice for errors.
     */
    public static function showAdminNotice(string $message, string $type = 'error'): void
    {
        if (! function_exists('add_action')) {
            return;
        }

        add_action('admin_notices', function () use ($message, $type) {
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($type),
                esc_html($message)
            );
        });
    }

    /**
     * Get the underlying BinaryManager instance.
     */
    public function getManager(): BinaryManager
    {
        return $this->manager;
    }
}

<?php

namespace Nadi\WordPress;

use PharData;

class Shipper
{
    private string $repository = 'nadi-pro/shipper';

    private string $operating_system;

    private string $config_path;

    public function __construct()
    {
        $this->operating_system = php_uname('s');
    }

    public function run()
    {
        $command = $this->getBinaryPath() . ' --config='. $this->getConfigPath() .' --record';
        exec($command);
    }

    public function setConfigPath(string $path): self
    {
        $this->config_path = $path;

        return $this;
    }

    public function getConfigPath(): string
    {
        return $this->config_path;
    }

    public function isInstalled()
    {
        return $this->isBinaryInstalled();
    }

    private function isBinaryInstalled()
    {
        return file_exists($this->getBinaryPath());
    }

    private function getBinaryPath()
    {
        return "{$this->getBinaryDirectory()}/shipper";
    }

    private function getBinaryDirectory()
    {
        return dirname(__DIR__).'/bin';
    }

    public static function install()
    {
        $shipper = new self();

        if ($shipper->isInstalled()) {
            return;
        }

        $version = $shipper->setVersion();
        $binaryPath = $shipper->downloadBinary($version);
        $shipper->extractBinary($binaryPath);
        $shipper->setPermissions();
    }

    public static function uninstall()
    {
        $shipper = new self();

        if ($shipper->isBinaryInstalled()) {
            unlink($shipper->getBinaryPath());
        }
    }

    public static function deactivate()
    {
        $shipper = new self();

        // need to disable the cron.
    }

    private function setVersion()
    {
        $version = $this->getLatestVersion();
        if (! $version) {
            echo "\nThere was an error trying to check what is the latest version of shipper.\nPlease try again later.\n";
            exit(1);
        }

        return $version;
    }

    private function getLatestVersion()
    {
        $url = "https://api.github.com/repos/{$this->repository}/releases/latest";

        // Initialize cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0'); // GitHub API requires a user-agent

        // Execute cURL request
        $response = curl_exec($ch);

        // Check for errors
        if ($response === false) {
            return false;
        }

        // Close cURL session
        curl_close($ch);

        // Decode JSON response
        $data = json_decode($response, true);

        // Return tag name if available
        return isset($data['tag_name']) ? $data['tag_name'] : false;
    }

    private function downloadBinary($version)
    {
        $os = strtolower($this->operating_system);
        $osType = $this->getOSType();
        $ghRepoBin = "shipper-$version-$os-$osType.tar.gz";
        $tmpDir = sys_get_temp_dir();
        $link = "https://github.com/{$this->repository}/releases/download/{$version}/{$ghRepoBin}";
        $binaryData = file_get_contents($link);
        $tmpFilePath = $tmpDir.'/'.$ghRepoBin;
        file_put_contents($tmpFilePath, $binaryData);

        return $tmpFilePath;
    }

    private function getOSType()
    {
        $osType = php_uname('m');
        switch ($osType) {
            case 'x86_64':
            case 'amd64':
                return 'amd64';
            case 'i386':
            case 'i486':
            case 'i586':
            case 'i686':
            case 'i786':
            case 'i886':
            case 'i986':
                return '386';
            case 'aarch64':
            case 'arm64':
                return 'arm64';
            default:
                echo 'OS type not supported';
                exit(2);
        }
    }

    private function extractBinary($binaryPath)
    {
        $extractPath = $this->getBinaryDirectory();
        $phar = new PharData($binaryPath);
        $phar->extractTo($extractPath, null, true);
        unlink($binaryPath);
    }

    private function setPermissions()
    {
        $shipperBinaryPath = "{$this->getBinaryDirectory()}/shipper";
        chmod($shipperBinaryPath, 0755);
    }
}

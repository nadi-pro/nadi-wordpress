<?php

namespace Nadi\WordPress;

use PharData;

class Shipper
{
    private $GH_REPO = 'nadi-pro/shipper';

    private $OS;

    public function __construct()
    {
        $this->OS = php_uname('s');
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
        $url = "https://api.github.com/repos/{$this->GH_REPO}/releases/latest";
        $response = file_get_contents($url);
        if ($response === false) {
            return false;
        }
        $data = json_decode($response, true);

        return $data['tag_name'] ?? false;
    }

    private function downloadBinary($version)
    {
        $os = strtolower($this->OS);
        $osType = $this->getOSType();
        $ghRepoBin = "shipper-$version-$os-$osType.tar.gz";
        $tmpDir = sys_get_temp_dir();
        $link = "https://github.com/{$this->GH_REPO}/releases/download/{$version}/{$ghRepoBin}";
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

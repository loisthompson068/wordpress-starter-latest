<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Util;

use Composer\Package\PackageInterface;

/**
 * Helper that uses Composer objects to get a list of installed packages and filter them to obtain
 * the list of installed MU plugins and their installation paths.
 */
class MuPluginList
{
    /**
     * @var PackageFinder
     */
    private $packageFinder;

    /**
     * @param PackageFinder $packageFinder
     */
    public function __construct(PackageFinder $packageFinder)
    {
        $this->packageFinder = $packageFinder;
    }

    /**
     * @return array
     */
    public function pluginsList(): array
    {
        $list = [];

        $packages = $this->packageFinder->findByType('wordpress-muplugin');
        foreach ($packages as $package) {
            $path = $this->pathForPluginPackage($package);
            $path and $list[$package->getName()] = $path;
        }

        return $list;
    }

    /**
     * @param PackageInterface $package
     * @return string
     */
    private function pathForPluginPackage(PackageInterface $package): string
    {
        $path = $this->packageFinder->findPathOf($package);
        if (!$path) {
            return '';
        }

        $files = glob("{$path}/*.php");
        if (!$files) {
            return '';
        }

        if (count($files) === 1) {
            return reset($files);
        }

        foreach ($files as $file) {
            if ($this->isPluginFile($file)) {
                return $file;
            }
        }

        return '';
    }

    /**
     * @param string $file
     * @return bool
     */
    private function isPluginFile(string $file): bool
    {
        $handle = @fopen($file, 'r');
        $data = @fread($handle, 8192);
        @fclose($handle);
        if (!$data) {
            return false;
        }

        $data = str_replace("\r", "\n", $data);

        return preg_match('/^[ \t\/*#@]*Plugin Name:(.*)$/mi', $data, $match) && ! empty($match[1]);
    }
}

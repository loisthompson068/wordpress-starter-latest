<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Util;

use Composer\Composer;
use Composer\Util\Filesystem;
use WeCodeMore\WpStarter\Config\Config;

final class Paths implements \ArrayAccess
{
    const ROOT = 'root';
    const VENDOR = 'vendor';
    const BIN = 'bin';
    const WP = 'wp';
    const WP_PARENT = 'wp-parent';
    const WP_CONTENT = 'wp-content';

    /**
     * @var \SplObjectStorage
     */
    private static $parsed;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var array
     */
    private $paths = [];

    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @var string|null
     */
    private $customTemplatesDir;

    /**
     * @param \Composer\Composer $composer
     * @param Filesystem $filesystem
     */
    public function __construct(Composer $composer, Filesystem $filesystem)
    {
        self::$parsed === null and self::$parsed = new \SplObjectStorage();
        $this->composer = $composer;
        $this->filesystem = $filesystem;
        $this->paths = self::$parsed->contains($this->composer)
            ? self::$parsed->offsetGet($this->composer)
            : $this->parse();
    }

    /**
     * @param string $templatesRootDir
     */
    public function useCustomTemplatesDir(string $templatesRootDir)
    {
        if (is_dir($templatesRootDir)) {
            $this->customTemplatesDir = rtrim($templatesRootDir, '/');
        }
    }

    /**
     * @return array
     */
    private function parse(): array
    {
        $extra = $this->composer->getPackage()->getExtra();

        $cwd = realpath(getcwd());

        $wpInstallDir = empty($extra['wordpress-install-dir'])
            ? 'wordpress'
            : $extra['wordpress-install-dir'];

        $wpFullDir = realpath("{$cwd}/{$wpInstallDir}");
        $wpParent = $wpFullDir ? dirname($wpFullDir) : $cwd;

        $wpContent = empty($extra['wordpress-content-dir'])
            ? $this->filesystem->normalizePath($cwd . '/wp-content')
            : $this->filesystem->normalizePath($cwd . "/{$extra['wordpress-content-dir']}");

        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        $binDir = $this->composer->getConfig()->get('bin-dir');

        $paths = [
            self::ROOT => $this->filesystem->normalizePath($cwd),
            self::VENDOR => $this->filesystem->normalizePath($vendorDir),
            self::BIN => $this->filesystem->normalizePath($binDir),
            self::WP => $this->filesystem->normalizePath($wpFullDir),
            self::WP_PARENT => $this->filesystem->normalizePath($wpParent),
            self::WP_CONTENT => $this->filesystem->normalizePath($wpContent),
        ];

        self::$parsed->attach($this->composer, $paths);

        return $paths;
    }

    /**
     * @param string $pathName Use one of the class constants
     * @param string $to
     * @return string
     */
    public function absolute(string $pathName, string $to = ''): string
    {
        if (!array_key_exists($pathName, $this->paths)) {
            throw new \InvalidArgumentException(
                sprintf('%s is not a valid WP Starter path key.', $pathName)
            );
        }

        return $this->paths[$pathName] . $this->to($to);
    }

    /**
     * @param string $pathName Use one of the class constants
     * @param string $to
     * @return string
     */
    public function relativeToRoot(string $pathName, string $to = ''): string
    {
        if (!array_key_exists($pathName, $this->paths)) {
            throw new \InvalidArgumentException(
                sprintf('%s is not a valid WP Starter path key.', $pathName)
            );
        }

        if ($pathName === self::ROOT) {
            return $this->to($to) ?: './';
        }

        $subdir = $this->filesystem->findShortestPath(
            $this->paths[self::ROOT],
            $this->paths[$pathName]
        );

        $to = $this->to($to);
        $to and $subdir = rtrim($subdir, '/\\') . $to;

        return $subdir;
    }

    /**
     * @param string $to
     * @return string
     */
    public function root(string $to = ''): string
    {
        return $this->absolute(self::ROOT, $to);
    }

    /**
     * @param string $to
     * @return string
     */
    public function vendor(string $to = ''): string
    {
        return $this->absolute(self::VENDOR, $to);
    }

    /**
     * @param string $to
     * @return string
     */
    public function bin(string $to = ''): string
    {
        return $this->absolute(self::BIN, $to);
    }

    /**
     * @param string $to
     * @return string
     */
    public function wp(string $to = ''): string
    {
        return $this->absolute(self::WP, $to);
    }

    /**
     * @param string $to
     * @return string
     */
    public function wpParent(string $to = ''): string
    {
        return $this->absolute(self::WP_PARENT, $to);
    }

    /**
     * @param string $to
     * @return string
     */
    public function wpContent(string $to = ''): string
    {
        return $this->absolute(self::WP_CONTENT, $to);
    }

    /**
     * @param string $filename
     * @return string
     */
    public function template(string $filename): string
    {
        if ($this->customTemplatesDir && is_file("{$this->customTemplatesDir}/{$filename}")) {
            return "{$this->customTemplatesDir}/{$filename}";
        }

        return $this->root("templates/{$filename}");
    }

    /**
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->paths);
    }

    /**
     * @param string $offset
     * @return string
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            throw new \OutOfRangeException(
                sprintf('%s is not a valid WP Starter path index.', $offset)
            );
        }

        return $this->paths[$offset];
    }

    /**
     * @param string $offset
     * @param string $value
     */
    public function offsetSet($offset, $value)
    {
        if ($this->offsetExists($offset)) {
            throw new \BadMethodCallException(
                sprintf(
                    '%s is append-only: can\'t set %s path because that name is already set.',
                    __CLASS__,
                    $offset
                )
            );
        }

        $this->paths[$offset] = $value;
        self::$parsed->attach($this->composer, $this->paths);
    }

    /**
     * Disabled.
     *
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException(sprintf('%s class does not support unset.', __CLASS__));
    }

    /**
     * @param string $to
     * @return string
     */
    private function to(string $to): string
    {
        if ($to) {
            $trail = strlen($to) > 1 && in_array(substr($to, -1, 1), ['\\', '/'], true);
            $to = '/' . ltrim($this->filesystem->normalizePath($to), '/');
            $trail and $to .= '/';
        }

        return $to;
    }
}

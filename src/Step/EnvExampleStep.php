<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Util\Io;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util\Locator;
use WeCodeMore\WpStarter\Util\Paths;
use WeCodeMore\WpStarter\Util\UrlDownloader;

/**
 * Steps that stores .env.example in root folder.
 */
final class EnvExampleStep implements FileCreationStepInterface, OptionalStep
{
    const NAME = 'build-env-example';

    /**
     * @var \WeCodeMore\WpStarter\Util\Io
     */
    private $io;

    /**
     * @var \WeCodeMore\WpStarter\Config\Config
     */
    private $config;

    /**
     * @var Paths
     */
    private $paths;

    /**
     * @var \WeCodeMore\WpStarter\Util\Filesystem
     */
    private $filesystem;

    /**
     * @var UrlDownloader
     */
    private $urlDownloader;

    /**
     * @var string
     */
    private $error = '';

    /**
     * @param Locator $locator
     */
    public function __construct(Locator $locator)
    {
        $this->io = $locator->io();
        $this->config = $locator->config();
        $this->paths = $locator->paths();
        $this->filesystem = $locator->filesystem();
        $this->urlDownloader = $locator->urlDownloader();
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'build-env-example';
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return bool
     */
    public function allowed(Config $config, Paths $paths): bool
    {
        return
            $config[Config::ENV_EXAMPLE]->not(false)
            && !is_file($paths->root($config[Config::ENV_FILE]->unwrapOrFallback('.env')));
    }

    /**
     * @param Paths $paths
     * @return string
     */
    public function targetPath(Paths $paths): string
    {
        return $paths->root('.env.example');
    }

    /**
     * @param Config $config
     * @param Io $io
     * @return bool
     */
    public function askConfirm(Config $config, Io $io): bool
    {
        if ($config[Config::ENV_EXAMPLE]->is(OptionalStep::ASK)) {
            $lines = [
                'Do you want to save .env.example file to',
                'your project folder?',
            ];

            return $io->confirm($lines, true);
        }

        return true;
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    public function run(Config $config, Paths $paths): int
    {
        $source = $this->config[Config::ENV_EXAMPLE]->unwrapOrFallback(false);
        if (!$source) {
            return Step::NONE;
        }

        $destination = $this->targetPath($paths);

        if (filter_var($source, FILTER_VALIDATE_URL)) {
            return $this->download($source, $destination);
        }

        $isAsk = $source === OptionalStep::ASK;

        if (!$isAsk && is_string($source)) {
            $realpath = realpath($paths->root($source));
            if (!$realpath) {
                $this->error = "{$source} is not a valid valid relative path to env-example file.";

                return Step::ERROR;
            }

            return $this->copy($paths, $destination, $realpath);
        }

        return $this->copy($paths, $destination);
    }

    /**
     * Download a remote .env.example in root folder.
     *
     * @param  string $url
     * @param  string $dest
     * @return int
     * @throws \RuntimeException
     */
    private function download(string $url, string $dest): int
    {
        if (!$this->urlDownloader->save($url, $dest)) {
            $error = $this->urlDownloader->error();
            $this->error = "Error downloading and saving {$url}: {$error}";

            return self::ERROR;
        }

        return self::SUCCESS;
    }

    /**
     * Copy a .env.example in root folder.
     *
     * @param  Paths $paths
     * @param  string $dest
     * @param  string|null $source
     * @return int
     * @throws \InvalidArgumentException
     */
    private function copy(Paths $paths, string $dest, string $source = null): int
    {
        if ($source === null) {
            $source = $paths->template('.env.example');
        }

        if ($this->filesystem->copyFile($source, $dest)) {
            return self::SUCCESS;
        }

        $this->error = 'Error on copy default .env.example in root folder.';

        return self::ERROR;
    }

    /**
     * @inheritdoc
     */
    public function error(): string
    {
        return $this->error;
    }

    /**
     * @inheritdoc
     */
    public function skipped(): string
    {
        return '  - env.example copy skipped.';
    }

    /**
     * @inheritdoc
     */
    public function success(): string
    {
        return '<comment>env.example</comment> saved successfully.';
    }
}
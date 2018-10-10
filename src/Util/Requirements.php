<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Util;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use WeCodeMore\WpStarter\ComposerPlugin;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Config\Validator;

final class Requirements
{
    /**
     * @var Paths
     */
    private $paths;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Io
     */
    private $io;

    /**
     * @param Composer $composer
     * @param IOInterface $io
     * @param Filesystem $filesystem
     */
    public function __construct(
        Composer $composer,
        IOInterface $io,
        Filesystem $filesystem
    ) {

        $config = $this->extractConfig($composer->getPackage()->getExtra());

        empty($config[Config::CUSTOM_STEPS]) and $config[Config::CUSTOM_STEPS] = [];
        empty($config[Config::SCRIPTS]) and $config[Config::SCRIPTS] = [];

        $packageRepo = $composer->getRepositoryManager()->getLocalRepository();
        $installationManager = $composer->getInstallationManager();
        $muPluginList = new MuPluginList($packageRepo, $installationManager);
        $config[Config::MU_PLUGIN_LIST] = $muPluginList->pluginsList();

        $config[Config::COMPOSER_CONFIG] = $composer->getConfig()->all();
        $config[Config::WP_CLI_EXECUTOR] = null;

        $this->paths = new Paths($composer, $filesystem);
        $this->config = new Config($config, new Validator($this->paths, $filesystem));
        $this->io = new Io($io);
        $this->paths->initTemplates($this->config);
    }

    /**
     * @return Config
     */
    public function config(): Config
    {
        return $this->config;
    }

    /**
     * @return Io
     */
    public function io(): Io
    {
        return $this->io;
    }

    /**
     * @return Paths
     */
    public function paths(): Paths
    {
        return $this->paths;
    }

    /**
     * @param array $extra
     * @return array
     */
    private function extractConfig(array $extra): array
    {
        $configs = empty($extra[ComposerPlugin::EXTRA_KEY])
            ? []
            : $extra[ComposerPlugin::EXTRA_KEY];

        $dir = getcwd() . DIRECTORY_SEPARATOR;

        $file = is_string($configs) ? trim(basename($configs), '/\\') : 'wpstarter.json';

        // Extract config from a separate JSON file
        $fileConfigs = null;
        if (is_file($dir . $file) && is_readable($dir . $file)) {
            $content = @file_get_contents($dir . $configs);
            $fileConfigs = $content ? @json_decode($content, true) : null;
            is_object($fileConfigs) and $fileConfigs = get_object_vars($fileConfigs);
        }

        is_object($configs) and $configs = get_object_vars($configs);
        is_array($configs) or $configs = [];
        $fileConfigs and $configs = array_merge($configs, $fileConfigs);

        return $configs;
    }
}

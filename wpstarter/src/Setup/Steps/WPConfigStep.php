<?php
/*
 * This file is part of the WPStarter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Setup\Steps;

use ArrayAccess;
use WCM\WPStarter\Setup\Config;
use WCM\WPStarter\Setup\IO;
use WCM\WPStarter\Setup\FileBuilder;
use WCM\WPStarter\Setup\Salter;

/**
 * Step that generates and saves wp-config.php.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
class WPConfigStep implements FileStepInterface, BlockingStepInterface
{
    /**
     * @var \WCM\WPStarter\Setup\IO
     */
    private $io;

    /**
     * @var \WCM\WPStarter\Setup\FileBuilder
     */
    private $builder;

    /**
     * @var \WCM\WPStarter\Setup\Salter
     */
    private $salter;

    /**
     * @var \WCM\WPStarter\Setup\Config
     */
    private $config;

    /**
     * @var string
     */
    private $error = '';

    /**
     * @param \WCM\WPStarter\Setup\IO          $io
     * @param \WCM\WPStarter\Setup\FileBuilder $builder
     * @param \WCM\WPStarter\Setup\Salter|null $salter
     */
    public function __construct(IO $io, FileBuilder $builder, Salter $salter = null)
    {
        $this->io = $io;
        $this->builder = $builder;
        $this->salter = $salter ?: new Salter();
    }

    /**
     * @inheritdoc
     */
    public function allowed(Config $config, ArrayAccess $paths)
    {
        $this->config = $config;

        return true;
    }

    /**
     * @inheritdoc
     */
    public function targetPath(ArrayAccess $paths)
    {
        return rtrim($paths['root'].'/'.$paths['site-dir'], '/').'/wp-config.php';
    }

    /**
     * @inheritdoc
     */
    public function run(ArrayAccess $paths)
    {
        $register = $this->config['register-theme-folder'];
        if ($register === 'ask') {
            $register = $this->ask();
        }
        $siteDir = $paths['site-dir'] ? str_replace('\\', '/', $paths['site-dir']) : false;
        $n = $siteDir ? substr_count(trim($siteDir, '/'), '/')+1 : 0;
        $envRelPath = str_repeat('dirname(', $n).'__DIR__'.str_repeat(')', $n);
        $vars = array_merge(
            array(
                'VENDOR_PATH'        => $paths['vendor'],
                'WP_INSTALL_PATH'    => $paths['wp'],
                'WP_CONTENT_PATH'    => $paths['wp-content'],
                'REGISTER_THEME_DIR' => $register ? 'true' : 'false',
                'ENV_REL_PATH'       => $envRelPath,
            ),
            $this->salter->keys()
        );
        $build = $this->builder->build($paths, 'wp-config.example', $vars);
        if (! $this->builder->save($build, dirname($this->targetPath($paths)), 'wp-config.php')) {
            $this->error = 'Error on create wp-config.php.';

            return self::ERROR;
        }

        return self::SUCCESS;
    }

    /**
     * @inheritdoc
     */
    public function error()
    {
        return $this->error;
    }

    /**
     * @inheritdoc
     */
    public function success()
    {
        return '<comment>wp-config.php</comment> saved successfully.';
    }

    /**
     * @return bool
     */
    private function ask()
    {
        $lines = array(
            'Do you want to register WordPress package wp-content folder',
            'as additional theme folder for your project?',
        );

        return $this->io->ask($lines, true);
    }
}

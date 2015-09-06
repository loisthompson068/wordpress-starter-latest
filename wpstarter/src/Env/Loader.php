<?php
/*
 * This file is part of the wpstarter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Env;

use Dotenv\Loader as DotenvLoader;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package wpstarter
 */
final class Loader extends DotenvLoader
{

    private $allVars = array();

    /**
     * Set variable using Dotenv loader and store the name in class var
     *
     * @param string $name
     * @param mixed  $value
     */
    public function setEnvironmentVariable($name, $value = null)
    {
        list($name, $value) = $this->normaliseEnvironmentVariable($name, $value);

        if ($this->immutable === true && ! is_null($this->getEnvironmentVariable($name))) {
            return;
        }

        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
        $this->allVars[] = $name;
    }

    /**
     * @return array
     */
    public function allVarNames()
    {
        return $this->allVars;
    }

}
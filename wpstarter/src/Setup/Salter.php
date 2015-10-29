<?php
/*
 * This file is part of the WPStarter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Setup;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
class Salter
{
    private static $keys = array(
        'AUTH_KEY',
        'SECURE_AUTH_KEY',
        'LOGGED_IN_KEY',
        'NONCE_KEY',
        'AUTH_SALT',
        'SECURE_AUTH_SALT',
        'LOGGED_IN_SALT',
        'NONCE_SALT',
    );

    private $result;

    /**
     * Build random keys.
     *
     * @return array
     */
    public function keys()
    {
        if (! is_array($this->result)) {
            $this->result = array();
            foreach (self::$keys as $key) {
                $this->result[$key] = $this->buildKey(64);
            }
        }

        return $this->result;
    }

    /**
     * Build random key.
     *
     * @param  int    $length
     * @return string
     */
    private function buildKey($length)
    {
        $chars = ' =,.;:/?!|@#$%^&*()-_[]{}<>~`+abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $key = '';
        for ($i = 0; $i < $length; $i++) {
            $rand = mt_rand(0, 91);
            $key .= $chars[$rand];
        }

        return $key;
    }
}

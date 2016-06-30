<?php

namespace TAO;

/**
 * Class CLI
 * @package TAO
 */
class CLI
{
    /**
     * @var array
     */
    static $handlers = array(
        'elements_urls' => array('\TAO\Infoblock', 'cliRebuildUrls'),
    );
    /**
     * @var bool
     */
    static $actions = false;
    /**
     * @var bool
     */
    static $options = false;
    /**
     * @var bool
     */
    static $script = false;

    /**
     *
     */
    public static function run()
    {
        $c = 0;
        self::$actions = array();
        self::$options = array();
        $argv = $GLOBALS['argv'];
        foreach ($argv as $arg) {
            $arg = trim($arg);

            if (preg_match('{^--([^\s=]+)=(.*)}', $arg, $m)) {
                self::$options[$m[1]] = $m[2];
            } else {
                if (preg_match('{^--([^\s=]+)}', $arg, $m)) {
                    self::$options[$m[1]] = true;
                } else {
                    if ($c == 0) {
                        self::$script = $arg;
                    } else {
                        self::$actions[$c - 1] = $arg;
                    }

                    $c++;
                }
            }
        }

        foreach (self::$actions as $action) {
            foreach (\TAO::bundles() as $bundle) {
                $bundle->cli($action, self::$options);
            }
            if (isset(self::$handlers[$action])) {
                $cb = self::$handlers[$action];
                if (is_string($cb)) {
                    $cb = array($cb, $action);
                }
                if (is_callable($cb)) {
                    call_user_func($cb, self::$options);
                }
            }
        }
    }
}
<?php
/**
 * RSentryLog class file.
 *
 * @author Rolies Deby <rolies106@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2012 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * RSentryLog records log messages to sentry server.
 *
 * @author Rolies Deby <rolies106@gmail.com>
 * @version $Id: CFileLogRoute.php 3426 2011-10-25 00:01:09Z alexander.makarow $
 * @package system.logging
 * @since 1.0
 */
class RSentryLog extends CLogRoute
{
    /**
     * @var string Sentry DSN value
     */
    public $dsn;

    /**
     * @var Raven_Client Sentry stored connection
     */
    protected $_client;

    /**
     * @var string Logger identifier
     */
    public $logger = 'php';

    /**
     * @var string|null
     */
    public $environment = null;

    /**
     * @var array array of regex
     */
    public $exceptTitle = array();

    /**
     * @var array of string|string[] Fetch context from globals by array keys or objects properties chains if exists. E.g. ['a' => ['_SESSION','a'], ['_SESSION','User', 'id'], ['_SESSION','optional_object', 'property']]
     */
    public $context = false;

    /**
     * Initializes the connection.
     */
    public function init()
    {
        parent::init();

        if ($this->_client === null) {
            $this->_client = new Raven_Client($this->dsn, array(
                'logger' => $this->logger,
                'environment' => $this->environment,
            ));
        }
    }

    /**
     * @param $title
     * @return bool
     */
    public function canLogTitle($title)
    {
        foreach ($this->exceptTitle as $pattern) {
            if (preg_match($pattern, $title)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Send log messages to Sentry.
     * @param array $logs list of log messages
     */
    protected function processLogs($logs)
    {
        foreach ($logs as $log) {
            $format = explode("\n", $log[0]);
            $title = strip_tags($format[0]);
            if ($this->canLogTitle($title)) {
                if ($this->context) {
                    $this->_client->user_context($this->extractContext(), true);
                }
                $this->_client->tags_context(['yii.component' => get_class($this)]);
                $this->_client->captureMessage($title, array(), $log[1], false);
            }
        }
    }

    /**
     * Extracts from GLOBALS stuff set in context
     * @return array
     */
    private function extractContext()
    {
        $r = [];
        if (!$this->context)
            return $r;

        foreach ($this->context as $name => $item) {
            if (is_numeric($name)) { // name defaults to glued array if it is numeric
                $name = implode(':', $item);
            }
            if (is_string($item)) {
                if (key_exists($item, $GLOBALS)) {
                    $r[$name] = $GLOBALS[$item];
                }
            } elseif (is_array($item)) {
                $register = null;
                $fail = false;
                foreach ($item as $k => $v) {
                    if ($k === 0) {
                        if (key_exists($v, $GLOBALS)) {
                            $register = $GLOBALS[$v];
                        } else {
                            $fail = true;
                            break;
                        }
                    } else {
                        if (is_array($register)) {
                            if (key_exists($v, $register)) {
                                $register = $register[$v];
                            } else {
                                $fail = true;
                                break;
                            }
                        } elseif (is_object($register)) {
                            if (property_exists($register, $v) || (method_exists($register, 'getAttribute') && is_array($register->attributes) && array_key_exists($v, $register->attributes))) {
                                $register = $register->{$v};
                            } else {
                                $fail = true;
                                break;
                            }
                        } else {
                            $fail = true;
                            break;
                        }
                    }
                }
                if (!$fail) {
                    $r[$name] = $register;
                }
            }
        }
        return $r;
    }

}

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
     * @var array array of regex
     */
    public $exceptTitle = array();

    /**
     * Initializes the connection.
     */
    public function init()
    {
        parent::init();

        if ($this->_client === null) {
            $this->_client = new Raven_Client($this->dsn, array('logger' => $this->logger));
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
                $this->_client->captureMessage($title, array(), $log[1], false);
            }
        }
    }
}

<?php

/**
 * RSentryComponent records exceptions to sentry server.
 *
 * RSentryComponent can be used with RSentryLog but only tracts exceptions
 * as Yii logger does not pass the exception to the logger but rather a string traceback
 * RSentryLog "error" logging is not that usefull as the traceback
 * does not contain variables but only a string where this component allows you to use
 * the power of sentry for exceptions.
 *
 * @author Pieter Venter <boontjiesa@gmail.com>
 * @property \Raven_Client $client
 */
class RSentryComponent extends CApplicationComponent
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
     * @var Raven_ErrorHandler Sentry error handler
     */
    protected $_error_handler;

    /**
     * @var string Logger identifier
     */
    public $logger = 'php';

    /**
     * @var string|null
     */
    public $environment = null;

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

        Yii::app()->attachEventHandler('onException', array($this, 'handleException'));
        Yii::app()->attachEventHandler('onError', array($this, 'handleError'));

        $this->_error_handler = new Raven_ErrorHandler($this->_client);
        $this->_error_handler->registerShutdownFunction();
	}

    /**
     * logs exception
     * @param CExceptionEvent $event Description
     */
    public function handleException($event)
    {
        if ($event->exception instanceof CHttpException &&
            $event->exception->statusCode >= 400 &&
            $event->exception->statusCode < 500
        ) {
            return;
        }

        $this->_error_handler->handleException($event->exception);
        if ($this->_client->getLastError()) {
            Yii::log($this->_client->getLastError(), CLogger::LEVEL_ERROR, 'raven');
        }
    }

    /**
     * @param CErrorEvent $event
     */
    public function handleError($event)
    {
        $this->_error_handler->handleError(
            $event->code,
            $event->message,
            $event->file,
            $event->line,
            $event->params // slightly different than typical context
        );
        if ($this->_client->getLastError()) {
            Yii::log($this->_client->getLastError(), CLogger::LEVEL_ERROR, 'raven');
        }
    }

    /**
     * @return \Raven_Client
     */
    public function getClient()
    {
        return $this->_client;
    }

    /**
     * @param Exception $e
     * @param array $additionalData
     */
    public function captureException($e, $additionalData = array())
    {
        $this->_client->extra_context($additionalData);
        $this->_client->captureException($e);
        $this->_client->context->clear();
    }

    /**
     * @param array $data
     */
    public function setContext($data)
    {
        $this->_client->extra_context($data);
    }
}

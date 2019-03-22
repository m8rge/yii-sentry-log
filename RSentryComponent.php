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
     * @var array of string|string[] Fetch context from globals by array keys or objects properties chains if exists. E.g. ['a' => ['_SESSION','a'], ['_SESSION','User', 'id'], ['_SESSION','optional_object', 'property']]
     */
    public $context = null;

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
        if ($this->context) {
            $this->_client->user_context($this->extractContext(), true);
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
        if ($this->context) {
            $this->_client->user_context($this->extractContext(), true);
        }
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

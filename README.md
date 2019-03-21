# Yii Sentry Log

Yii sentry log is a component for Yii to send all logging and exception to sentry server instead of showing it on screen or save it on files, especially when you set ```YII_DEBUG``` to ```false```, this module based on [raven-php](https://github.com/getsentry/raven-php) by [getsentry](https://github.com/getsentry)

RSentryComponent can be used to have proper trace back messages to use with the yii log

## Requirement

- Yii Framework >1.1.x
- [Sentry](https://www.getsentry.com/)

## Configuring

- Put your clone or copy all files to ```/protected/components/yii-sentry-log```
- Edit your ```config.php```

```php
    ...
    'components'=>array(
    	...
        'log'=>array(
            'class'=>'CLogRouter',
            'routes'=>array(
                array(
                    'class'=>'application.components.yii-sentry-log.RSentryLog',
                    'dsn'=> '[YOUR_DSN_FROM_SENTRY_SERVER]',
                    'levels'=>'error, warning',
                    // optional
                    //     adds to user context values from arrays or objects chains from $GLOBAL
                    //     skips if anything from the chain is absent (respects attributes, but not other getters)
                    //     NB! default values for user context will disappear and these values will be added
                    'context' => [
                        // adds $GLOBALS['_SESSION']['user']->username under 'nameOfUser' key
                        'nameOfUser' => ['_SESSION', 'user', 'username'],
                        // adds $GLOBALS['_SESSION']['user']->username under '_SESSION:user:email' key
                        ['_SESSION', 'user', 'email'],
                        // adds $GLOBALS['_SESSION']['optionalObject']->someProperty['someSubKey'] value, skips if absent
                        'optionalValue' => ['_SESSION', 'optionalObject', 'someProperty', 'someSubKey'],
                    ]
                ),                
            ),
        ),
        ...
    )
    ...
```

- With RSentryComponent thanks to @BoontjieSA, just worked for ```warning``` level and unfortunately its not running on a public server at the moment.
- With both `error, warning` should use `exceptTitle` to avoid doubled Exceptions reports

```php
    'preload'=> array('log', 'RSentryException'),
    'components'=>array(
    	...
    	'RSentryException'=> array(
    	    'dsn'=> '[YOUR_DSN_FROM_SENTRY_SERVER]',
            'class' => 'application.components.yii-sentry-log.RSentryComponent',
            // optional
            //     adds to user context values from arrays or objects chains from $GLOBAL
            //     skips if anything from the chain is absent (respects attributes, but not other getters)
            //     NB! default values for user context will disappear and these values will be added
            'context' => [
                // adds $GLOBALS['_SESSION']['user']->username under 'nameOfUser' key
                'nameOfUser' => ['_SESSION', 'user', 'username'],
                // adds $GLOBALS['_SESSION']['user']->username under '_SESSION:user:email' key
                ['_SESSION', 'user', 'email'],
                // adds $GLOBALS['_SESSION']['optionalObject']->someProperty['someSubKey'] value, skips if absent
                'optionalValue' => ['_SESSION', 'optionalObject', 'someProperty', 'someSubKey'],
            ]
    	),
        'log'=>array(
            'class'=>'CLogRouter',
            'routes'=>array(
                array(
                    'class'=>'application.components.yii-sentry-log.RSentryLog',
                    'dsn'=> '[YOUR_DSN_FROM_SENTRY_SERVER]',
                    'levels'=>'error, warning',
                    // optional
                    //     adds to user context values from arrays or objects chains from $GLOBAL
                    //     skips if anything from the chain is absent (respects attributes, but not other getters)
                    //     NB! default values for user context will disappear and these values will be added
                    'context' => [
                        // adds $GLOBALS['_SESSION']['user']->username under 'nameOfUser' key
                        'nameOfUser' => ['_SESSION', 'user', 'username'],
                        // adds $GLOBALS['_SESSION']['user']->username under '_SESSION:user:email' key
                        ['_SESSION', 'user', 'email'],
                        // adds $GLOBALS['_SESSION']['optionalObject']->someProperty['someSubKey'] value, skips if absent
                        'optionalValue' => ['_SESSION', 'optionalObject', 'someProperty', 'someSubKey'],
                    ],
                    'exceptTitle' => [ // array of regex patterns
                        "/^exception '([^ ]*)' with message/",
                    ],
                ),                
            ),
        ),
        ...
    )
    ...
```


## Copyrights

Copyright (c) 2012 RoliesTheBee

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

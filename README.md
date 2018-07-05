# cross-posting
Auto publishing module for social services (Vkontakte, Odnoklassniki, Facebook)

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist bemulima/yii2-cross-posting "*"
```

or add

```
"bemulima/yii2-cross-posting": "*"
```

to the require section of your `composer.json` file.


Config
-------

```
'modules' => [
        'cross_posting' => [
            'class' => 'bemulima\cross-posting\Module',
        ],
        ...
    ],
'components' => [
    'crossPosting' => [
            'class' => 'bemulima\cross-posting\CrossPosting',
            'services' => [
                'vk' => [
                    'class' => 'bemulima\cross-posting\Vk',
                    'accessToken' => 'XXX',
                    'groups' => [
                        '###',
                        '###',
                    ]
                ],
                'ok' => [
                    'class' => 'bemulima\cross-posting\Ok',
                    'accessToken' => 'XXX',
                    'privateKey' => 'XXX',
                    'publicKey' => 'XXX', 
                    'groups' => [
                        '###',
                        '###',
                    ]
                ],
                'fb' => [
                    'class' => 'bemulima\cross-posting\Fb',
                    'accessToken' => 'XXX',
                    'privateKey' => 'XXX',
                    'publicKey' => 'XXX',
                    'groups' => [
                        '###',
                        '###',
                    ]
                ]
            ]
        ]
    ],
    ...
]
```
Instead of XXX, you have to use yours values. To receive them, you must create applications on the social networks. 
Instead of ###, you have to use yours id groups in social networks

| Client     | Registration address    | 
| --------|---------|
| vkontakte  | https://vk.com/editapp?act=create|
| facebook | https://developers.facebook.com/apps|
| odnoklassniki | https://apiok.ru/dev/app/create|

Usage
--------
in main layout:
```
use budyaga\users\components\AuthorizationWidget;
```

```
$crossPosting = Yii::$app->crossPosting
                ->text($text)
                ->images($images)
                ->url($ad->url);
        
$crossPosting->service('vk')->publish();
$crossPosting->service('ok')->publish();
$crossPosting->service('fb')->publish();
```

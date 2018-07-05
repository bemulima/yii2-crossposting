<?php

/*
 * Класс компонет для кросспостинга
 */

namespace bemulima\crossPosting;

use Yii;
use yii\base\BaseObject;
use yii\base\InvalidParamException;
use yii\web\UrlManager;
use frontend\config\urlrule\ViewUrlRule;
/**
 * Description of CrossPosting
 *
 * @author Programmer
 */
class CrossPosting extends BaseObject {
    
    /**
     * Массив стен, соцсетей
     * @var array
     */
    private $_services = [];
    /**
     * пебликуемый текст
     * @var string
     */
    private $_text;
    /**
     * урл поста
     * @var string
     */
    private $_url;
    /**
     * путь до картинки
     * @var string
     */
    private $_images;

    /**
     * @param array $images puth to img
     * @return $this
     */
    public function images(array $images) {
        $this->_images = $images;
        return $this;
    }
    /**
     * @param string $text text of post
     * @return $this
     */
    public function text($text) {
        $this->_text = $text;
        return $this;
    }
    /**
     * @param string|array $url url to post
     * @return $this
     */
    public function url($url) {
        if(is_array($url)){
            $urlrule = new ViewUrlRule;
            $this->_url = $urlrule->createUrl((new UrlManager), 'ad/view', $url);
        }else
            $this->_url = $url;

        return $this;
    }

    /**
     * @param array $services list
     */
    public function setServices(array $services)
    {
        $this->_services = $services;
    }

    /**
     * Возвращает объект для кросспостинга
     * @return ServiceInterface[]
     */
    public function getServices()
    {
        $services = [];
        foreach ($this->_services as $name) {
            $services[$name] = $this->getService($name);
        }

        return $services;
    }
    
    /**
     * @param string $name crossposting name.
     * @return ServiceInterface.
     * @throws InvalidParamException on non existing client request.
     */
    public function service(string $name)
    {
        if (!array_key_exists($name, $this->_services)) {
            throw new InvalidParamException("Unknown crossposting service '{$name}'.");
        }
        
        if(empty($this->_services[$name]['groups']))
            throw new InvalidParamException("Необходимо ввести хотя б ID одной группы.");
        
        $groups = [];
        foreach($this->_services[$name]['groups'] as $key => $val){

            $groups[] = $val;
        }
        
            
        return $this->createService([
                'class' => $this->_services[$name]['class'],
                'accessToken' => $this->_services[$name]['accessToken'],
                'privateKey' => $this->_services[$name]['privateKey'],
                'publicKey' => $this->_services[$name]['publicKey'],
                'wallIDs' => $groups,
                'text' => $this->_text,
                'url' => $this->_url,
                'images' => $this->_images,
            ]);

    }
    
    /**
     * Создает объект класс для кросспостинга
     * @param array $config конфигурация класса кросспостинга
     * @return ServiceInterface.
     */
    protected function createService($config)
    {
        return Yii::createObject($config);
    }
}

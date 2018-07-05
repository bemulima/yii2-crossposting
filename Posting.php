<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace bemulima\crossPosting;

/**
 * Description of Posting
 *
 * @author Programmer
 */
class Posting extends \yii\base\BaseObject implements PostingInterface  {
    /**
     * Токен для доступа
     * @var string
     */
    public $accessToken;
    /**
     * секретный ключ
     * @var string
     */
    public $privateKey;
    /**
     * публичный ключ
     * @var string
     */
    public $publicKey;
    /**
     * ID стены
     * @var array
     */
    public $wallIDs;
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
     * @var array
     */
    private $_images;

    /**
     * @return string
     */
    public function getText() {
        return $this->_text;
    }
    /**
     * @return string
     */
    public function getUrl() {
        return $this->_url;
    }
    /**
     * @return string
     */
    public function getImages() {
        return $this->_images;
    }
    /**
     * @param string $images puth to img
     */
    public function setImages(array $images) {
        $this->_images = $images;
    }
    /**
     * @param string $text text of post
     */
    public function setText(string $text) {
        $this->_text = $text;
    }
    /**
     * @param string $url url to post
     */
    public function setUrl(string $url) {
        $this->_url = $url;
    }
    /**
     * Публикация
     */
    public function publish() {
        
    }

}

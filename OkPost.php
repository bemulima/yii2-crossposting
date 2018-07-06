<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace bemulima\crossposting;

use yii\base\BaseObject;
/**
 * Description of Ok
 *
 * @author Programmer
 */
class OkPost extends BaseObject {
    
    private $access_token    = "tkn1cH7U3pt8ekFoA1mxR2Lfqz8Zgve4WakTW8znpWY2OYXDoUAWOVS7xbti1lyFIFuPb";  // Наш вечный токен
    private $private_key     = "257FD3C5DBF37855D8653657";  // Секретный ключ приложения
    private $public_key      = "CBAHJMHDEBABABABA";  // Публичный ключ приложения
    private $group_id        = "53693728489577";  // ID нашей группы
    private $step1,$step2,$step3;
    public $message             = "Автопост тестовое сообщение с сайта на одноклассники";  // Сообщение к посту, можно с переносами строки
    public $url;
    public $images = array();
    
    public function __construct($message, $url, $images){
        $this->message = $message;
        $this->url = $url;
        $this->images = $images;
    }
    
    private function getUrl($url, $params = array(), $timeout = 30, $image = false, $decode = true)
    {
        if ($ch = curl_init())
        {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_POST, true);
    
            // Картинка
            if ($image) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            }
            // Обычный запрос       
            elseif($decode) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, urldecode(http_build_query($params)));
            }
            // Текст
            else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            }
    
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Bot');
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    
            $data = curl_exec($ch);
    
            curl_close($ch);
    
            // Еще разок, если API завис
            if (isset($data['error_code']) && $data['error_code'] == 5000) {
                $data = $this->getUrl($url, $params, $timeout, $image, $decode);
            }
    
            return $data;
    
        }
        else {
            return "{}";
        }
    }
    
    private function arInStr($array) // Массив аргументов в строку
    {
        ksort($array);
    
        $string = "";
    
        foreach($array as $key => $val) {
            if (is_array($val)) {
                $string .= $key."=".$this->arInStr($val);
            } else {
                $string .= $key."=".$val;
            }
        }
    
        return $string;
    }
    
    /**
     *  1. Получим адрес для загрузки 1 фото
     */ 
    public function getImageAddress(){

        $params = array(
            "application_key"   =>  $this->public_key,
            "method"            => "photosV2.getUploadUrl",
            "count"             => count($this->images),  // количество фото для загрузки
            "gid"               => $this->group_id,
            "format"            =>  "json"
        );
        $access_token = $this->access_token;
        $private_key = $this->private_key;
        // Подпишем запрос
        $sig = md5( $this->arInStr($params) . md5("{$access_token}{$private_key}") );
        
        $params['access_token'] = $this->access_token;
        $params['sig']          = $sig;
        
        // Выполним
        $this->step1 = json_decode($this->getUrl("https://api.ok.ru/fb.do",  $params), true);
        
        // Если ошибка
        if (isset($this->step1['error_code'])) {
            echo $this->arInStr($params);
            echo '<pre>1'; print_r($params);
            echo '<pre>1'; print_r($this->step1);
            exit();
        }
        return $this;
    }
    /**
     *  2. Закачаем фотку
     */ 
    public function loadImage(){
        
        // Предполагается, что картинка располагается в каталоге со скриптом
        $params = array();// array(  "pic1" => "@picture.jpg" );
        
        for($i=0; $i<count($this->images); $i++){
            $params['pic'.($i+1)] = '@'.$this->images[$i]; 
        }
        
        // Отправляем картинку на сервер, подписывать не нужно
        $this->step2 = json_decode( $this->getUrl( $this->step1['upload_url'], $params, 30, true), true);
        
        // Если ошибка
        if (isset($this->step2['error_code'])) {
            echo '<pre>22'; print_r($this->step1);
            echo '<pre>22'; print_r($this->step2);
            echo '<pre>22'; print_r($params);
            exit();
        }
        return $this;
    }
    
    /**
     * 3. Отправляем весь пост
     */ 
    public function loadPost() {
        
        // Идентификатор для загрузки фото
        //$photo_id = $this->step1['photo_ids'][0];
        
        // Токен загруженной фотки
        //$token = $this->step2['photos'][$photo_id]['token'];
        $photos = '';
        for($i=0; $i<count($this->step1['photo_ids']); $i++){
            $photos .= '{"type": "photo", "list": [{"id": "' . $this->step2['photos'][$this->step1['photo_ids'][$i]]['token'].'"}] },';
        }
        
        // Заменим переносы строк, чтоб не вываливалась ошибка аттача
        $message_json = str_replace("\n", "\\n", $this->message);
        
        // 3. Запостим в группу
        $attachment = '{
                            "media": [
                                {
                                    "type": "text",
                                    "text": "'.$message_json.'"
                                },
                                '.$photos.'
                                {
                                    "type": "link",
                                    "url": "'.$this->url.'"
                                }
                            ]
                        }';
        
        $params = array(
            "application_key"   =>  $this->public_key,
            "method"            =>  "mediatopic.post",
            "gid"               =>  $this->group_id,
            "type"              =>  "GROUP_THEME",
            "attachment"        =>  $attachment,
            "format"            =>  "json",
        );
        
        // Подпишем
        $access_token = $this->access_token;
        $private_key = $this->private_key;
        $sig = md5( $this->arInStr($params) . md5("{$access_token}{$private_key}") );
        
        $params['access_token'] = $this->access_token;
        $params['sig']          = $sig;
        
        $this->step3 = json_decode( $this->getUrl("https://api.ok.ru/fb.do",  $params, 30, false, false ), true);
        
        // Если ошибка
        if (isset($step3['error_code'])) {
            echo '<pre>3'; print_r($this->step3);
            exit();
        }
        
        // Успешно
        echo '<pre>last<br/>'; print_r($this->step3);
    }
    
    public static function writing($message, $url, $images){
        $ok = new OkPost($message, $url, $images);
        return $ok->getImageAddress()
                    ->loadImage()
                    ->loadPost();
        
    }
}

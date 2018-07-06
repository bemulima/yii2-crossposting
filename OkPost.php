<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace bemulima\crossposting;

use yii\base\Component;
/**
 * Description of Ok
 *
 * @author Programmer
 */
class OkPost extends Component {
    
    private $access_token    = "";  // Наш вечный токен
    private $private_key     = "";  // Секретный ключ приложения
    private $public_key      = "";  // Публичный ключ приложения
    private $group_id        = "";  // ID нашей группы
    private $step1,$step2,$step3;
    public $text;  // Сообщение к посту, можно с переносами строки
    public $url;
    public $images = [];

    function setGroupID($group_id) {
        $this->group_id = $group_id;
    }

    public function __construct($access_token, $private_key, $public_key){
        $this->access_token = $access_token;
        $this->private_key = $private_key;
        $this->public_key = $public_key;
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
     * @return array
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
        return $this->step1;
    }
    /**
     *  2. Закачаем фотку
     * @return array
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
            echo '<pre>2'; print_r($this->step1);
            echo '<pre>2'; print_r($this->step2);
            echo '<pre>2'; print_r($params);
            exit();
        }
        return $this->step2;
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
        $text_json = str_replace("\n", "\\n", $this->text);

        // 3. Запостим в группу
        $attachment = '{
                            "media": [
                                {
                                    "type": "text",
                                    "text": "'.$text_json.'"
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
        return $this->step3;
    }
    /**
     * 
     * @return array
     */
    public function wallPost(){
        
        $res = [];
        
        if(!empty($this->images)){
            $res[1] = $this->getImageAddress();
            $res[2] = $this->loadImage();
        }
        $res[3] = $this->loadPost();
        return $res;
        
    }
}

<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace bemulima\crossposting;

use Yii;
use yii\base\Component;
use yii\base\Exception;

/**
 * Description of Vk
 *
 * @author Programmer
 */
class VkPost extends  Component {
    
    
    private $access_token = '';//Постоянный токен для опубликования на стене!
    private $url = "https://api.vk.com/method/";
    private $groupId = "";
    
    /**
     * Конструктор
     */
    public function __construct($access_token = null, $groupId = null) {
        if($access_token !== null)
            $this->access_token = $access_token;
        if($groupId !== null)
            $this->groupId = $groupId;
    }
    /**
     * @param int $groupID
     */
    public function setGroupID($groupID) {
        $this->groupId = $groupID;
    }
    /**
     * Делает запрос к Api VK
     * @param $method
     * @param $params
     */
    public function method($method, array $params = null) {

        $params['access_token'] = $this->access_token;        
        $params['v'] = '3.0';        
        $url = sprintf( 'https://api.vk.com/method/%s', $method);
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $url);
        $response = curl_exec($ch);
        curl_close( $ch);
        
        if( $response ) {
            return json_decode($response);
        }
        return false;
    }
    
    /**
     * @param string $message
     * @param string $attachment
     * @param bool $fromGroup
     * @param bool $signed
     * @return mixed
     */
    public function wallPost($message, $attachment = '', $fromGroup = true, $signed = false)
    {
        return $this->method('wall.post', array(
            'owner_id' => -1 * $this->groupId, //для wall.post id с минусом
            'attachment' => strval($attachment),
            'message' => $message,
            'from_group' => $fromGroup ? 1 : 0,
            'signed' => $signed ? 1 : 0,
        ));
    }
    
    /**
     * @param array $files relative file path
     * @return mixed
     */
    public function createPhotoAttachment(array $files)
    {        
        $result = $this->method('photos.getWallUploadServer', array(
            'gid' => $this->groupId
        ));
        $ch = curl_init($result->response->upload_url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-type: multipart/form-data'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $fields = [];
        
        for ($i = 0; $i < count($files); $i++) {
            $fields['file' . ($i+1)] = (function_exists('curl_file_create')?curl_file_create($files[$i]):'@'.realpath($files[$i]));
            
        }
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

        if (($upload = curl_exec($ch)) === false) {
            throw new Exception(curl_error($ch));
        }
        
        curl_close($ch);
        $upload = json_decode($upload);
        $result = $this->method('photos.saveWallPhoto', array(
            'server' => $upload->server,
            'photo' => $upload->photo,
            'hash' => $upload->hash,
            'group_id' => $this->groupId,
        ));
        
        $ids = [];
        
        foreach ($result->response as $value) {
            $ids[] = $value->id;
        }
        
        return implode(",", $ids);
    }
    
    public function combineAttachments()
    {
        $result = '';
        if (func_num_args() == 0) return '';
        foreach (func_get_args() as $arg) {
            $result .= strval($arg) . ',';
        }
        return substr($result, 0, strlen($result) - 1);
    }

}

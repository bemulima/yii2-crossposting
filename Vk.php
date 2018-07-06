<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace bemulima\crossposting;

use Yii;
use bemulima\crossposting\VkPost;
/**
 * Description of Vk
 *
 * @author Programmer
 */
class Vk extends Posting {
    
    /**
     * Публикация поста в вконтакте
     * @return array - возвращает массив содержавший ID опубликованных постов
     */
    public function publish() {
        $vk = new VkPost($this->accessToken);
        $attachments = '';
        
        if(!empty($this->images)){
            $miniature = $vk->createPhotoAttachment($this->images);
            $attachments =  $vk->combineAttachments( $miniature, Yii::$app->urlManager->createAbsoluteUrl($this->url));
        }elseif(!empty($this->url)){
            $attachments =  $vk->combineAttachments( Yii::$app->urlManager->createAbsoluteUrl($this->url));
        }
        
        $res = [];

        foreach ($this->wallIDs as $group_id) {
            $vk->groupID = $group_id;
            $res[] = $vk->wallPost($this->text,$attachments);
        }
        
        return $res;
    }
}

<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace bemulima\autoposting;

/**
 * Description of Ok
 *
 * @author Programmer
 */
class Ok extends Posting {
    
    /**
     * Публикация поста в одноклассники
     * @return array
     */
    public function publish() {
        $ok = new OkPost($this->accessToken, $this->privateKey, $this->publicKey);
        $ok->text = $this->text; 
        
        $ok->url = $this->url;
        $ok->images = $this->images;
        
        $res = [];

        foreach ($this->wallIDs as $group_id) {
            $ok->groupID = $group_id;
            $res[] = $ok->wallPost();
        }
        
        return $res;
    }
}

<?php

/*
 * Интерфэйс для классов автопостинга
 */

namespace bemulima\crossPosting;

/**
 *
 * @author Programmer
 */
interface PostingInterface {
    
    /**
     * @return string $text text of post
     */
    public function getText();
    /**
     * @return string $images puth to img
     */
    public function getImages();
    /**
     * @return string $url puth to post
     */
    public function getUrl();
    /**
     * @param string $text text of post
     */
    public function setText(string $text);
    /**
     * @param array $images puth to img
     */
    public function setImages(array $images);
    /**
     * @param string $url puth to post
     */
    public function setUrl(string $url);
    /**
     * Публикация поста
     */
    public function publish();
}

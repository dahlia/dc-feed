<?php
require_once dirname(__FILE__) . '/GalleryPageCollection.php';

final class DCInside_Gallery {
    public $id;
    public $title;
    public $url;
    public $pages;

    function __construct($id, $title = null) {
        $this->id = $id;
        $this->title = $title;
        $this->url = "http://gall.dcinside.com/list.php?id=$id";
        $this->pages = new DCInside_GalleryPageCollection($this);

        if (is_null($this->title)) {
            $http = new HTTP_Request($this->url);
            $http->sendRequest();

            ereg('<title>([^<]*)</title>',
                 $http->getResponseBody(),
                 $match);
            $this->title = $match[1];
        }
    }

    function __toString() {
        return (string) $this->title;
    }
}


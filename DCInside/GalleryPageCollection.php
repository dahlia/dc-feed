<?php
require_once 'HTTP/Request.php';
require_once dirname(__FILE__) . '/GalleryPage.php';

final class DCInside_GalleryPageCollection
            implements Iterator, ArrayAccess, Countable {
    public $gallery;
    protected $approximateLength;
    protected $page;
    protected $length;

    function __construct(DCInside_Gallery $gallery) {
        $this->gallery = $gallery;
        $this->approximateLength = null;
        $this->page = 0;
        $this->length = null;
    }

    protected function set_approximate_length($length) {
        $this->approximateLength = $this->approximateLength
                                 ? max($this->approximateLength, $length)
                                 : $length;
    }

    function valid() {
        return $this->page < $this->approximateLength
            || $this->page < $this->count();
    }

    function key() {
        return $this->page;
    }

    function current() {
        return new DCInside_GalleryPage($this->gallery, $this->page + 1);
    }

    function next() {
        ++$this->page;
    }

    function rewind() {
        $this->page = 0;
    }

    function offsetGet($page) {
        try {
            if ($page < 0) {
                $page = $this->count() + $page;
            }

            $page = new DCInside_GalleryPage($this->gallery, $page + 1);
            $this->set_approximate_length($page->page);
            return $page;
        } catch(DCInside_GalleryPageNotExistsException $e) {
            return null;
        }
    }

    function offsetExists($page) {
        $offset = $page < 0 ? -1 - $page : $page;

        return $this->approximateLength && $offset < $this->approximateLength
             ? true
             : $offset < $this->count();
    }

    function offsetSet($page, $_) {
        throw new BadMethodCallException;
    }

    function offsetUnset($page) {
        throw new BadMethodCallException;
    }

    function count() {
        if ($this->length > 0) return $this->length;

        $http = new HTTP_Request($this->gallery->url);
        $http->sendRequest();

        ereg(
            '<strong>([0-9]+) +pages</strong>',
            $http->getResponseBody(),
            $match
        );

        return $this->length = (int) $match[1];
    }
}


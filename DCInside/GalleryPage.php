<?php
require_once 'HTTP/Request.php';
require_once dirname(__FILE__) . '/Gallery.php';
require_once dirname(__FILE__) . '/Article.php';

final class DCInside_GalleryPage
            implements ArrayAccess, Countable, IteratorAggregate {
    const ARTICLE_LINK_PATTERN = '{
        <a \s* href=" (?P<url> /list\.php\?id=[^&]+&no=(?P<no>\d+)&page=\d+ ) "
           \s* > (?P<subject> [^<]+ ) </a>
        .*?
        (?P<created_at> \d{4}-\d\d-\d\d \s+ \d\d:\d\d:\d\d)
    }imsux';

    public $gallery;
    public $page;
    public $url;
    protected $articles;
    protected $length;

    function __construct(DCInside_Gallery $gallery, $page) {
        $this->gallery = $gallery;
        $this->page = $page;
        $this->url = "{$gallery->url}&page=$page";
        $http = new HTTP_Request($this->url);
        $http->sendRequest();

        preg_match_all(
            self::ARTICLE_LINK_PATTERN,
            $http->getResponseBody(),
            $matches,
            PREG_SET_ORDER
        );

        $this->articles = array_map(array($this, 'preg_match_to_article'),
                                    $matches);
        $this->length = count($this->articles);

        if ($this->length < 1) throw new DCInside_GalleryPageNotExists();
    }

    function offsetGet($offset) {
        $offset = $offset < 0 ? $this->count() + $offset : $offset;
        return $this->articles[$offset];
    }

    function offsetExists($offset) {
        return ($offset < 0 ? -1 - $offset: $offset) < $this->count();
    }

    function offsetSet($offset, $value) {
        throw new BadMethodCallException;
    }

    function offsetUnset($offset) {
        throw new BadMethodCallException;
    }

    function count() {
        return $this->length;
    }

    function getIterator() {
        return new ArrayIterator($this->articles);
    }

    protected function preg_match_to_article($match) {
        $subject = substr($match['subject'], -2) == '..'
                 ? null : htmlspecialchars_decode($match['subject']);
        return new DCInside_Article(
            $this->gallery,
            (int) $match['no'],
            $subject,
            $match['created_at'] ? new DateTime($match['created_at']) : null
        );
    }
}

class DCInside_GalleryPageNotExists extends RuntimeException {}


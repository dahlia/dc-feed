<?php
require_once dirname(__FILE__) . '/Gallery.php';
require_once dirname(__FILE__) . '/User.php';
require_once 'HTTP/Request.php';

final class DCInside_Article {
    const AUTHOR_PATTERN = '{
        # author
        <strong> (?: 이 \s* 름 | 갤로거 ) </strong> \s* </a> \s* </td> \s*
        <td [^>]* > \s* </td> \s*
        <td [^>]* > \s*
        <span [^>]* > \s*
            (?: <span [^>]* > \s* )?
                (?P<author> .+? ) \s*
            (?: </span> \s* )?
        </span> \s*
        (?: <img [^>]*? " (?P<author_url> http://gallog.dcinside.com/[^"]+ ) "
                 [^>]* > \s* )?
        </td>
        .*?

        # subject
        <strong> 제 \s* 목 </strong> \s* </a> \s* </td> \s*
        <td [^>]* > \s* </td> \s*
        <td [^>]* > \s*
            (?P<subject> .+? ) \s*
        </td>
    }imsux';

    const CONTENT_PATTERN = '{
        <span \s* style=line-height: \s* 160%> \s*
        (<div \s* style=["\'] position: \s* relative; ["\'] \s* > )?
        (?P<content> .+ )
        <br> \s* <div \s* align=right \s*
                          style=font-family:tahoma;font-size=8pt>
        .+?
        (?P<created_at> \d{4}-\d{2}-\d{2} \s+ \d{2}:\d{2}:\d{2})
    }imsux';

    public $gallery;
    public $id;
    public $url;
    protected $_subject;
    protected $_createdAt;
    protected $_author;
    protected $_content;

    function __construct(DCInside_Gallery $gallery, $id, $subject = null,
                         DateTime $createdAt = null,
                         DCInside_User $author = null) {
        $this->gallery = $gallery;
        $this->id = $id;
        $this->url = "{$gallery->url}&no=$id";
        $this->_subject = $subject;
        $this->_createdAt = $createdAt;
        $this->_author = $author;
        $this->_content = null;
    }

    function __get($name) {
        $lazyAttributes = array('subject', 'createdAt', 'author', 'content');
        if (!in_array($name, $lazyAttributes)) return null;

        $name = "_$name";
        if (is_null($this->$name)) {
            $this->retrieve();
        }

        return $this->$name;
    }

    protected function retrieve() {
        $http = new HTTP_Request($this->url);
        $http->sendRequest();
        $body = $http->getResponseBody();

        if (preg_match(self::AUTHOR_PATTERN, $body, $subject)) {
            $this->_subject = htmlspecialchars_decode($subject['subject']);
            $this->_author = new DCInside_User(
                htmlspecialchars_decode($subject['author']),
                htmlspecialchars_decode($subject['author_url'])
            );
        } else {
            $this->_subject = '';
            $this->_author = null;
        }

        if (preg_match(self::CONTENT_PATTERN, $body, $content)) {
            $this->_createdAt = new DateTime($content['created_at']);
            $this->_content = $content['content'];
        } else {
            $this->_createdAt = $this->_content = '';
        }
    }

    function __toString() {
        return $this->__get('subject');
    }
}


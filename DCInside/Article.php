<?php
require_once dirname(__FILE__).'/Gallery.php';
require_once dirname(__FILE__).'/User.php';
require_once 'HTTP/Request.php';

final class DCArticle {
	public $gallery;
	public $id;
	protected $_subject;
	protected $_createdAt;
	protected $_author;
	protected $_content;
	public $url;

	function __construct(DCGallery $gallery, $id, $subject = null, DateTime $createdAt = null, DCUser $author = null) {
		$this->gallery = $gallery;
		$this->id = $id;
		$this->_subject = $subject;
		$this->_createdAt = $createdAt;
		$this->_author = $author;
		$this->_content = null;

		$this->url = $gallery->url."&no=$id";
	}

	function __get($name) {
		$lazyAttributes = array('subject', 'createdAt', 'author', 'content');

		if(!in_array($name, $lazyAttributes))
			return null;

		$name = "_$name";
		if(is_null($this->$name))
			$this->retrieve();
		return $this->$name;
	}

	protected function retrieve() {
		$http = new HTTP_Request($this->url);
		$http->sendRequest();
		$body = $http->getResponseBody();
		
		preg_match('{<td align=left width=100%> *(?:<span .+?onClick[^>]+>)? *(<span +title=[^>]+>)?(?P<author>.+?)</span>(?:</td>|</span>.+?(?P<author_url>http://gallog.dcinside.com/[^"\']+)")?.+?<td align=left> *(?P<subject>.+?)</td>}imsu', $body, $author_subject);

		preg_match('{<span *style=line-height: *160%>\s*(<div *style=["\']position: *relative;["\'] *>)?(?P<content>.+)<br>\s*<div align=right style=font-family:tahoma;font-size=8pt>.+?(?P<created_at>\\d{4}-\\d{2}-\\d{2} +\\d{2}:\\d{2}:\\d{2})}imsu', $body, $content_createdAt);
		
		$this->_subject = htmlspecialchars_decode($author_subject['subject']);
		$this->_createdAt = new DateTime($content_createdAt['created_at']);
		$this->_content = $content_createdAt['content'];
		
		$this->_author = new DCUser(
			htmlspecialchars_decode($author_subject['author']),
			htmlspecialchars_decode($author_subject['author_url'])
		);
	}

	function __toString() {
		return $this->__get('subject');
	}
}

<?php
require_once dirname(__FILE__).'/Gallery.php';
require_once dirname(__FILE__).'/Article.php';
require_once 'HTTP/Request.php';

final class DCGalleryPage implements ArrayAccess, Countable, IteratorAggregate {
	public $gallery;
	public $page;
	public $url;
	protected $articles;
	protected $length;

	function __construct(DCGallery $gallery, $page) {
		$this->gallery = $gallery;
		$this->page = $page;
		$this->url = $gallery->url."&page=$page";

		$http = new HTTP_Request($this->url);
		$http->sendRequest();

		preg_match_all(
			'{<a *href="(?P<url>/list\.php\?id=[^&]+&no=(?P<no>[0-9]+)&page=[0-9]+)" *>(?P<subject>[^<]+)</a>.*?<span +(?:title=["\'][^"\']+["\'] +)?onClick=[^>]+>(?:<span +[^>]+>)?(?P<author>.*?)</span>(?:[^<]*</td>|</span>.*?(?P<author_url>http://gallog.dcinside.com/[^"\']+))?.*?(?P<created_at>\\d{4}-\\d{2}-\\d{2} +\\d{2}:\\d{2}:\\d{2})}imsu',
			$http->getResponseBody(),
			$matches,
			PREG_SET_ORDER
		);

		$this->articles = array_map(array($this, 'preg_match_to_article'), $matches);
		$this->length = count($this->articles);

		if($this->length < 1)
			throw new DCGalleryPageNotExists();
	}

	function offsetGet($offset) {
		$offset = $offset < 0 ? $this->count() + $offset : $offset;
		return $this->articles[$offset];
	}

	function offsetExists($offset) {
		return ($offset < 0 ? -1 - $offset: $offset) < $this->count();
	}

	function offsetSet($offset, $value) {}
	function offsetUnset($offset) {}

	function count() {
		return $this->length;
	}

	function getIterator() {
		return new ArrayIterator($this->articles);
	}

	protected function preg_match_to_article($match) {
		$author	= ($match['author'] and substr($match['author'], -2) != '..')
				? new DCUser(htmlspecialchars_decode($match['author']), $match['author_url'])
				: null;
		
		return new DCArticle(
			$this->gallery,
			(int) $match['no'],
			substr($match['subject'], -2) == '..' ? null : htmlspecialchars_decode($match['subject']),
			$match['created_at'] ? new DateTime($match['created_at']) : null,
			$author
		);
	}
}

class DCGalleryPageNotExists extends RuntimeException {}


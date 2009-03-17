<?php
require_once dirname(__FILE__).'/GalleryPage.php';
require_once 'HTTP/Request.php';

final class DCGalleryPageCollection implements Iterator, ArrayAccess, Countable {
	const FirstPageNumber = 1;
	
	public $gallery;
	protected $approximateLength;
	protected $page;
	protected $length;

	function __construct(DCGallery $gallery) {
		$this->gallery = $gallery;
		$this->approximateLength = null;
		$this->page = 0;
		$this->length = null;
	}

	protected function set_approximate_length($length) {
		$this->approximateLength	= $this->approximateLength
									? max($this->approximateLength, $length)
									: $length;
	}

	function valid() {
		return $this->page < $this->approximateLength or $this->page < $this->count();
	}

	function key() {
		return $this->page;
	}

	function current() {
		return new DCGalleryPage($this->gallery, $this->page + 1);
	}

	function next() {
		++$this->page;
	}

	function rewind() {
		$this->page = 0;
	}

	function offsetGet($page) {
		try {
			if($page < 0)
				$page = $this->count() + $page;

			$page = new DCGalleryPage($this->gallery, $page + 1);
			$this->set_approximate_length($page->page);
			return $page;
		}
		catch(DCGalleryPageNotExistsException $e) {
			return null;
		}
	}

	function offsetExists($page) {
		$offset = $page < 0 ? -1 - $page : $page;

		if($this->approximateLength and $offset < $this->approximateLength)
			return true;
		else
			return $offset < $this->count();
	}

	function offsetSet($page, $_) {}
	function offsetUnset($page) {}

	function count() {
		if($this->length > 0)
			return $this->length;

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

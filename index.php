<?php
if(!$_GET['id'])
	exit;

require_once 'DC/Gallery.php';

$limit		= max($_GET['limit'], 0);
$url		= 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
$imageProxy	= dirname($url).'/image-proxy.php';
$gallery	= new DCGallery($_GET['id']);

$articles = call_user_func_array(
	'array_merge',
	array_map(
		'iterator_to_array',
		array_map(array($gallery->pages, 'offsetGet'), range(0, $limit))
	)
);

$updatedAt = array_reduce(
	$articles,
	create_function('$a, $b', 'return $a->createdAt > $b->createdAt ? $a : $b;')
)->createdAt;

for($i = 0, $count = count($articles); $i < $count; ++$i) {
	if($updatedAt == $articles[$i]->createdAt)
		break;
	unset($articles[$i]);
}

function content($content) {
	global $imageProxy;

	return ereg_replace(
		'http://(img[0-9]+).dcinside.com/viewimage.php(\\?[^"\']+)',
		$imageProxy.'/\\1\\2',
		$content
	);
}

function cdata($string) {
	echo htmlspecialchars($string);
}

header('Content-Type: text/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8" ?>';
?>

<feed xmlns="http://www.w3.org/2005/Atom">
	<title><?php cdata($gallery) ?></title>
	<id><?php cdata($url) ?></id>
	<updated><?php echo $updatedAt->format(DATE_ATOM) ?></updated>
	<link rel="self" href="<?php cdata($url) ?>" type="application/atom+xml" />
	<link rel="alternate" href="<?php cdata($gallery->url) ?>" type="text/html" />

	<?php foreach($articles as $article): ?>
		<entry>
			<id><?php cdata($article->url) ?></id>
			<title><?php cdata($article) ?></title>
			<link rel="alternate" href="<?php cdata($article->url) ?>" type="text/html" />
			<published><?php echo $article->createdAt->format(DATE_ATOM) ?></published>
			<updated><?php echo $article->createdAt->format(DATE_ATOM) ?></updated>
			
			<author>
				<name><?php cdata($article->author) ?></name>
				<?php if($article->author->url): ?>
					<uri><?php cdata($article->author->url) ?></uri>
				<?php endif ?>
			</author>

			<summary type="html"><?php cdata(content($article->content)) ?></summary>
			<content type="html"><?php cdata(content($article->content)) ?></content>
		</entry>
	<?php endforeach ?>
</feed>

<?php
if (get_magic_quotes_gpc()) {
    $_GET = array_map('stripslashes', $_GET);
}

if (empty($_GET['id'])) exit;

@set_time_limit(120);
require_once 'DCInside/Gallery.php';

$id      = $_GET['id'];
$limit   = empty($_GET['limit']) ? 0 : max($_GET['limit'], 0);
$url     = "http://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}";
$gallery = new DCInside_Gallery($id);

if (!trim($gallery->title) && !empty($_GET['title'])) {
    if (function_exists('iconv') && function_exists('mb_detect_encoding')) {
        $gallery->title = iconv(
            mb_detect_encoding($_GET['title'], 'auto'),
            'UTF-8', $_GET['title']
        );
    } else {
        $gallery->title = function_exists('mb_convert_encoding')
                        ? mb_convert_encoding($_GET['title'], 'utf-8', 'auto')
                        : $_GET['title'];

    }
}

function isEmptyLine($line) {
	return $line && $line[0] != '#';
}

function ignoreEmptyLines($lines) {
	return array_filter(array_map('trim', (array) $lines), 'isEmptyLine');
}

$imageProxies = ignoreEmptyLines(@file('image-proxies'));
$imageProxies[] = dirname($url) . '/image-proxy.php';
shuffle($imageProxies);

$articles = call_user_func_array(
	'array_merge',
	array_map(
		'iterator_to_array',
		array_map(array($gallery->pages, 'offsetGet'), range(0, $limit))
	)
);

$updatedAt = @array_reduce(
	$articles,
	create_function('$a, $b', 'return $a->createdAt > $b->createdAt ? $a : $b;')
)->createdAt;

for($i = 0, $count = count($articles); $i < $count; ++$i) {
	if ($updatedAt == $articles[$i]->createdAt) break;
	unset($articles[$i]);
}

$ignorePatterns = array_merge(
	ignoreEmptyLines(@file('ignore-patterns/__all__')),
	ignoreEmptyLines(@file("ignore-patterns/$id"))
);

function isIgnored($article) {
	global $ignorePatterns;
    if (empty($article->author) || empty($article->createdAt)) return false;
	foreach ($ignorePatterns as $pattern) {
		if (@preg_match($pattern, $article->subject)) return true;
	    if (@preg_match($pattern, $article->content)) return true;
	}
	return false;
}

function content($content) {
	global $imageProxies;

	$imageProxy = current($imageProxies);
	if (!next($imageProxies)) {
		reset($imageProxies);
    }

	return ereg_replace(
		'http://(img[0-9]+).dcinside.com/viewimage.php(\\?[^"\']+)',
		"$imageProxy/\\1\\2",
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
  <?php foreach ($articles as $article): ?>
    <?php if (!isIgnored($article)): ?>
      <entry>
        <id><?php cdata($article->url) ?></id>
        <title><?php cdata($article) ?></title>
        <link rel="alternate"
              href="<?php cdata($article->url) ?>"
              type="text/html" />
        <published><?php
            echo $article->createdAt->format(DATE_ATOM) ?></published>
        <updated><?php echo $article->createdAt->format(DATE_ATOM) ?></updated>
        <author>
            <name><?php cdata($article->author) ?></name>
            <?php if ($article->author->url): ?>
                <uri><?php cdata($article->author->url) ?></uri>
            <?php endif ?>
        </author>
        <summary type="html"><?php cdata(content($article->content))?></summary>
        <content type="html"><?php cdata(content($article->content))?></content>
      </entry>
    <?php endif ?>
  <?php endforeach ?>
</feed>

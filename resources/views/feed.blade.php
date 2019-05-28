<?php declare(strict_types=1);

use Adduc\SimpleXMLElement;
use App\User;
use App\Item;
use Illuminate\Support\Facades\Redis;

$user = app(User::class);
$cache_key = 'feed:' . $feed->id . $feed->last_change->getTimestamp();
$cache = Redis::get($cache_key);

if ($cache) {
    echo str_replace(
        ['##user##', '##pass##'],
        [$user->rss_user, $user->rss_password],
        $cache
    );
    return;
}

$atom_ns = 'http://www.w3.org/2005/Atom';
$itunes_ns = 'http://www.itunes.com/dtds/podcast-1.0.dtd';
$googleplay_ns = 'http://www.google.com/schemas/play-podcasts/1.0';

$str = sprintf(
    '<?xml version="1.0" encoding="utf-8"?>'
    . '<rss version="2.0" xmlns:atom="%s" xmlns:itunes="%s" xmlns:googleplay="%s"></rss>',
    $atom_ns,
    $itunes_ns,
    $googleplay_ns
);
$xml = new SimpleXMLElement($str);
$channel = $xml->addChild('channel');

$protocol = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
$domain = $_SERVER['HTTP_HOST'];

$base_uri = "{$protocol}://{$domain}";
$url = "{$base_uri}/shows/{$feed->id}/feed";

$atom = $channel->addChild('link', '', $atom_ns);
$atom['href'] = $url;
$atom['rel'] = "self";
$atom['type'] = "application/rss+xml";

$channel->addChild('link', $url);

$channel->title = $feed->title;
$channel->addChild('lastBuildDate', $feed->last_change->format(DateTime::RSS));
$channel->addChild('block', 'yes', $itunes_ns);
$channel->addChild('block', 'yes', $googleplay_ns);

$description = sprintf(
    "%s\n\nFeed generated by <a href='%s'>Unofficial RSS Feeds for Stitcher Premium</a>.",
    $feed->description,
    $base_uri
);

$channel->addCData('description', $description);
$image = $channel->addChild('image');
$image->url = $feed->image_url;
$image->title = $feed->title;
$image->link = $url;

$items = $feed->items->all();

usort($items, function ($a, $b) {
    return -($a->pub_date <=> $b->pub_date) ?: -($a->id <=> $b->id);
});

$used_urls = [];

foreach ($items as $item) {
    // Skip episodes that have already been placed in the feed.
    if (isset($used_urls[$item->enclosure_url])) {
        continue;
    }

    $used_urls[$item->enclosure_url] = 1;

    $rss_item = $channel->addChild('item');
    $rss_item->addCData('title', $item->title ?: '');
    $rss_item->addCData('description', $item->description ?: '');
    $rss_item->pubDate = $item->pub_date->format(DateTime::RSS);

    $rss_item->addChild('duration', $item->itunes_duration, $itunes_ns);

    if ($item->itunes_season) {
        $rss_item->addChild('season', (string)$item->itunes_season, $itunes_ns);
    }

    $rss_item->guid = "{$protocol}://{$domain}/shows/{$feed->id}/episodes/{$item->id}";
    $rss_item->guid['isPermaLink'] = "false";

    $url = sprintf(
        "{$protocol}://{$domain}/shows/%d/episodes/##user##/##pass##/%d.mp3",
        $feed->id,
        $item->id
    );

    $enclosure = $rss_item->addChild('enclosure', null, null);
    $enclosure['url'] = $url;
    $enclosure['length'] = 1;
    $enclosure['type'] = 'audio/mpeg';
}

$cache = $xml->asXML();

Redis::set($cache_key, $cache);

echo str_replace(
    ['##user##', '##pass##'],
    [$user->rss_user, $user->rss_password],
    $cache
);

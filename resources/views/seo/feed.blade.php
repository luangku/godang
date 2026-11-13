<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title>{{ $site['site_title'] ?? config('app.name') }}</title>
    <link>{{ url('/blog') }}</link>
    <atom:link href="{{ url('/feed.xml') }}" rel="self" type="application/rss+xml"/>
    <description>Kumpulan catatan masalah teknis beserta solusinya.</description>
    <language>id</language>
    <lastBuildDate>{{ now()->toRfc7231String() }}</lastBuildDate>
@foreach($posts as $post)
    <item>
        <title>{{ $post->title }}</title>
        <link>{{ url('/blog/'.$post->slug) }}</link>
        <guid isPermaLink="true">{{ url('/blog/'.$post->slug) }}</guid>
        <pubDate>{{ optional($post->published_at)->toRfc7231String() }}</pubDate>
        <description>{{ $post->excerpt }}</description>
    </item>
@endforeach
</channel>
</rss>

<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ url('/') }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ url('/browse') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc>{{ url('/profiles') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.4</priority>
    </url>
    @foreach($presets as $preset)
    <url>
        <loc>{{ url('/guides/' . $preset) }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach
    @foreach($genres as $genre)
    <url>
        <loc>{{ url('/games/genre/' . $genre->slug) }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
    @endforeach
    @foreach($platforms as $platform)
    <url>
        <loc>{{ url('/games/platform/' . $platform->slug) }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
    @endforeach
</urlset>

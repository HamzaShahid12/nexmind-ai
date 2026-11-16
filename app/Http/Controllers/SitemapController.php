<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;

/** Dynamic sitemap of the PUBLIC SPA URLs (never dashboard/admin). Served at /sitemap.xml by the API host. */
class SitemapController extends Controller
{
    private const PAGES = ['', 'features', 'ai-tools', 'pricing', 'about', 'contact', 'blog', 'faq'];

    public function __invoke()
    {
        $base = rtrim(config('nexmind.frontend_url'), '/');
        $urls = [];

        foreach (self::PAGES as $page) {
            $urls[] = ['loc' => $base.($page === '' ? '/' : '/'.$page), 'lastmod' => null];
        }

        BlogPost::query()->published()->orderByDesc('published_at')->get(['slug', 'updated_at'])->each(function ($post) use (&$urls, $base) {
            $urls[] = ['loc' => $base.'/blog/'.$post->slug, 'lastmod' => $post->updated_at?->toAtomString()];
        });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc>'
                .($url['lastmod'] ? '<lastmod>'.$url['lastmod'].'</lastmod>' : '').'</url>'."\n";
        }
        $xml .= '</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}

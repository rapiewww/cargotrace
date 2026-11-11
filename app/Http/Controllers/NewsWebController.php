<?php

namespace App\Http\Controllers;

use App\Services\MaritimeNewsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsWebController extends Controller
{
    public function __construct(
        protected MaritimeNewsService $newsService
    ) {}

    /**
     * Display the dedicated Maritime News Wire Hub.
     */
    public function index(Request $request): View
    {
        $allNews = $this->newsService->getMaritimeNews(24);

        $selectedCategory = $request->query('category', 'all');
        $searchQuery = trim((string) $request->query('q', ''));
        $selectedSource = $request->query('source', 'all');

        // Extract available categories & sources for filters
        $categories = collect($allNews)->pluck('category')->filter()->unique()->values()->all();
        $sources = collect($allNews)->pluck('source')->filter()->unique()->values()->all();

        // Filter news items
        $filteredNews = collect($allNews)->filter(function ($item) use ($selectedCategory, $searchQuery, $selectedSource) {
            if ($selectedCategory !== 'all' && strcasecmp($item['category'] ?? '', $selectedCategory) !== 0) {
                return false;
            }
            if ($selectedSource !== 'all' && strcasecmp($item['source'] ?? '', $selectedSource) !== 0) {
                return false;
            }
            if (! empty($searchQuery)) {
                $haystack = strtolower(($item['headline'] ?? '') . ' ' . ($item['summary'] ?? '') . ' ' . ($item['category'] ?? ''));
                if (! str_contains($haystack, strtolower($searchQuery))) {
                    return false;
                }
            }
            return true;
        })->values()->all();

        $dataSource = $allNews[0]['data_source'] ?? 'live';

        return view('news.index', compact(
            'filteredNews',
            'allNews',
            'categories',
            'sources',
            'selectedCategory',
            'searchQuery',
            'selectedSource',
            'dataSource'
        ));
    }
}


<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Services\MaritimeNewsService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class NewsWebController extends Controller
{
    public function __construct(
        protected MaritimeNewsService $newsService
    ) {}

    /**
     * Display the dedicated Maritime News Wire Hub with server-side filters and pagination.
     */
    public function index(Request $request): View
    {
        $rawNews = $this->newsService->getMaritimeNews(48);

        // Fetch user's active shipments for relevance matching
        $user = $request->user();
        $shipments = [];
        if ($user) {
            $shipmentQuery = Shipment::query()->with(['originLocation', 'destinationLocation', 'currentLocation', 'carrier']);
            if ($user->role === 'customer') {
                $shipmentQuery->where('customer_id', $user->id);
            }
            $shipments = $shipmentQuery->whereIn('status', ['IN_TRANSIT', 'PICKED_UP', 'BOOKED'])->get();
        }

        $allNews = $this->newsService->tagShipmentRelevance($rawNews, $shipments);

        $selectedCategory = $request->query('category', 'all');
        $searchQuery = trim((string) $request->query('q', ''));
        $selectedSource = $request->query('source', 'all');
        $onlyRelevant = (bool) $request->query('relevant', false);

        // Extract available categories & sources for filters
        $categories = collect($allNews)->pluck('category')->filter()->unique()->values()->all();
        $sources = collect($allNews)->pluck('source')->filter()->unique()->values()->all();

        // Filter news items
        $filteredNews = collect($allNews)->filter(function ($item) use ($selectedCategory, $searchQuery, $selectedSource, $onlyRelevant) {
            if ($onlyRelevant && empty($item['is_relevant'])) {
                return false;
            }
            if ($selectedCategory !== 'all' && strcasecmp($item['category'] ?? '', $selectedCategory) !== 0) {
                return false;
            }
            if ($selectedSource !== 'all' && strcasecmp($item['source'] ?? '', $selectedSource) !== 0) {
                return false;
            }
            if (! empty($searchQuery)) {
                $haystack = strtolower(($item['headline'] ?? '').' '.($item['summary'] ?? '').' '.($item['category'] ?? ''));
                if (! str_contains($haystack, strtolower($searchQuery))) {
                    return false;
                }
            }

            return true;
        })->values()->all();

        // Server-side pagination
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 9;
        $total = count($filteredNews);
        $offset = ($page - 1) * $perPage;
        $itemsForCurrentPage = array_slice($filteredNews, $offset, $perPage);

        $paginatedNews = new LengthAwarePaginator(
            $itemsForCurrentPage,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $dataSource = $allNews[0]['data_source'] ?? 'live';
        $relevantCount = collect($allNews)->where('is_relevant', true)->count();

        return view('news.index', compact(
            'paginatedNews',
            'allNews',
            'categories',
            'sources',
            'selectedCategory',
            'searchQuery',
            'selectedSource',
            'onlyRelevant',
            'dataSource',
            'relevantCount'
        ));
    }
}

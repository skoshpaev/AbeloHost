<?php

declare(strict_types=1);

namespace App\Service\ViewTracker;

use App\Http\Request;
use App\Repository\RedisViewBufferRepository;
use App\Service\VisitorIdentity\Api\VisitorIdentityServiceInterface;
use App\Service\ViewTracker\Api\ViewTrackerServiceInterface;
use Throwable;

final readonly class ViewTrackerService implements ViewTrackerServiceInterface
{
    public function __construct(
        private RedisViewBufferRepository $buffer,
        private VisitorIdentityServiceInterface $visitorIdentity,
    ) {
    }

    public function track(Request $request, int $articleId, int $storedViews): array
    {
        if ($request->method !== 'GET') {
            return ['views' => $storedViews, 'headers' => []];
        }

        $headers = [];
        try {
            $identity = $this->visitorIdentity->resolve(
                $request->cookies[$this->visitorIdentity->cookieName()] ?? null
            );
            $headers['Set-Cookie'] = $this->visitorIdentity->header($identity['cookie'], $request->secure);
            $this->buffer->record($articleId, $identity['id'], $storedViews);
            $views = $this->buffer->total($articleId) ?? $storedViews;

            return ['views' => $views, 'headers' => $headers];
        } catch (Throwable $error) {
            error_log('View tracking failed: ' . $error->getMessage());

            return ['views' => $storedViews, 'headers' => $headers];
        }
    }

    public function display(array $articles): array
    {
        $fallback = [];
        foreach ($articles as $article) {
            if (isset($article['id'], $article['views'])) {
                $fallback[(int)$article['id']] = (int)$article['views'];
            }
        }
        try {
            $totals = $this->buffer->totals($fallback);
        } catch (Throwable $error) {
            error_log('View totals unavailable: ' . $error->getMessage());
            return $articles;
        }
        foreach ($articles as &$article) {
            $id = (int)($article['id'] ?? 0);
            if (isset($totals[$id])) {
                $article['views'] = $totals[$id];
            }
        }
        unset($article);
        return $articles;
    }
}

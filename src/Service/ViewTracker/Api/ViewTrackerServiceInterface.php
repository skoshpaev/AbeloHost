<?php
declare(strict_types=1);

namespace App\Service\ViewTracker\Api;

use App\Http\Request;

interface ViewTrackerServiceInterface
{
    public function track(Request $request, int $articleId, int $storedViews): array;

    public function display(array $articles): array;
}

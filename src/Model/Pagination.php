<?php

declare(strict_types=1);

namespace App\Model;

use App\Http\HttpException;

final class Pagination
{
    public const PER_PAGE = 9;

    public static function parsePage(mixed $value): int
    {
        if ($value === null) {
            return 1;
        }
        if (!is_string($value) || !preg_match('/^[1-9][0-9]{0,6}$/D', $value)) {
            throw new HttpException(400, 'Номер страницы должен быть положительным целым числом.');
        }
        return (int)$value;
    }

    public static function parseSort(mixed $value): string
    {
        if ($value === null) {
            return 'date';
        }
        if (!in_array($value, ['date', 'views'], true)) {
            throw new HttpException(400, 'Неизвестный способ сортировки.');
        }
        return $value;
    }

    public static function build(int $page, int $total): array
    {
        $pages = max(1, (int)ceil($total / self::PER_PAGE));
        if ($page > $pages) {
            throw new HttpException(404, 'Такой страницы в категории нет.');
        }
        $numbers = array_values(
            array_unique(array_merge([1], range(max(1, $page - 2), min($pages, $page + 2)), [$pages]))
        );
        return ['page' => $page, 'pages' => $pages, 'total' => $total, 'numbers' => $numbers];
    }
}

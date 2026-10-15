<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Http\HttpException;
use App\Model\Pagination;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PaginationTest extends TestCase
{
    public function testDefaults(): void
    {
        self::assertSame(1, Pagination::parsePage(null));
        self::assertSame('date', Pagination::parseSort(null));
        self::assertSame(9, Pagination::PER_PAGE);
    }

    #[DataProvider('invalidPages')]
    public function testRejectsInvalidPage(mixed $page): void
    {
        try {
            Pagination::parsePage($page);
            self::fail('Invalid page was accepted.');
        } catch (HttpException $error) {
            self::assertSame(400, $error->status);
        }
    }

    public static function invalidPages(): array
    {
        return [['0'], ['-1'], ['1.5'], ['01'], ['1e3'], ['10000000'], ['1 OR 1=1'], [''], [['1']], [1]];
    }

    #[DataProvider('invalidSorts')]
    public function testRejectsInvalidSort(mixed $sort): void
    {
        try {
            Pagination::parseSort($sort);
            self::fail('Invalid sort was accepted.');
        } catch (HttpException $error) {
            self::assertSame(400, $error->status);
        }
    }

    public static function invalidSorts(): array
    {
        return [['id'], ['views DESC; DROP TABLE articles'], [['views']], ['']];
    }

    public function testPagesAndBoundaries(): void
    {
        self::assertSame(2, Pagination::parsePage('2'));
        self::assertSame('views', Pagination::parseSort('views'));
        self::assertSame(['page' => 1, 'pages' => 1, 'total' => 0, 'numbers' => [1]], Pagination::build(1, 0));
        self::assertSame([1, 4, 5, 6, 7, 8, 20], Pagination::build(6, 180)['numbers']);
        self::assertSame(2, Pagination::build(2, 10)['pages']);
        try {
            Pagination::build(3, 10);
            self::fail('Out-of-range page was accepted.');
        } catch (HttpException $error) {
            self::assertSame(404, $error->status);
        }
    }
}

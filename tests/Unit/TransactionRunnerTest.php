<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Infrastructure\TransactionRunner;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TransactionRunnerTest extends TestCase
{
    public function testRetriesWholeTransactionAfterDeadlock(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::exactly(2))->method('beginTransaction')->willReturn(true);
        $pdo->expects(self::once())->method('inTransaction')->willReturn(true);
        $pdo->expects(self::once())->method('rollBack')->willReturn(true);
        $pdo->expects(self::once())->method('commit')->willReturn(true);
        $calls = 0;
        $result = (new TransactionRunner($pdo))->run(static function () use (&$calls): int {
            $calls++;
            if ($calls === 1) {
                $error = new PDOException('Deadlock');
                $error->errorInfo = ['40001', 1213, 'Deadlock'];
                throw $error;
            }
            return 42;
        });
        self::assertSame(42, $result);
        self::assertSame(2, $calls);
    }

    public function testDoesNotRetryUnrelatedErrors(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::once())->method('beginTransaction')->willReturn(true);
        $pdo->expects(self::once())->method('inTransaction')->willReturn(true);
        $pdo->expects(self::once())->method('rollBack')->willReturn(true);
        $pdo->expects(self::never())->method('commit');
        $this->expectException(RuntimeException::class);
        (new TransactionRunner($pdo))->run(static fn () => throw new RuntimeException('Invalid data'));
    }

    public function testRetriesAreBounded(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::exactly(3))->method('beginTransaction')->willReturn(true);
        $pdo->expects(self::exactly(3))->method('inTransaction')->willReturn(true);
        $pdo->expects(self::exactly(3))->method('rollBack')->willReturn(true);
        $pdo->expects(self::never())->method('commit');
        $error = new PDOException('Lock timeout');
        $error->errorInfo = ['HY000', 1205, 'Lock timeout'];
        $this->expectException(PDOException::class);
        (new TransactionRunner($pdo))->run(static fn () => throw $error);
    }
}

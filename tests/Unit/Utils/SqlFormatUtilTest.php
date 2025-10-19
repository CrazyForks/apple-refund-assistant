<?php

namespace Tests\Unit\Utils;

use App\Utils\SqlFormatUtil;
use Illuminate\Database\Events\QueryExecuted;
use Tests\TestCase;

class SqlFormatUtilTest extends TestCase
{
    protected function makeQueryExecuted(string $sql, array $bindings = [], float $time = 10.5): QueryExecuted
    {
        $connection = $this->app->make('db')->connection();

        return new QueryExecuted($sql, $bindings, $time, $connection);
    }

    public function test_format_with_string_binding(): void
    {
        $query = $this->makeQueryExecuted(
            'SELECT * FROM users WHERE name = ?',
            ['John Doe'],
            15.23
        );

        $result = SqlFormatUtil::format('Query-001', $query);

        $this->assertStringContainsString('Query-001', $result);
        $this->assertStringContainsString('15.23 ms', $result);
        $this->assertStringContainsString("name = 'John Doe'", $result);
    }

    public function test_format_with_multiple_string_bindings(): void
    {
        $query = $this->makeQueryExecuted(
            'SELECT * FROM users WHERE name = ? AND email = ?',
            ['John', 'john@example.com'],
            20.5
        );

        $result = SqlFormatUtil::format('Query-002', $query);

        $this->assertStringContainsString("name = 'John'", $result);
        $this->assertStringContainsString("email = 'john@example.com'", $result);
    }

    public function test_format_with_null_binding(): void
    {
        $query = $this->makeQueryExecuted(
            'SELECT * FROM users WHERE deleted_at = ?',
            [null],
            5.0
        );

        $result = SqlFormatUtil::format('Query-003', $query);

        $this->assertStringContainsString('deleted_at = NULL', $result);
    }

    public function test_format_with_boolean_bindings(): void
    {
        $query = $this->makeQueryExecuted(
            'SELECT * FROM users WHERE is_active = ? AND is_deleted = ?',
            [true, false],
            8.75
        );

        $result = SqlFormatUtil::format('Query-004', $query);

        $this->assertStringContainsString('is_active = TRUE', $result);
        $this->assertStringContainsString('is_deleted = FALSE', $result);
    }

    public function test_format_with_numeric_bindings(): void
    {
        $query = $this->makeQueryExecuted(
            'SELECT * FROM users WHERE id = ? AND age > ?',
            [123, 18],
            12.5
        );

        $result = SqlFormatUtil::format('Query-005', $query);

        $this->assertStringContainsString('id = 123', $result);
        $this->assertStringContainsString('age > 18', $result);
    }

    public function test_format_with_mixed_bindings(): void
    {
        $query = $this->makeQueryExecuted(
            'SELECT * FROM users WHERE name = ? AND age = ? AND deleted_at = ? AND is_active = ?',
            ['Alice', 25, null, true],
            18.99
        );

        $result = SqlFormatUtil::format('Query-006', $query);

        $this->assertStringContainsString("name = 'Alice'", $result);
        $this->assertStringContainsString('age = 25', $result);
        $this->assertStringContainsString('deleted_at = NULL', $result);
        $this->assertStringContainsString('is_active = TRUE', $result);
    }

    public function test_format_without_bindings(): void
    {
        $query = $this->makeQueryExecuted(
            'SELECT * FROM users',
            [],
            3.14
        );

        $result = SqlFormatUtil::format('Query-007', $query);

        $this->assertStringContainsString('Query-007', $result);
        $this->assertStringContainsString('3.14 ms', $result);
        $this->assertStringContainsString('SELECT *', $result);
        $this->assertStringContainsString('FROM users', $result);
    }

    public function test_format_includes_separator_lines(): void
    {
        $query = $this->makeQueryExecuted('SELECT * FROM users', [], 10.0);

        $result = SqlFormatUtil::format('Query-008', $query);

        $separatorLine = str_repeat('=', 80);
        $this->assertStringContainsString($separatorLine, $result);
    }

    public function test_format_includes_emoji_indicators(): void
    {
        $query = $this->makeQueryExecuted('SELECT * FROM users', [], 10.0);

        $result = SqlFormatUtil::format('Query-009', $query);

        $this->assertStringContainsString('⏱️', $result);
        $this->assertStringContainsString('🔍', $result);
    }

    public function test_format_with_insert_query(): void
    {
        $query = $this->makeQueryExecuted(
            'INSERT INTO users (name, email) VALUES (?, ?)',
            ['Bob', 'bob@example.com'],
            25.5
        );

        $result = SqlFormatUtil::format('Query-010', $query);

        $this->assertStringContainsString('INSERT', $result);
        $this->assertStringContainsString("'Bob'", $result);
        $this->assertStringContainsString("'bob@example.com'", $result);
    }

    public function test_format_with_update_query(): void
    {
        $query = $this->makeQueryExecuted(
            'UPDATE users SET name = ? WHERE id = ?',
            ['Charlie', 99],
            15.0
        );

        $result = SqlFormatUtil::format('Query-011', $query);

        $this->assertStringContainsString('UPDATE', $result);
        $this->assertStringContainsString("'Charlie'", $result);
        $this->assertStringContainsString('99', $result);
    }

    public function test_format_with_delete_query(): void
    {
        $query = $this->makeQueryExecuted(
            'DELETE FROM users WHERE id = ?',
            [456],
            8.0
        );

        $result = SqlFormatUtil::format('Query-012', $query);

        $this->assertStringContainsString('DELETE', $result);
        $this->assertStringContainsString('456', $result);
    }

    public function test_format_applies_sql_formatting(): void
    {
        $query = $this->makeQueryExecuted(
            'SELECT id, name, email FROM users WHERE age > ? AND city = ? ORDER BY created_at LIMIT ?',
            [18, 'New York', 10],
            30.0
        );

        $result = SqlFormatUtil::format('Query-013', $query);

        // Check that SQL is formatted with line breaks
        $this->assertStringContainsString('FROM', $result);
        $this->assertStringContainsString('WHERE', $result);
        $this->assertStringContainsString('ORDER BY', $result);
        $this->assertStringContainsString('LIMIT', $result);
    }

    public function test_format_handles_zero_execution_time(): void
    {
        $query = $this->makeQueryExecuted('SELECT 1', [], 0.0);

        $result = SqlFormatUtil::format('Query-014', $query);

        $this->assertStringContainsString('0.00 ms', $result);
    }

    public function test_format_handles_large_execution_time(): void
    {
        $query = $this->makeQueryExecuted('SELECT * FROM large_table', [], 1234.5678);

        $result = SqlFormatUtil::format('Query-015', $query);

        $this->assertStringContainsString('1,234.57 ms', $result);
    }

    public function test_format_with_special_characters_in_string(): void
    {
        $query = $this->makeQueryExecuted(
            'SELECT * FROM users WHERE name = ?',
            ["O'Brien"],
            10.0
        );

        $result = SqlFormatUtil::format('Query-016', $query);

        $this->assertStringContainsString("name = 'O'Brien'", $result);
    }
}

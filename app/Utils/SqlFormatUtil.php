<?php

namespace App\Utils;

use Carbon\Carbon;
use Illuminate\Database\Events\QueryExecuted;

class SqlFormatUtil
{
    public static function format(string $id, QueryExecuted $query): string
    {
        $sql = $query->sql;
        $bindings = $query->bindings;
        $time = $query->time;

        foreach ($bindings as $binding) {
            if (is_string($binding)) {
                $binding = "'{$binding}'";
            } elseif (is_null($binding)) {
                $binding = 'NULL';
            } elseif (is_bool($binding)) {
                $binding = $binding ? 'TRUE' : 'FALSE';
            }
            $sql = preg_replace('/\?/', $binding, $sql, 1);
        }

        $formattedSql = self::formatSql($sql);

        return sprintf(
            "\n" . str_repeat('=', 80) . "\n" .
            "{$id}\n" .
            "⏱️ : %s ms\n" .
            "🔍 :\n%s\n" .
            str_repeat('=', 80),
            number_format($time, 2),
            $formattedSql
        );
    }

    private static function formatSql(string $sql): string
    {
        $keywords = [
            'SELECT', 'FROM', 'WHERE', 'JOIN', 'LEFT JOIN', 'RIGHT JOIN', 'INNER JOIN',
            'ORDER BY', 'GROUP BY', 'HAVING', 'LIMIT', 'OFFSET', 'INSERT', 'INTO',
            'VALUES', 'UPDATE', 'SET', 'DELETE', 'CREATE', 'TABLE', 'ALTER', 'DROP',
            'INDEX', 'PRIMARY KEY', 'FOREIGN KEY', 'REFERENCES', 'ON', 'AND', 'OR',
            'NOT', 'NULL', 'DEFAULT', 'AUTO_INCREMENT', 'UNIQUE', 'CONSTRAINT'
        ];

        $formatted = $sql;

        $mainClauses = ['FROM', 'WHERE', 'JOIN', 'LEFT JOIN', 'RIGHT JOIN', 'INNER JOIN',
            'ORDER BY', 'GROUP BY', 'HAVING', 'LIMIT'];

        foreach ($mainClauses as $clause) {
            $formatted = preg_replace('/\s+' . $clause . '\s+/i', "\n    " . $clause . ' ', $formatted);
        }

        $formatted = preg_replace('/,\s*(?=\w)/', ",\n        ", $formatted);

        $formatted = preg_replace('/\s+(AND|OR)\s+/i', "\n        $1 ", $formatted);

        $formatted = "    " . trim($formatted);

        return $formatted;
    }
}

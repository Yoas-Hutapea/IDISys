<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class DocumentCounterService
{
    public function generateNumber(string $docCode, array $context = [], int $quantity = 1): string
    {
        $numbers = $this->generateBulkNumbers($docCode, $context, $quantity);
        return $numbers[0] ?? '';
    }

    public function generateBulkNumbers(string $docCode, array $context = [], int $quantity = 1): array
    {
        $stringContext = [];
        foreach ($context as $key => $value) {
            $stringContext[strtoupper((string) $key)] = (string) $value;
        }

        $now = now();
        $stringContext['YYYY'] = $now->format('Y');
        $stringContext['YY'] = $now->format('y');
        $stringContext['MM'] = $now->format('m');
        $stringContext['DD'] = $now->format('d');
        $stringContext['MONTH'] = $this->toRomanMonth((int) $now->format('m'));

        $template = DB::table('mstDocumentCounterTemplates')
            ->where('DocCode', $docCode)
            ->where('IsActive', true)
            ->first();

        if (!$template) {
            throw new RuntimeException("Document Template '{$docCode}' not found or inactive.");
        }

        $counterKey = $this->buildCounterKey($docCode, $template->ResetRule ?? '', $stringContext);
        $startSeq = $this->reserveSequence($counterKey, $quantity);

        $results = [];
        for ($i = 0; $i < $quantity; $i++) {
            $results[] = $this->applyFormat($template->FormatString ?? '', $stringContext, $startSeq + $i);
        }

        return $results;
    }

    private function reserveSequence(string $counterKey, int $size): int
    {
        $rows = DB::select(
            'EXEC sp_DocumentCounterNextSequence @CounterKey = ?, @BatchSize = ?',
            [$counterKey, $size]
        );

        if (!$rows) {
            return 1;
        }

        $row = (array) $rows[0];
        foreach ($row as $key => $value) {
            if (strcasecmp($key, 'StartSequence') === 0) {
                return (int) $value;
            }
        }

        return (int) (array_values($row)[0] ?? 1);
    }

    private function buildCounterKey(string $docCode, string $resetRule, array $context): string
    {
        $keyParts = [$docCode];

        if ($resetRule !== '') {
            $rules = array_filter(array_map('trim', explode(',', $resetRule)));
            foreach ($rules as $rule) {
                $lookup = strtoupper($rule);
                if (!array_key_exists($lookup, $context)) {
                    throw new InvalidArgumentException("ResetRule '{$rule}' needed for {$docCode} but not provided in Context.");
                }
                $keyParts[] = $context[$lookup];
            }
        }

        return implode('-', $keyParts);
    }

    private function applyFormat(string $format, array $context, int $seqVal): string
    {
        $result = preg_replace_callback('/\{(\w+)\}/', function ($match) use ($context) {
            $key = strtoupper($match[1]);
            if (str_starts_with($key, 'SEQ')) {
                return $match[0];
            }
            return $context[$key] ?? $match[0];
        }, $format);

        return preg_replace_callback('/\{SEQ:(\d+)\}/', function ($match) use ($seqVal) {
            $len = (int) $match[1];
            return str_pad((string) $seqVal, $len, '0', STR_PAD_LEFT);
        }, $result ?? $format);
    }

    private function toRomanMonth(int $month): string
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        return $map[$month] ?? '';
    }
}

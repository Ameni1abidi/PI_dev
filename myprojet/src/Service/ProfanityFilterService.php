<?php

namespace App\Service;

class ProfanityFilterService
{
    /**
     * @param array<int, string> $blockedWords
     */
    public function __construct(
        private readonly array $blockedWords = []
    ) {
    }

    public function containsInappropriateWord(string $text): bool
    {
        return $this->findInappropriateWords($text) !== [];
    }

    /**
     * @return array<int, string>
     */
    public function findInappropriateWords(string $text): array
    {
        $words = array_values(array_filter(array_map('trim', $this->blockedWords)));
        if ($words === []) {
            return [];
        }

        usort(
            $words,
            static fn (string $a, string $b): int => strlen($b) <=> strlen($a)
        );

        $escapedWords = array_map(
            static fn (string $word): string => preg_quote($word, '/'),
            $words
        );

        $pattern = '/\b(' . implode('|', $escapedWords) . ')\b/iu';

        if (!preg_match_all($pattern, $text, $matches)) {
            return [];
        }

        $found = [];
        foreach ($matches[0] as $match) {
            $found[] = mb_strtolower($match);
        }

        return array_values(array_unique($found));
    }
}

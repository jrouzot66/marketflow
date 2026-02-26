<?php
// backend/src/Domain/Offer/TagIterator.php

namespace App\Domain\Offer;

/**
 * Service de parsing et normalisation de tags.
 *
 * Responsabilités :
 * - découper une chaîne en tags potentiels
 * - appliquer des règles de normalisation (trim, lower, slug)
 * - filtrer (stop-words, longueur, caractères invalides)
 * - dédupliquer
 */
final class TagIterator
{
    private const STOP_WORDS = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for'];

    public function __construct(
        private readonly bool $filterStopWords = true,
        private readonly int $minLength = 2,
        private readonly int $maxLength = 50
    ) {
    }

    /**
     * Parse une chaîne de tags (séparés par virgule, point-virgule ou espace).
     * Retourne une collection Tags.
     *
     * @throws \InvalidArgumentException si validation échoue
     */
    public function parse(string $rawInput): Tags
    {
        $rawTags = $this->splitInput($rawInput);
        $normalized = $this->normalize($rawTags);
        $filtered = $this->filter($normalized);

        return Tags::fromStrings($filtered);
    }

    /**
     * Découpe l'input selon plusieurs délimiteurs.
     *
     * @return list<string>
     */
    private function splitInput(string $input): array
    {
        // Remplace les délimiteurs par une virgule commune
        $input = preg_replace('/[,;]+/', ',', $input);

        // Découpe par virgule
        $tags = explode(',', $input);

        // Trim chaque tag
        $tags = array_map('trim', $tags);

        // Enlève les vides
        return array_filter($tags, fn(string $t) => $t !== '');
    }

    /**
     * Normalise chaque tag (trim, lower, slug).
     *
     * @param list<string> $tags
     * @return list<string>
     */
    private function normalize(array $tags): array
    {
        return array_map(function (string $tag): string {
            $tag = trim($tag);
            $tag = mb_strtolower($tag);

            // Slug simple : remplace espaces par tirets
            $tag = preg_replace('/\s+/', '-', $tag);

            // Enlève les caractères non-alphanumériques sauf dash et underscore
            $tag = preg_replace('/[^a-z0-9_-]/', '', $tag);

            // Enlève les tirets/underscores multiples
            $tag = preg_replace('/[-_]+/', '-', $tag);

            // Trim les tirets des bords
            $tag = trim($tag, '-_');

            return $tag;
        }, $tags);
    }

    /**
     * Filtre selon des règles :
     * - longueur (min/max)
     * - stop-words (optionnel)
     * - doublons
     *
     * @param list<string> $tags
     * @return list<string>
     */
    private function filter(array $tags): array
    {
        $seen = [];
        $result = [];

        foreach ($tags as $tag) {
            // Skip vides
            if ($tag === '') {
                continue;
            }

            // Check longueur
            if (mb_strlen($tag) < $this->minLength || mb_strlen($tag) > $this->maxLength) {
                continue;
            }

            // Check stop-words
            if ($this->filterStopWords && in_array($tag, self::STOP_WORDS, true)) {
                continue;
            }

            // Déduplique
            if (isset($seen[$tag])) {
                continue;
            }

            $seen[$tag] = true;
            $result[] = $tag;
        }

        return $result;
    }
}

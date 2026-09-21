<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Models\StoreProductModel;
use Fc\Admin\Presenters\StoreProductPresenter;

/**
 * Deep Scan: ranks the catalogue against a gap's TITLE once SKU matching has run out of ideas.
 *
 * MissingSkuScanService decides; this one only suggests. Nothing is written to
 * writable/missing-products.csv and nothing reaches Fill — a title similarity is a lead for a
 * person to confirm, never an answer, so the results live in the response and die with it.
 *
 * Ranking is BM25F over the catalogue's Name and Description, so a renamed or reworded product is
 * findable when its SKU tells us nothing. Colour stays a HARD filter and is never scored: a
 * catalogue name is written per range, so XP-2400-FP-B (Black) and -BS (Basalt) read identically,
 * and a scorer allowed to see both picks a colour at random and sounds certain about it. See
 * wrongColour() for which evidence decides.
 *
 * Colour and Description are columns WooCommerceProductExportService added to
 * wc-products-{GO,JG}.csv. A catalogue exported before them still scans — on Name alone, with
 * the colour read out of the SKU, which is what this did before they existed.
 */
final class MissingSkuDeepScan
{
    /** Okapi BM25 term saturation and length normalisation. */
    private const K1 = 1.2;
    private const B = 0.75;

    private const TOP_N = 5;

    /** A lone common word is not a lead; both gates must pass for a candidate to be shown. */
    private const MIN_SCORE = 3.0;
    private const MIN_COVERAGE = 0.4;

    /** What each part of the query is worth, relative to the product's own title. */
    private const W_TITLE = 1.0;
    private const W_SIBLING = 0.6;
    private const W_STYLE = 0.3;
    private const W_SLUG = 0.35;

    /**
     * What each part of a catalogue entry is worth (BM25F field weights). A description is
     * marketing copy written for a range, not for one SKU — it widens what can be found but
     * must never outvote the name, which is the one field written about this product alone.
     */
    private const F_NAME = 1.0;
    private const F_DESCRIPTION = 0.35;

    /** The document fields, in the order a coverage tie is resolved. */
    private const FIELDS = ['name' => self::F_NAME, 'description' => self::F_DESCRIPTION];

    /** Words that carry no product meaning; BM25's IDF handles the rest. */
    private const STOPWORDS = ['the', 'and', 'for', 'with', 'of', 'to', 'in', 'on', 'a', 'an'];

    /**
     * Words that reach the colour map only because a colour is named after them. A sheen spans
     * four codes ("satin"), and a material is not a colour at all — without this, "Security Steel
     * Gate" reads as Stainless and answers no black gap.
     */
    private const NON_COLOUR_WORDS = [
        'matt', 'matte', 'gloss', 'satin', 'textured',
        'steel', 'stainless', 'polished', 'red',
    ];

    /**
     * Every unresolved gap in products.csv, ranked against the catalogue by title.
     *
     * Gaps the catalogue scan already placed, and gaps a person marked `manual`, are left alone —
     * this runs on what is still open, which is the only place a fuzzy lead is worth reading.
     *
     * @return array{ok:bool,gaps:int,matched:int,suggestions:array<string, list<array{sku:string,name:string,image:string,percent:int}>>,error?:string}
     */
    public static function scan(): array
    {
        $payload = StoreProductModel::all();
        if (empty($payload['ok'])) {
            return self::failed('Could not read products.csv.');
        }

        $catalogue = WcProductSkuIndex::catalogueUnion();
        if ($catalogue === []) {
            return self::failed('The store catalogue is empty.');
        }

        $names = array_column($catalogue, 'name', 'sku');
        $skuSet = WcProductSkuIndex::skuLookup();
        $columns = is_array($payload['columns'] ?? null) ? $payload['columns'] : [];
        $styleColors = StoreProductPresenter::styleColorsMap();
        $styleLabels = StoreProductPresenter::styleLabels();
        $initials = StoreProductPresenter::colorInitialsMap();
        // Built before the index: every entry's Colour cell is resolved to codes once, here.
        $colorWords = self::colorWords($initials);
        $index = self::buildIndex($catalogue, $colorWords);
        $settled = self::settledKeys($skuSet);

        $suggestions = [];
        $gapCount = 0;

        foreach (is_array($payload['rows'] ?? null) ? $payload['rows'] : [] as $product) {
            $slug = trim((string) ($product['SLUG'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $values = [];
            $siblings = [];
            $gaps = [];
            foreach (StoreProductPresenter::allowedColorColumns($product, $columns, $styleColors) as $column) {
                $value = trim((string) ($product[$column] ?? ''));
                $values[] = $value;
                if ($value !== '' && strtoupper($value) !== 'OFF' && isset($skuSet[$value])) {
                    $siblings[] = $value;
                    continue;
                }
                if (strtoupper($value) === 'OFF') {
                    continue;
                }
                $gaps[$column] = $value;
            }
            if ($gaps === []) {
                continue;
            }

            $style = (string) ($product['STYLE'] ?? '');
            $query = self::queryFor(
                (string) ($product['PRODUCT'] ?? ''),
                $slug,
                StoreProductPresenter::styleLabel($style, $styleLabels),
                $siblings,
                $names
            );
            if ($query === []) {
                continue;
            }
            $agnostic = MissingSkuMatcher::isColorAgnostic($values);

            foreach (array_keys($gaps) as $column) {
                $key = $slug . '|' . $column;
                if (isset($settled[$key])) {
                    continue;
                }
                $gapCount++;

                $hits = self::rank($query, $index, $agnostic ? '' : ($initials[$column] ?? ''));
                if ($hits !== []) {
                    $suggestions[$key] = $hits;
                }
            }
        }

        return [
            'ok'          => true,
            'gaps'        => $gapCount,
            'matched'     => count($suggestions),
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Gap keys a run must not talk over: a catalogue match that stands up, or a person's own
     * decision. A missing-products.csv is optional, so an absent file simply settles nothing.
     *
     * @param array<string, true> $skuSet
     * @return array<string, true>
     */
    private static function settledKeys(array $skuSet): array
    {
        $settled = [];
        foreach (MissingSkuScanService::proposals() as $key => $proposal) {
            if ($proposal['source'] === 'manual' || isset($skuSet[$proposal['sku']])) {
                $settled[$key] = true;
            }
        }

        return $settled;
    }

    /**
     * The weighted bag of words one product is described by. Siblings pull their names from the
     * catalogue because the store's own wording for this product beats the terse products.csv
     * title at matching the store's other listings.
     *
     * @param list<string> $siblings
     * @param array<string, string> $names
     * @return array<string, float> token => summed query weight
     */
    private static function queryFor(
        string $title,
        string $slug,
        string $styleLabel,
        array $siblings,
        array $names
    ): array {
        $query = [];
        self::addTerms($query, $title, self::W_TITLE);
        self::addTerms($query, $slug, self::W_SLUG);
        self::addTerms($query, $styleLabel, self::W_STYLE);
        foreach ($siblings as $sibling) {
            self::addTerms($query, (string) ($names[$sibling] ?? ''), self::W_SIBLING);
        }

        return $query;
    }

    /**
     * @param array<string, float> $query
     */
    private static function addTerms(array &$query, string $text, float $weight): void
    {
        foreach (self::tokenize($text) as $token) {
            $query[$token] = ($query[$token] ?? 0.0) + $weight;
        }
    }

    /**
     * BM25F over the catalogue's name and description, filtered to this colour first.
     *
     * The percentage shown is not the BM25 score — it is how much of the query's distinctive
     * wording the candidate covers, which is the part a person can check by eye. A word found
     * only in the description counts for its field weight, so "matched by marketing copy" can
     * never read as confidently as "matched by the product name".
     *
     * @param array<string, float> $query
     * @param array{docs:list<array{sku:string,name:string,image:string,code:string,colour:string,colourCodes:array<string,true>,nameCodes:array<string,true>,len:array<string,int>,tf:array<string,array<string,int>>}>,idf:array<string,float>,avgdl:array<string,float>} $index
     * @return list<array{sku:string,name:string,image:string,percent:int}>
     */
    private static function rank(array $query, array $index, string $initial): array
    {
        $total = 0.0;
        foreach ($query as $token => $weight) {
            $total += $weight * ($index['idf'][$token] ?? 0.0);
        }
        if ($total <= 0.0) {
            return [];
        }

        $scored = [];
        foreach ($index['docs'] as $doc) {
            if ($initial !== '' && self::wrongColour($doc, $initial)) {
                continue;
            }

            $score = 0.0;
            $covered = 0.0;
            foreach ($query as $token => $weight) {
                $combined = 0.0;
                $best = 0.0;
                foreach (self::FIELDS as $field => $fieldWeight) {
                    $freq = $doc['tf'][$field][$token] ?? 0;
                    if ($freq === 0) {
                        continue;
                    }
                    $norm = 1.0 - self::B + self::B * ($doc['len'][$field] / $index['avgdl'][$field]);
                    $combined += $fieldWeight * ($freq / $norm);
                    $best = max($best, $fieldWeight);
                }
                if ($combined <= 0.0) {
                    continue;
                }
                $idf = $index['idf'][$token] ?? 0.0;
                // Saturation is applied once, after the fields are combined — that is what makes
                // this BM25F rather than two BM25 scores added together.
                $score += $weight * $idf * (($combined * (self::K1 + 1)) / ($combined + self::K1));
                $covered += $weight * $idf * $best;
            }

            $coverage = $covered / $total;
            if ($score < self::MIN_SCORE || $coverage < self::MIN_COVERAGE) {
                continue;
            }
            $scored[] = [
                'sku'     => $doc['sku'],
                'name'    => $doc['name'],
                'image'   => $doc['image'],
                'percent' => (int) round($coverage * 100),
                'score'   => $score,
            ];
        }

        // Coverage leads the sort because it is the number the page shows: a list ordered by a
        // score nobody can see reads as shuffled. BM25 breaks the many ties coverage leaves.
        usort(
            $scored,
            static fn (array $a, array $b): int => [$b['percent'], $b['score']] <=> [$a['percent'], $a['score']]
        );

        $out = [];
        foreach (array_slice($scored, 0, self::TOP_N) as $hit) {
            unset($hit['score']);
            $out[] = $hit;
        }

        return $out;
    }

    /**
     * Colour word -> every SKU code that word is used under, read from Settings -> Fence colours
     * so a new colour needs no edit here. "monument" spans M and MN, which is why a word keeps a
     * set rather than one code.
     *
     * @param array<string, string> $initials CSV colour column => SKU code
     * @return array<string, array<string, true>>
     */
    private static function colorWords(array $initials): array
    {
        $skip = array_fill_keys(self::NON_COLOUR_WORDS, true);
        $words = [];
        foreach ($initials as $column => $initial) {
            foreach (self::tokenize($column) as $word) {
                if (isset($skip[$word]) || strlen($word) < 3) {
                    continue;
                }
                $words[$word][$initial] = true;
            }
        }

        return $words;
    }

    /**
     * Whether a catalogue entry is the wrong colour for this gap. The three signals are consulted
     * in the order they proved trustworthy against the live catalogue, not the order they look
     * authoritative:
     *
     *  1. the SKU's trailing code — chosen per variant, by someone naming that exact product;
     *  2. the product name — written per product, and it agrees with the code where both exist;
     *  3. the exported Colour cell — a pa_colour taxonomy term, which the store sets on the PARENT,
     *     so every variant of a black range reads "Black" however it is actually finished. Of the
     *     380 rows where the cell and the name both name a colour, 76 disagree, and wherever the
     *     SKU code is there to break the tie it sides with the name (FS-FT-SS-1200-GC-2PK-MN-GO,
     *     "Flat Top Gate Converters 2 pcs Monument", cell "Black"). So the cell only decides what
     *     nothing better has — which is still most of what it is worth, because those rows used to
     *     match every colour equally.
     *
     * An entry with none of the three is colour-agnostic hardware and answers any gap, the same
     * allowance MissingSkuMatcher::byExactName() makes.
     *
     * @param array{code:string,colour:string,colourCodes:array<string,true>,nameCodes:array<string,true>} $doc
     */
    private static function wrongColour(array $doc, string $initial): bool
    {
        if ($doc['code'] !== '') {
            return $doc['code'] !== $initial;
        }
        if ($doc['nameCodes'] !== []) {
            return !isset($doc['nameCodes'][$initial]);
        }

        return $doc['colour'] !== '' && !isset($doc['colourCodes'][$initial]);
    }

    /**
     * The colour a product name claims. A variation is named "<range> – <ATTR> / <ATTR>", by this
     * exporter and by WooCommerce alike, and the range name is often a colour itself: 49933 is
     * "Flat Top Post Black 50mm x 50mm – MONUMENT / 2400mm L" and is a Monument post. So where the
     * separator is present only what follows it describes this variant; a suffix naming nothing
     * known falls back to the whole name rather than leaving the entry unconstrained.
     *
     * The description is never read here — a white post whose copy mentions the black one in the
     * same range would reject itself.
     *
     * @param array<string, array<string, true>> $colorWords
     * @return array<string, true>
     */
    private static function nameColourCodes(string $name, array $colorWords): array
    {
        $pos = mb_strrpos($name, "\u{2013}", 0, 'UTF-8');
        if ($pos !== false) {
            $codes = self::codesForText(mb_substr($name, $pos + 1, null, 'UTF-8'), $colorWords);
            if ($codes !== []) {
                return $codes;
            }
        }

        return self::codesForText($name, $colorWords);
    }

    /**
     * Every colour code the words of a piece of text stand for. Empty means it says nothing about
     * colour — the store's numeric SKUs are the reason this is asked at all, since they carry no
     * code and would otherwise match every colour.
     *
     * @param array<string, array<string, true>> $colorWords
     * @return array<string, true>
     */
    private static function codesForText(string $text, array $colorWords): array
    {
        $codes = [];
        foreach (self::tokenize($text) as $token) {
            foreach (array_keys($colorWords[$token] ?? []) as $code) {
                $codes[$code] = true;
            }
        }

        return $codes;
    }

    /**
     * Per-field term frequencies, field lengths and IDF for the whole catalogue, built once per
     * request. A term's document frequency counts a document once however many fields it is in,
     * so a word repeated in every description is rare-ish in exactly the way BM25 expects.
     *
     * @param list<array{sku:string,name:string,image:string,colour:string,description:string}> $catalogue
     * @param array<string, array<string, true>> $colorWords
     * @return array{docs:list<array{sku:string,name:string,image:string,code:string,colour:string,colourCodes:array<string,true>,nameCodes:array<string,true>,len:array<string,int>,tf:array<string,array<string,int>>}>,idf:array<string,float>,avgdl:array<string,float>}
     */
    private static function buildIndex(array $catalogue, array $colorWords): array
    {
        $docs = [];
        $df = [];
        $lengths = [];
        foreach (array_keys(self::FIELDS) as $field) {
            $lengths[$field] = 0;
        }

        foreach ($catalogue as $entry) {
            $tf = [];
            $len = [];
            $inDoc = [];
            foreach (array_keys(self::FIELDS) as $field) {
                $tokens = self::tokenize((string) ($entry[$field] ?? ''));
                $counts = [];
                foreach ($tokens as $token) {
                    $counts[$token] = ($counts[$token] ?? 0) + 1;
                    $inDoc[$token] = true;
                }
                $tf[$field] = $counts;
                $len[$field] = count($tokens);
                $lengths[$field] += count($tokens);
            }
            // A row with no name is not findable by title, whatever its description says.
            if ($tf['name'] === []) {
                continue;
            }
            foreach (array_keys($inDoc) as $token) {
                $df[$token] = ($df[$token] ?? 0) + 1;
            }

            $colour = (string) ($entry['colour'] ?? '');
            $docs[] = [
                'sku'         => (string) $entry['sku'],
                'name'        => (string) ($entry['name'] ?? ''),
                'image'       => (string) ($entry['image'] ?? ''),
                // Some exported SKUs carry a stray space ("…-W -GO"), which hides the colour code
                // and would let a White product answer a Black gap.
                'code'        => MissingSkuMatcher::colorCodeOf(
                    strtoupper(preg_replace('/\s+/', '', (string) $entry['sku']) ?? (string) $entry['sku'])
                ),
                'colour'      => $colour,
                'colourCodes' => self::colourCodesOf($colour, $colorWords),
                'nameCodes'   => self::nameColourCodes((string) ($entry['name'] ?? ''), $colorWords),
                'len'         => $len,
                'tf'          => $tf,
            ];
        }

        $count = count($docs);
        $idf = [];
        foreach ($df as $token => $seen) {
            $idf[$token] = log(1 + (($count - $seen + 0.5) / ($seen + 0.5)));
        }
        $avgdl = [];
        foreach (self::FIELDS as $field => $weight) {
            $avgdl[$field] = $count === 0 ? 1.0 : max(1.0, $lengths[$field] / $count);
        }

        return ['docs' => $docs, 'idf' => $idf, 'avgdl' => $avgdl];
    }

    /**
     * The SKU codes a Colour cell stands for. "Monument" is two codes (M and MN) and a finish
     * word such as "Matte" is none, which is why colorWords keeps a set per word.
     *
     * @param array<string, array<string, true>> $colorWords
     * @return array<string, true>
     */
    private static function colourCodesOf(string $colour, array $colorWords): array
    {
        return self::codesForText($colour, $colorWords);
    }

    /**
     * Words a product name is matched on. Letter/digit runs are split apart so "1800mm" answers a
     * title that only says 1800, which is most of them.
     *
     * @return list<string>
     */
    private static function tokenize(string $text): array
    {
        $text = strtolower(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $text = preg_replace('/[^a-z0-9]+/', ' ', $text) ?? $text;
        $text = preg_replace('/(\d)([a-z])/', '$1 $2', $text) ?? $text;
        $text = preg_replace('/([a-z])(\d)/', '$1 $2', $text) ?? $text;

        $stop = array_fill_keys(self::STOPWORDS, true);
        $out = [];
        foreach (preg_split('/\s+/', trim($text)) ?: [] as $token) {
            if ($token === '' || isset($stop[$token])) {
                continue;
            }
            if (strlen($token) < 2 && !ctype_digit($token)) {
                continue;
            }
            $out[] = $token;
        }

        return $out;
    }

    /**
     * @return array{ok:bool,gaps:int,matched:int,suggestions:array<string, list<array{sku:string,name:string,image:string,percent:int}>>,error:string}
     */
    private static function failed(string $error): array
    {
        return ['ok' => false, 'gaps' => 0, 'matched' => 0, 'suggestions' => [], 'error' => $error];
    }
}

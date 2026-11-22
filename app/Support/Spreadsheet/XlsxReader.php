<?php

namespace App\Support\Spreadsheet;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Minimal .xlsx reader (no dependency): returns every sheet as a list of rows of trimmed strings.
 * Guards against oversized archives and never resolves external entities.
 */
class XlsxReader
{
    private const MAX_ENTRY_BYTES = 30 * 1024 * 1024;

    private const MAX_ROWS = 5000;

    /**
     * @return array<string, list<list<string>>> sheet name => rows (the first row holds the headers)
     *
     * @throws RuntimeException when the file is not a readable workbook
     */
    public static function read(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('Ce fichier n’est pas un classeur Excel (.xlsx) valide.');
        }

        try {
            $workbook = self::xml($zip, 'xl/workbook.xml');
            $rels = self::xml($zip, 'xl/_rels/workbook.xml.rels');
            $shared = self::sharedStrings($zip);

            $targets = [];
            foreach ($rels->Relationship as $rel) {
                $targets[(string) $rel['Id']] = ltrim((string) $rel['Target'], '/');
            }

            $workbook->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $sheets = [];

            foreach ($workbook->xpath('//m:sheets/m:sheet') ?: [] as $sheet) {
                $relId = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                $target = $targets[$relId] ?? null;

                if ($target === null) {
                    continue;
                }

                $file = str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
                $sheets[(string) $sheet['name']] = self::rows(self::xml($zip, $file), $shared);
            }

            return $sheets;
        } finally {
            $zip->close();
        }
    }

    private static function xml(ZipArchive $zip, string $name): SimpleXMLElement
    {
        $stat = $zip->statName($name);

        if ($stat === false) {
            throw new RuntimeException('Ce fichier n’est pas un classeur Excel (.xlsx) valide.');
        }

        if ($stat['size'] > self::MAX_ENTRY_BYTES) {
            throw new RuntimeException('Le classeur est trop volumineux.');
        }

        $xml = simplexml_load_string((string) $zip->getFromName($name), SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);

        if ($xml === false) {
            throw new RuntimeException('Le classeur est illisible (XML invalide).');
        }

        return $xml;
    }

    /**
     * @return list<string>
     */
    private static function sharedStrings(ZipArchive $zip): array
    {
        if ($zip->statName('xl/sharedStrings.xml') === false) {
            return [];
        }

        $strings = [];
        foreach (self::xml($zip, 'xl/sharedStrings.xml')->si as $si) {
            $text = '';
            if (isset($si->t)) {
                $text = (string) $si->t;
            } else {
                foreach ($si->r as $run) {
                    $text .= (string) $run->t;
                }
            }
            $strings[] = $text;
        }

        return $strings;
    }

    /**
     * @param  list<string>  $shared
     * @return list<list<string>>
     */
    private static function rows(SimpleXMLElement $sheet, array $shared): array
    {
        $rows = [];

        foreach ($sheet->sheetData->row ?? [] as $row) {
            if (count($rows) > self::MAX_ROWS) {
                throw new RuntimeException('Le classeur dépasse '.self::MAX_ROWS.' lignes : découpez-le en plusieurs fichiers.');
            }

            $values = [];
            foreach ($row->c as $cell) {
                $column = self::columnIndex((string) $cell['r']);
                $type = (string) $cell['t'];

                $value = match ($type) {
                    's' => $shared[(int) $cell->v] ?? '',
                    'inlineStr' => self::inlineText($cell),
                    'b' => (string) $cell->v === '1' ? 'oui' : 'non',
                    default => (string) $cell->v,
                };

                $values[$column] = trim($value);
            }

            if ($values === []) {
                $rows[] = [];

                continue;
            }

            $line = array_fill(0, max(array_keys($values)) + 1, '');
            foreach ($values as $index => $value) {
                $line[$index] = $value;
            }
            $rows[] = $line;
        }

        return $rows;
    }

    private static function inlineText(SimpleXMLElement $cell): string
    {
        if (isset($cell->is->t)) {
            return (string) $cell->is->t;
        }

        $text = '';
        foreach ($cell->is->r ?? [] as $run) {
            $text .= (string) $run->t;
        }

        return $text;
    }

    /**
     * « C5 » => 2 (zero based).
     */
    private static function columnIndex(string $reference): int
    {
        preg_match('/^[A-Z]+/i', $reference, $match);
        $letters = strtoupper($match[0] ?? 'A');
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = $index * 26 + (ord($letter) - 64);
        }

        return $index - 1;
    }
}

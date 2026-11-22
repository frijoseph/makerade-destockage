<?php

namespace App\Support\Spreadsheet;

use RuntimeException;
use ZipArchive;

/**
 * Minimal .xlsx writer (no dependency): several sheets, a styled and frozen header row, typed cells.
 * Text is always written as a string cell, so Excel never evaluates it as a formula.
 */
class XlsxWriter
{
    /**
     * @param  array<string, array{headers: list<string>, rows: list<list<scalar|null>>, widths?: list<int>}>  $sheets
     * @return string path of a temporary .xlsx file (the caller deletes it)
     */
    public static function build(array $sheets, string $headerColor = '0F6A26'): string
    {
        $path = tempnam(sys_get_temp_dir(), 'mkx');
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Impossible de créer le fichier Excel.');
        }

        $names = array_keys($sheets);
        $zip->addFromString('[Content_Types].xml', self::contentTypes(count($names)));
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', self::workbook($names));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels(count($names)));
        $zip->addFromString('xl/styles.xml', self::styles($headerColor));

        foreach (array_values($sheets) as $index => $sheet) {
            $zip->addFromString('xl/worksheets/sheet'.($index + 1).'.xml', self::sheet($sheet));
        }

        $zip->close();

        return $path;
    }

    /**
     * 1 => A, 27 => AA.
     */
    public static function column(int $number): string
    {
        $letters = '';
        while ($number > 0) {
            $remainder = ($number - 1) % 26;
            $letters = chr(65 + $remainder).$letters;
            $number = intdiv($number - 1, 26);
        }

        return $letters;
    }

    private static function escape(string $text): string
    {
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $text) ?? '';

        return htmlspecialchars($clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param  array{headers: list<string>, rows: list<list<scalar|null>>, widths?: list<int>}  $sheet
     */
    private static function sheet(array $sheet): string
    {
        $columns = count($sheet['headers']);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';
        $xml .= '<sheetFormatPr defaultRowHeight="18"/>';

        if (! empty($sheet['widths'])) {
            $xml .= '<cols>';
            foreach ($sheet['widths'] as $i => $width) {
                $xml .= '<col min="'.($i + 1).'" max="'.($i + 1).'" width="'.$width.'" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData><row r="1" ht="24" customHeight="1">';
        foreach ($sheet['headers'] as $i => $header) {
            $xml .= self::cell($i + 1, 1, $header, 1);
        }
        $xml .= '</row>';

        foreach ($sheet['rows'] as $rowIndex => $row) {
            $r = $rowIndex + 2;
            $xml .= '<row r="'.$r.'">';
            foreach (array_values($row) as $i => $value) {
                $xml .= self::cell($i + 1, $r, $value, 0);
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData>';

        if ($columns > 0) {
            $xml .= '<autoFilter ref="A1:'.self::column($columns).max(1, count($sheet['rows']) + 1).'"/>';
        }

        return $xml.'</worksheet>';
    }

    private static function cell(int $column, int $row, mixed $value, int $style): string
    {
        $ref = self::column($column).$row;
        $s = $style ? ' s="'.$style.'"' : '';

        if ($value === null || $value === '') {
            return $style ? '<c r="'.$ref.'"'.$s.'/>' : '';
        }

        if (is_bool($value)) {
            return '<c r="'.$ref.'"'.$s.' t="b"><v>'.($value ? 1 : 0).'</v></c>';
        }

        if (is_int($value) || is_float($value)) {
            return '<c r="'.$ref.'"'.$s.'><v>'.$value.'</v></c>';
        }

        return '<c r="'.$ref.'"'.$s.' t="inlineStr"><is><t xml:space="preserve">'.self::escape((string) $value).'</t></is></c>';
    }

    private static function contentTypes(int $sheets): string
    {
        $overrides = '';
        for ($i = 1; $i <= $sheets; $i++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'.$overrides.'</Types>';
    }

    /**
     * @param  list<string>  $names
     */
    private static function workbook(array $names): string
    {
        $sheets = '';
        foreach ($names as $i => $name) {
            $sheets .= '<sheet name="'.self::escape(mb_substr(preg_replace('/[\\\\\/?*\[\]:]/', ' ', $name) ?? $name, 0, 31)).'" sheetId="'.($i + 1).'" r:id="rId'.($i + 1).'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>'.$sheets.'</sheets></workbook>';
    }

    private static function workbookRels(int $sheets): string
    {
        $rels = '';
        for ($i = 1; $i <= $sheets; $i++) {
            $rels .= '<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$rels.'<Relationship Id="rId'.($sheets + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private static function styles(string $headerColor): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF'.self::escape($headerColor).'"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment vertical="center" wrapText="1"/></xf></cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }
}

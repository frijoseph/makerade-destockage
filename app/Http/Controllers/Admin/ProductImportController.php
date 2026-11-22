<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Spreadsheet\ProductSpreadsheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductImportController extends Controller
{
    private const XLSX_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public function export(): BinaryFileResponse
    {
        return response()->download(ProductSpreadsheet::export(), 'produits-'.now()->format('Y-m-d').'.xlsx', ['Content-Type' => self::XLSX_TYPE])->deleteFileAfterSend();
    }

    public function template(): BinaryFileResponse
    {
        return response()->download(ProductSpreadsheet::template(), 'modele-import-produits.xlsx', ['Content-Type' => self::XLSX_TYPE])->deleteFileAfterSend();
    }

    public function create(): View
    {
        return view('admin.products.import', ['report' => null, 'token' => null]);
    }

    /**
     * Reads the uploaded workbook and shows what the import would do; nothing is written yet.
     */
    public function preview(Request $request): View|RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx', 'max:5120']], [
            'file.required' => 'Choisissez un fichier Excel (.xlsx).',
            'file.mimes' => 'Le fichier doit être au format Excel (.xlsx).',
            'file.max' => 'Le fichier ne doit pas dépasser 5 Mo.',
        ]);

        $token = Str::lower(Str::random(40));
        $path = $request->file('file')->storeAs('imports', "{$token}.xlsx", 'local');

        try {
            $report = ProductSpreadsheet::parse(Storage::disk('local')->path($path));
        } catch (RuntimeException $exception) {
            Storage::disk('local')->delete($path);

            return back()->withErrors(['file' => $exception->getMessage()]);
        }

        return view('admin.products.import', ['report' => $report, 'token' => $token]);
    }

    /**
     * Applies a previously previewed workbook.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['token' => ['required', 'regex:/^[a-z0-9]{40}$/']]);
        $path = "imports/{$data['token']}.xlsx";

        if (! Storage::disk('local')->exists($path)) {
            return redirect()->route('admin.products.import')->withErrors(['file' => 'Cet aperçu a expiré : envoyez le fichier à nouveau.']);
        }

        try {
            $report = ProductSpreadsheet::parse(Storage::disk('local')->path($path));

            if ($report['errors'] !== []) {
                return redirect()->route('admin.products.import')->withErrors(['file' => 'Le fichier contient des erreurs : corrigez-les puis renvoyez-le.']);
            }

            $summary = ProductSpreadsheet::apply($report, $request->user());
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.products.import')->withErrors(['file' => $exception->getMessage()]);
        } finally {
            Storage::disk('local')->delete($path);
        }

        $parts = array_filter([
            $summary['created'] ? "{$summary['created']} produit(s) créé(s)" : null,
            $summary['updated'] ? "{$summary['updated']} mis à jour" : null,
            $summary['variants'] ? "{$summary['variants']} variante(s) traitée(s)" : null,
            $summary['categories'] ? "{$summary['categories']} catégorie(s) créée(s)" : null,
            $summary['brands'] ? "{$summary['brands']} marque(s) créée(s)" : null,
        ]);

        return redirect()->route('admin.products.index')->with('status', 'Import terminé : '.($parts ? implode(', ', $parts) : 'aucun changement').'.');
    }
}

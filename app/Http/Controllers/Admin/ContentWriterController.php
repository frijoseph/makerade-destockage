<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Support\Content\DescriptionWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON endpoint behind the "Rédiger" buttons of the product and article forms.
 */
class ContentWriterController extends Controller
{
    public function generate(Request $request): JsonResponse
    {
        if (! DescriptionWriter::enabled()) {
            return response()->json(['message' => 'La rédaction automatique est désactivée (Paramètres › Rédaction automatique).'], 403);
        }

        $data = $request->validate([
            'type' => ['required', 'in:product,article'],
            'name' => ['required', 'string', 'min:2', 'max:200'],
            'variant' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'tag' => ['nullable', 'string', 'max:60'],
            'variation' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'ignore_id' => ['nullable', 'integer'],
        ], ['name.required' => 'Saisissez d’abord le nom.', 'name.min' => 'Le nom est trop court pour être rédigé.']);

        $variation = (int) ($data['variation'] ?? 0);

        if ($data['type'] === 'article') {
            $draft = DescriptionWriter::article($data['name'], $data['tag'] ?? null, $variation, $data['ignore_id'] ?? null);

            return response()->json(['html' => $draft['html'], 'excerpt' => $draft['excerpt'], 'source' => $draft['source']]);
        }

        $draft = DescriptionWriter::product([
            'name' => $data['name'],
            'variant' => $data['variant'] ?? null,
            'category' => isset($data['category_id']) ? Category::query()->whereKey($data['category_id'])->value('name') : null,
            'brand' => isset($data['brand_id']) ? Brand::query()->whereKey($data['brand_id'])->value('name') : null,
        ], $variation, $data['ignore_id'] ?? null);

        return response()->json(['html' => $draft['html'], 'source' => $draft['source']]);
    }
}

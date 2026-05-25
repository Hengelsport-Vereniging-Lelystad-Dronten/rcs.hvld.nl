<?php

namespace App\Http\Controllers;

use App\Models\ControleRonde;
use App\Services\VispasScanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class VispasScanController extends Controller
{
    public function store(Request $request, VispasScanner $scanner): JsonResponse
    {
        $validated = $request->validate([
            'controle_ronde_id' => ['required', 'exists:controle_rondes,id'],
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $ronde = ControleRonde::findOrFail($validated['controle_ronde_id']);
        $this->authorizeRondeAccess($ronde);

        if ($ronde->status !== ControleRonde::STATUS_ACTIEF) {
            return response()->json(['message' => 'Een VISpas kan alleen bij een actieve ronde worden gescand.'], 422);
        }

        $file = $request->file('foto');
        
        // 1. OPTIMALISATIE: Verklein de afbeelding in het geheugen voor de scanner
        // Dit voorkomt 413-fouten bij de externe API.
        $optimizedImage = Image::make($file);
        $optimizedImage->resize(1200, null, function ($constraint) {
            $constraint->aspectRatio();
        });
        
        // 2. Sla de originele foto op (voor archief)
        $path = $file->store("vispassen/ronde-{$ronde->id}", 'public');
        
        $scan = ['vispas_nummer' => null, 'confidence' => 0];
        $scanError = null;

        try {
            // Gebruik de geoptimaliseerde stream/data voor de scanner
            $scan = $scanner->scan($optimizedImage);
        } catch (\Throwable $exception) {
            $scanError = $scanner->errorMessageFor($exception);

            Log::warning('VISpas scan mislukt', [
                'controle_ronde_id' => $ronde->id,
                'path' => $path,
                'exception' => $exception->getMessage(),
            ]);
        }

        activity()
            ->performedOn($ronde)
            ->withProperties([
                'vispas_foto_path' => $path,
                'vispas_nummer_gevonden' => (bool) $scan['vispas_nummer'],
                'scan_confidence' => $scan['confidence'],
            ])
            ->log('VISpas foto geupload en gescand');

        return response()->json([
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'vispas_nummer' => $scan['vispas_nummer'],
            'confidence' => $scan['confidence'],
            'message' => $scanError,
        ]);
    }

    private function authorizeRondeAccess(ControleRonde $ronde): void
    {
        $user = auth()->user();
        $isOwner = (int) $ronde->user_id === (int) $user->id;
        $isBeheerder = method_exists($user, 'isBeheerder') && $user->isBeheerder();

        if (!$isOwner && !$isBeheerder) {
            abort(403);
        }
    }
}
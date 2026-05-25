<?php

namespace App\Http\Controllers;

use App\Models\ControleRonde;
use App\Services\VispasScanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
        
        // 2. Sla de originele foto op (voor archief)
        $path = $file->store("vispassen/ronde-{$ronde->id}", 'public');
        
        // 1. OPTIMALISATIE: Verklein de afbeelding met native GD voor de scanner
        // We maken een tijdelijk bestand aan voor de geoptimaliseerde versie
        $optimizedPath = tempnam(sys_get_temp_dir(), 'vispas_scan_');
        $sourceImage = imagecreatefromstring(file_get_contents($file->getRealPath()));
        
        if ($sourceImage) {
            $scaledImage = imagescale($sourceImage, 1200); // Schalen naar 1200px breed, ratio blijft behouden
            imagejpeg($scaledImage, $optimizedPath, 80); // Opslaan als JPG met 80% kwaliteit
            imagedestroy($sourceImage);
            imagedestroy($scaledImage);
        } else {
            $optimizedPath = $file->getRealPath(); // Fallback naar origineel als GD faalt
        }

        $scan = ['vispas_nummer' => null, 'confidence' => 0];
        $scanError = null;

        try {
            // Geef het pad van het geoptimaliseerde bestand door aan de scanner
            $scan = $scanner->scan($optimizedPath);
        } catch (\Throwable $exception) {
            $scanError = $scanner->errorMessageFor($exception);

            Log::warning('VISpas scan mislukt', [
                'controle_ronde_id' => $ronde->id,
                'path' => $path,
                'exception' => $exception->getMessage(),
            ]);
        } finally {
            if ($optimizedPath !== $file->getRealPath()) {
                @unlink($optimizedPath);
            }
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
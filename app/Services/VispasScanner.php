<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class VispasScanner
{
    public function scan(UploadedFile $image): array
    {
        $apiKey = config('services.ocrspace.api_key');

        if (!$apiKey) {
            throw new RuntimeException('OCR.space API key is niet geconfigureerd.');
        }

        $response = Http::withHeaders([
            'apikey' => $apiKey,
        ])
            ->timeout(30)
            ->retry(2, 300)
            ->attach(
                'file',
                file_get_contents($image->getRealPath()),
                $image->getClientOriginalName() ?: 'vispas.jpg'
            )
            ->post(config('services.ocrspace.endpoint'), [
                'language' => config('services.ocrspace.language', 'eng'),
                'isOverlayRequired' => 'false',
                'detectOrientation' => 'true',
                'scale' => 'true',
                'OCREngine' => config('services.ocrspace.engine', '2'),
            ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('OCR.space scan is mislukt: ' . $exception->getMessage(), previous: $exception);
        }

        return $this->parseResponse($response->json());
    }

    public function errorMessageFor(\Throwable $exception): string
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'status code 403') || str_contains($message, 'denied access')) {
            return 'Foto opgeslagen, maar OCR.space weigert toegang voor de ingestelde API-key.';
        }

        if (str_contains($message, 'status code 429')) {
            return 'Foto opgeslagen, maar OCR.space heeft tijdelijk geen capaciteit of quota beschikbaar.';
        }

        if (str_contains($message, 'status code 404')) {
            return 'Foto opgeslagen, maar de OCR.space endpoint-instelling klopt niet.';
        }

        if (str_contains($message, 'OCR.space API key is niet geconfigureerd')) {
            return 'Foto opgeslagen, maar de OCR.space API-key is niet geconfigureerd.';
        }

        return 'Foto opgeslagen, maar automatisch uitlezen is niet gelukt.';
    }

    private function parseResponse(?array $response): array
    {
        if (($response['IsErroredOnProcessing'] ?? false) === true) {
            $message = $response['ErrorMessage'] ?? 'OCR.space kon de afbeelding niet verwerken.';
            $message = is_array($message) ? implode(' ', $message) : $message;
            throw new RuntimeException($message);
        }

        $text = collect($response['ParsedResults'] ?? [])
            ->pluck('ParsedText')
            ->filter()
            ->implode("\n");

        $number = $this->extractVispasNumber($text);

        $confidence = $number ? 80 : 0;

        return [
            'vispas_nummer' => $number,
            'confidence' => $confidence,
            'raw_text' => trim($text),
        ];
    }

    public function extractVispasNumber(string $text): ?string
    {
        $lines = preg_split('/\R+/', $text) ?: [];
        $candidates = [];

        foreach ($lines as $index => $line) {
            preg_match_all('/\d[\d\s-]{5,}\d/', $line, $matches);

            foreach ($matches[0] as $match) {
                $digits = preg_replace('/\D+/', '', $match);

                if (strlen($digits) < 6 || strlen($digits) > 16) {
                    continue;
                }

                $score = strlen($digits);

                if (preg_match('/(^|\s)[nN]\s*\d/', $line)) {
                    $score += 20;
                }

                if (preg_match('/^\s*\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}\s*$/', $line)) {
                    $score -= 30;
                }

                if (preg_match('/^\s*20\d{2}\s*$/', $line)) {
                    $score -= 30;
                }

                $candidates[] = [
                    'digits' => $digits,
                    'score' => $score,
                    'line' => $index,
                ];
            }
        }

        if ($candidates === []) {
            preg_match_all('/\d{6,16}/', preg_replace('/\s+/', '', $text), $matches);
            $candidates = array_map(
                fn (string $digits) => ['digits' => $digits, 'score' => strlen($digits), 'line' => 999],
                $matches[0] ?? []
            );
        }

        usort($candidates, fn (array $a, array $b) => $b['score'] <=> $a['score'] ?: $a['line'] <=> $b['line']);

        return $candidates[0]['digits'] ?? null;
    }
}

<?php

namespace App\Support\ComputerVision;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ComputerVisionClient
{
    /**
     * Send the processing payload to the Python computer vision service and
     * return its decoded JSON response.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function process(array $payload): array
    {
        $response = Http::timeout(180)
            ->post(rtrim((string) config('services.computer_vision.url'), '/').'/process', $payload);

        if ($response->failed()) {
            throw new RuntimeException('Computer vision service request failed: '.$response->body());
        }

        return $response->json() ?? [];
    }
}

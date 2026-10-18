<?php

namespace App\Support\Grading;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GradingClient
{
    /**
     * Send a text-only grading request to the LLM provider and return its
     * decoded structured response.
     *
     * @return array<string, mixed>
     */
    public function grade(string $systemPrompt, string $userPrompt): array
    {
        $response = Http::timeout(120)
            ->withToken((string) config('services.grading.api_key'), 'Bearer')
            ->post(rtrim((string) config('services.grading.url'), '/'), [
                'model' => config('services.grading.model'),
                'temperature' => 0,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Grading service request failed: '.$response->body());
        }

        $content = data_get($response->json(), 'choices.0.message.content');

        if (is_array($content)) {
            $content = implode('', array_map(
                fn (array $part) => (string) ($part['text'] ?? ''),
                $content,
            ));
        }

        return $this->decode((string) $content);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $content): array
    {
        $content = trim($content);

        if (preg_match('/```(?:json)?\s*(.*?)```/s', $content, $matches)) {
            $content = trim($matches[1]);
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Grading service returned invalid JSON: '.$content);
        }

        return $decoded;
    }
}

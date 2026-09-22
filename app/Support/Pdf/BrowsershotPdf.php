<?php

namespace App\Support\Pdf;

use Spatie\Browsershot\Browsershot;

class BrowsershotPdf implements PdfRenderer
{
    public function generate(string $html): string
    {
        return $this->base($html)
            ->format('A4')
            ->margins(0, 0, 0, 0, 'mm')
            ->pdf();
    }

    public function measure(string $html): array
    {
        $result = $this->base($html)
            ->format('A4')
            ->margins(0, 0, 0, 0, 'mm')
            ->emulateMedia('print')
            ->evaluate($this->measurementScript());

        $decoded = json_decode($result, true);

        if (! is_array($decoded)) {
            return ['regions' => []];
        }

        $regions = [];

        foreach ($decoded['regions'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $regions[] = [
                'question_id' => isset($item['question_id']) ? (int) $item['question_id'] : null,
                'key' => isset($item['key']) ? (string) $item['key'] : null,
                'page' => (int) ($item['page'] ?? 0),
                'x' => (float) ($item['x'] ?? 0),
                'y' => (float) ($item['y'] ?? 0),
                'width' => (float) ($item['width'] ?? 0),
                'height' => (float) ($item['height'] ?? 0),
            ];
        }

        return ['regions' => $regions];
    }

    private function base(string $html): Browsershot
    {
        $browsershot = Browsershot::html($html)
            ->showBackground()
            ->noSandbox()
            ->setNodeEnv([
                'HOME' => sys_get_temp_dir(),
                'XDG_CONFIG_HOME' => sys_get_temp_dir().'/chromium-config',
            ])
            ->addChromiumArguments([
                'disable-dev-shm-usage',
                'disable-gpu',
                'disable-setuid-sandbox',
                'disable-crashpad',
                'disable-breakpad',
            ]);

        if ($chromePath = config('browsershot.chrome_path')) {
            $browsershot->setChromePath($chromePath);
        }

        if ($nodeBinary = config('browsershot.node_binary')) {
            $browsershot->setNodeBinary($nodeBinary);
        }

        if ($npmBinary = config('browsershot.npm_binary')) {
            $browsershot->setNpmBinary($npmBinary);
        }

        return $browsershot;
    }

    private function measurementScript(): string
    {
        return <<<'JS'
        (() => {
            const round = (n) => Math.round(n * 10000) / 10000;
            const pages = Array.from(document.querySelectorAll('.page'));

            const regionData = (el) => {
                const page = el.closest('.page');
                const pageIndex = pages.indexOf(page) + 1;
                const rect = el.getBoundingClientRect();
                const pageRect = page.getBoundingClientRect();
                return {
                    page: pageIndex,
                    x: round((rect.left - pageRect.left) / pageRect.width),
                    y: round((rect.top - pageRect.top) / pageRect.height),
                    width: round(rect.width / pageRect.width),
                    height: round(rect.height / pageRect.height),
                };
            };

            const regions = Array.from(document.querySelectorAll('.answer-region')).map((el) => {
                const question = el.closest('[data-question-id]');
                return {
                    question_id: question ? parseInt(question.getAttribute('data-question-id'), 10) : null,
                    key: el.getAttribute('data-region-key'),
                    ...regionData(el),
                };
            });

            return JSON.stringify({ regions });
        })()
        JS;
    }
}

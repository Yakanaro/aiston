<?php

namespace App\Services;

class FakeTranscriptionService
{
    public function process(string $audioUrl): array
    {
        usleep(200000);

        return [
            [
                'speaker' => 'S1',
                'start' => 0.0,
                'end' => 5.0,
                'text' => 'Добрый день, как я могу помочь?',
            ],
            [
                'speaker' => 'S2',
                'start' => 5.0,
                'end' => 10.0,
                'text' => 'Здравствуйте, у меня проблема с заказом.',
            ],
        ];
    }
}

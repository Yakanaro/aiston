<?php

namespace App\Services;

class FakeLlmService
{
    public function evaluate(array $segments): array
    {
        usleep(150000);
        $score = rand(60, 95);

        return [
            'overall_score' => $score,
            'details' => [
                'greeting' => true,
                'politeness' => $score > 70,
                'resolution' => $score > 80,
                'notes' => 'Фейковая оценка качества на основе транскрипта.',
            ],
        ];
    }
}

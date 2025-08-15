<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'audio_url' => 'required|string',
            'metadata' => 'array',
        ];
    }

    public function getAudioUrl(): string
    {
        return $this->validated('audio_url');
    }

    public function getMetadata(): array
    {
        return $this->validated('metadata', []);
    }
}

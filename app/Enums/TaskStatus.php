<?php

namespace App\Enums;

enum TaskStatus: string
{
    case NEW = 'new';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case ERROR = 'error';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Новая',
            self::PROCESSING => 'В обработке',
            self::COMPLETED => 'Завершена',
            self::FAILED => 'Неудачна',
            self::ERROR => 'Ошибка',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::ERROR]);
    }

    public function isActive(): bool
    {
        return in_array($this, [self::NEW, self::PROCESSING]);
    }
}

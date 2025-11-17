<?php

namespace App\Enums;

enum NoteType: string
{
    case GENERAL = 'general';
    case TRAINING = 'training';
    case HEALTH = 'health';
    case ACCOUNTING = 'accounting';
    
    public function getLabel(): string
    {
        return match($this) {
            self::GENERAL => __('enums.note_type.GENERAL'),
            self::TRAINING => __('enums.note_type.TRAINING'),
            self::HEALTH => __('enums.note_type.HEALTH'),
            self::ACCOUNTING => __('enums.note_type.ACCOUNTING'),
        };
    }
    
    public function getIcon(): string
    {
        return match($this) {
            self::GENERAL => 'document',
            self::TRAINING => 'academic-cap',
            self::HEALTH => 'heart',
            self::ACCOUNTING => 'currency-dollar',
        };
    }
    
    public function getColor(): string
    {
        return match($this) {
            self::GENERAL => 'gray',
            self::TRAINING => 'blue',
            self::HEALTH => 'red',
            self::ACCOUNTING => 'green',
        };
    }
}

<?php
  
namespace App\Enums;
 
enum KnowledgeStatusEnum : string {
    case Poor = 'zəif';
    case Good = 'yaxşı';
    case Fluent = 'sərbəst';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
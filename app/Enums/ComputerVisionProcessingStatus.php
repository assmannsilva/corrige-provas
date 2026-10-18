<?php

namespace App\Enums;

enum ComputerVisionProcessingStatus: string
{
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}

<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case Uploaded = 'uploaded';
    case Processing = 'processing';
    case ComputerVisionCompleted = 'computer_vision_completed';
    case Grading = 'grading';
    case Completed = 'completed';
    case Failed = 'failed';
}

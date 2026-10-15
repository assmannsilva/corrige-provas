<?php

namespace App\Enums;

enum SubmissionAnswerStatus: string
{
    case Extracted = 'extracted';
    case Failed = 'failed';
}

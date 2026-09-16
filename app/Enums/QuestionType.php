<?php

namespace App\Enums;

enum QuestionType: string
{
    case Essay = 'essay';
    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
}

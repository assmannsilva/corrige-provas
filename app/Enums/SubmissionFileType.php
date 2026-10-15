<?php

namespace App\Enums;

use Symfony\Component\HttpFoundation\File\UploadedFile;

enum SubmissionFileType: string
{
    case Pdf = 'pdf';
    case Image = 'image';

    public static function defineFileTypeFromUploadedFile(UploadedFile $file): SubmissionFileType
    {
        return str_starts_with((string) $file->getMimeType(), 'image/')
            ? SubmissionFileType::Image
            : SubmissionFileType::Pdf;
    }
}

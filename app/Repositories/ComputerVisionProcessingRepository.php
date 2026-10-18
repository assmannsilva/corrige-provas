<?php

namespace App\Repositories;

use App\Models\ComputerVisionProcessing;

class ComputerVisionProcessingRepository
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ComputerVisionProcessing
    {
        return ComputerVisionProcessing::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ComputerVisionProcessing $processing, array $data): ComputerVisionProcessing
    {
        $processing->update($data);

        return $processing;
    }
}

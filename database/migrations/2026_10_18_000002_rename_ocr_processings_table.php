<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('ocr_processings', 'computer_vision_processings');
    }

    public function down(): void
    {
        Schema::rename('computer_vision_processings', 'ocr_processings');
    }
};

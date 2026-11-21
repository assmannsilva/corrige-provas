<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

test('demo teacher can be created and reset without duplication', function () {
    expect(Artisan::call('demo:teacher'))->toBe(0);

    $teacher = User::query()->where('email', 'professor@example.test')->firstOrFail();

    expect($teacher->name)->toBe('Professor de Demonstração')
        ->and(Hash::check('password', $teacher->password))->toBeTrue()
        ->and($teacher->email_verified_at)->not->toBeNull();

    $originalId = $teacher->id;

    expect(Artisan::call('demo:teacher'))->toBe(0);

    $teacher->refresh();

    expect($teacher->id)->toBe($originalId)
        ->and(User::query()->where('email', 'professor@example.test')->count())->toBe(1)
        ->and(Hash::check('password', $teacher->password))->toBeTrue();
});

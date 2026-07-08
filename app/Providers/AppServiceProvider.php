<?php

namespace App\Providers;

use App\Enums\QuestionType;
use App\QuestionTypes\EssayType;
use App\QuestionTypes\MultipleChoiceType;
use App\QuestionTypes\QuestionTypeRegistry;
use App\QuestionTypes\SingleChoiceType;
use App\Renderers\EssayRenderer;
use App\Renderers\MultipleChoiceRenderer;
use App\Renderers\RendererRegistry;
use App\Renderers\SingleChoiceRenderer;
use App\Support\Grading\Graders\EssayGrader;
use App\Support\Grading\Graders\GraderRegistry;
use App\Support\Grading\Graders\MultipleChoiceGrader;
use App\Support\Grading\Graders\SingleChoiceGrader;
use App\Support\Pdf\BrowsershotPdf;
use App\Support\Pdf\PdfRenderer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(QuestionTypeRegistry::class, function () {
            $registry = new QuestionTypeRegistry;

            $registry->register(new EssayType);
            $registry->register(new SingleChoiceType);
            $registry->register(new MultipleChoiceType);

            return $registry;
        });

        $this->app->singleton(RendererRegistry::class, function () {
            $registry = new RendererRegistry;

            $registry->register(QuestionType::Essay, new EssayRenderer);
            $registry->register(QuestionType::SingleChoice, new SingleChoiceRenderer);
            $registry->register(QuestionType::MultipleChoice, new MultipleChoiceRenderer);

            return $registry;
        });

        $this->app->singleton(GraderRegistry::class, function ($app) {
            $registry = new GraderRegistry;

            $registry->register(QuestionType::Essay, $app->make(EssayGrader::class));
            $registry->register(QuestionType::SingleChoice, $app->make(SingleChoiceGrader::class));
            $registry->register(QuestionType::MultipleChoice, $app->make(MultipleChoiceGrader::class));

            return $registry;
        });

        $this->app->bind(PdfRenderer::class, BrowsershotPdf::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}

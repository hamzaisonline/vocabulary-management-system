<?php

namespace App\Providers;

use App\Models\SchoolClass;
use App\Models\VocabularyLevel;
use App\Models\VocabularyWord;
use App\Policies\SchoolClassPolicy;
use App\Policies\VocabularyPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        SchoolClass::class => SchoolClassPolicy::class,
        VocabularyLevel::class => VocabularyPolicy::class,
        VocabularyWord::class => VocabularyPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}

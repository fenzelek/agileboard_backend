<?php

namespace App\Providers;

use App\Models\Db\KnowledgePageComment;
use App\Models\Db\Package;
use App\Models\Db\Payment;
use App\Models\Db\Transaction;
use App\Models\Db\File;
use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Models\Db\Ticket;
use App\Models\Db\Story;
use App\Models\Db\TicketComment;
use Modular;
use App\Models\Db\User;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to the controller routes in your routes file.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Route::model('user', User::class);
        Route::model('payment', Payment::class);
        Route::model('transaction', Transaction::class);
        Route::model('package', Package::class);
        Route::model('project', Project::class);
        Route::model('file', File::class);
        Route::model('sprint', Sprint::class);
        Route::model('story', Story::class);
        Route::model('ticket_comment', TicketComment::class);
        Route::bind('ticket', function ($value) {
            return Ticket::where('id', $value)->orWhere('title', mb_strtoupper($value))->first();
        });
        Route::model('page_comment', KnowledgePageComment::class);
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        Route::group(['namespace' => $this->namespace], function ($router) {
            require app_path('Http/routes.php');
        });

        Modular::loadRoutes($this->app['router']);
    }
}

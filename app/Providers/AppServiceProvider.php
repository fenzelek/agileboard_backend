<?php

namespace App\Providers;

use App\Interfaces\Interactions\IInteractionFacade;
use App\Models\Db\CompanyToken;
use App\Models\Db\File;
use App\Models\Db\Knowledge\KnowledgeDirectory;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\KnowledgePageComment;
use App\Models\Db\Project;
use App\Models\Db\Story;
use App\Models\Db\Ticket;
use App\Models\Db\TicketComment;
use App\Models\Notification\Contracts\IInteractionNotificationManager;
use App\Models\Other\MorphMap;
use App\Modules\Agile\Observers\TicketCommentObserver;
use App\Modules\Agile\Observers\TicketObserver;
use App\Modules\Company\Services\Token;
use App\Modules\Interaction\Services\InteractionFacade;
use App\Modules\Notification\Services\InteractionNotificationManager;
use App\Modules\Project\Observers\ProjectObserver;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['validator']->extend('amount_min', function ($attribute, $value, $parameters) {
            $item = explode('.', $attribute)[1];
            if (! empty($parameters[$item])) {
                return ($value > -100000);
            }

            return ($value > 0);
        }, trans('validation.amount_min'));

        $this->app['validator']->extend('delivery_address', '\App\Modules\SaleInvoice\Services\DeliveryAddress@validateDeliveryAddress');
        $this->app['validator']->extend('invoice_registries_prefix', '\App\Modules\SaleInvoice\Services\InvoiceSettings@validateRegistryPrefix');
        $this->app['validator']->extend('registries_start_number', '\App\Modules\SaleInvoice\Services\InvoiceSettings@validateRegistryStartNumber');
        $this->app['validator']->extend('allow_updating_invoice', '\App\Modules\SaleInvoice\Services\Invoice@validateAllowUpdatingInvoice');
        $this->app['validator']->extend('decimal_quantity', '\App\Modules\SaleInvoice\Services\Invoice@validateDecimalQuantity');
        $this->app['validator']->extend('check_polish_zip_code', '\App\Modules\Contractor\Services\Contractor@validatePolishZipCode');
        $this->app['validator']->extend('invoice_is_editable', '\App\Modules\SaleInvoice\Services\Invoice@validateIsEditableInvoice');

        // @todo morphMap - created resource alias instead of the model path that will be saved in
        // the database
        Relation::morphMap([
            MorphMap::FILES => File::class,
            MorphMap::KNOWLEDGE_PAGES => KnowledgePage::class,
            MorphMap::KNOWLEDGE_DIRECTORIES => KnowledgeDirectory::class,
            MorphMap::STORIES => Story::class,
            MorphMap::TICKETS => Ticket::class,
            MorphMap::TICKET_COMMENTS => TicketComment::class,
            MorphMap::KNOWLEDGE_PAGE_COMMENTS => KnowledgePageComment::class,
        ]);

        //observers
        Ticket::observe(TicketObserver::class);
        TicketComment::observe(TicketCommentObserver::class);
        Project::observe(ProjectObserver::class);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment('local', 'testing')) {
            $this->registerLocalProviders();
        }

        $this->bindImplementationToInterfaces();

        $this->app->bind(Token::class, function () {
            return new Token(
                $this->app->make(CompanyToken::class),
                $this->app['config']->get('services.external_api.key')
            );
        });
    }

    /**
     * Register local providers that should be used only for development
     * purposes.
     */
    protected function registerLocalProviders()
    {
        $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        $this->app->register(\App\Services\Mnabialek\LaravelSqlLogger\Providers\ServiceProvider::class);
    }

    /**
     * Bind interfaces implementations.
     */
    protected function bindImplementationToInterfaces()
    {
        $this->app->bind(
            \App\Modules\CalendarAvailability\Contracts\CalendarAvailability::class,
            \App\Modules\CalendarAvailability\Services\CalendarAvailability::class
        );
        $this->app->bind(
            IInteractionNotificationManager::class,
            InteractionNotificationManager::class
        );
        $this->app->bind(
            \App\Modules\Interaction\Contracts\IInteractionManager::class,
            \App\Modules\Interaction\Services\InteractionManager::class
        );
        $this->app->bind(
            IInteractionFacade::class,
            InteractionFacade::class
        );
    }
}

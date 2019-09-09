<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use App\Extensions\Pagination\CursorPaginator;

class CursorPaginationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerMacro();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Create Macros for the Builders.
     */
    public function registerMacro()
    {
        /**
         * Retrieve all data of repository, paginated
         *
         * @param string $identifier
         * @param int $perPage
         * @param array $columns
         * @param boolean $dateIdentifier
         * @param string $prevPageName
         * @param string $nextPageName
         *
         * @return CursorPaginator
         */
        $macro = function ($identifier, $perPage = null, $columns = ['*'], $dateIdentifier = false, $prevPageName = 'prev_id', $nextPageName = 'next_id') {

            $perPage = $perPage ?: $this->model->getPerPage();

            $this->take($perPage + 1);

            return new CursorPaginator($identifier, $this->get($columns), $perPage, [
                'path' => CursorPaginator::resolveCurrentPath(),
                'dateIdentifier' => $dateIdentifier,
                'prevPageName' => $prevPageName,
                'nextPageName' => $nextPageName,
            ]);
        };

        // Register macros
        QueryBuilder::macro('cursorPaginate', $macro);
        EloquentBuilder::macro('cursorPaginate', $macro);
    }
}
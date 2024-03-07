<?php

namespace App\Services;

use App\Models\Other\Paginated;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorInstance;

class Paginator
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var UrlGenerator
     */
    protected $urlGenerator;

    /**
     * @var Container
     */
    protected $app;

    /**
     * Paginator constructor.
     *
     * @param Container $app
     */
    public function __construct(Container $app)
    {
        $this->config = $app['config'];
        $this->request = $app['request'];
        $this->urlGenerator = $app['url'];
        $this->app = $app;
    }

    /**
     * Get paginated data for current User.
     *
     * @param Builder $query
     * @param string $route_name Route name
     * @param array $route_params Route url parameters
     * @param int|null $limit Limit per page. If none given
     *
     * @return LengthAwarePaginator
     */
    public function get(
        $query,
        $route_name,
        array $route_params = [],
        $limit = null
    ) {
        $limit = $limit ?: $this->request->input('limit', 50);

        // get paginated data
        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate($this->getLimit((int) $limit));

        return $this->setPaginatorAttributes($paginator, $route_name, $route_params);
    }

    /**
     * Decorate paginated object.
     *
     * @param Paginated $paginated
     * @param string $route_name Route name
     * @param array $route_params Route url parameters
     *
     * @return LengthAwarePaginator
     */
    public function decorate(Paginated $paginated, $route_name, array $route_params = [])
    {
        // transform paginator into LengthAwarePaginator object
        $paginator = new LengthAwarePaginatorInstance(
            $paginated->items(),
            $paginated->total(),
            $paginated->limit(),
            $paginated->page()
        );

        return $this->setPaginatorAttributes($paginator, $route_name, $route_params);
    }

    /**
     * Calculates limit based on user input and system settings.
     *
     * @param $inputLimit
     *
     * @return mixed
     */
    public function getLimit($inputLimit)
    {
        $maxLimit = $this->getMaxLimit();

        // limit set to 0 - we will use max available limit
        if ($inputLimit == 0) {
            return $maxLimit;
        }

        // custom limit set - we use it or set to max if it's greater than max
        return $inputLimit > $maxLimit ? $maxLimit : $inputLimit;
    }

    /**
     * Set extra paginator attributes.
     *
     * @param LengthAwarePaginator $paginator
     * @param string $route_name
     * @param array $route_params
     *
     * @return LengthAwarePaginator
     */
    protected function setPaginatorAttributes(
        LengthAwarePaginator $paginator,
        $route_name,
        array $route_params
    ) {
        // set valid query string into paginator and relative path
        $paginator->appends($this->request->query());
        $paginator->setPath($this->urlGenerator->route($route_name, $route_params, false));

        return $paginator;
    }

    /**
     * Get max pagination limit.
     *
     * @return int
     */
    protected function getMaxLimit()
    {
        return $this->config->get('pagination.max');
    }
}

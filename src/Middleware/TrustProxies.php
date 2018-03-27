<?php

namespace iMemento\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Fideloper\Proxy\TrustProxies as Middleware;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array
     */
    protected $proxies;

    /**
     * The current proxy header mappings.
     *
     * @var string
     */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;

    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        // this should be changed in the future to something else!
        $this->proxies = [];
        array_push($this->proxies, $request->server('REMOTE_ADDR'));

        return parent::handle($request, $next);
    }
}

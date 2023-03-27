<?php

namespace App\Http\Integrations\Steam\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetAppList extends Request
{
    /**
     * Define the HTTP method
     */
    protected Method $method = Method::GET;

    /**
     * Define the endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return '/isteamapps/getapplist/v0002/';
    }
}

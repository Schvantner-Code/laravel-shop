<?php

namespace App\Http\Requests\Traits;

trait HasScribeBodyParameters
{
    /**
     * Mark validation rules as request body parameters for Scribe.
     *
     * Scribe warns when a body Form Request does not define this method. The
     * empty array is intentional: rules and BodyParam attributes provide the
     * parameter details, so there are no additional overrides to return here.
     *
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [];
    }
}

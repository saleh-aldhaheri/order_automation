<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function resolvePagination(Request $request)
    {
        $perPageOptions = [5, 10, 25, 50, 100];
        $perPage = (int) $request->query('per_page', $perPageOptions[0]);
        $perPage = in_array($perPage, $perPageOptions, strict: true) ? $perPage :
            $perPageOptions[0];

        return [$perPage, $perPageOptions];
    }

    protected function getSearch(Request $request): ?string
    {
        $search = $request->query('search');

        if (! is_string($search)) {
            return null;
        }

        $search = trim($search);

        return $search === '' ? null : $search;
    }
}

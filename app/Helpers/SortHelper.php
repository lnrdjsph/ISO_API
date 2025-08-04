<?php

if (!function_exists('sortUrl')) {
    function sortUrl($column) {
        $direction = (request('sort') === $column && request('direction') === 'asc') ? 'desc' : 'asc';
        return request()->fullUrlWithQuery(['sort' => $column, 'direction' => $direction]);
    }
}

<?php

namespace App\Http\Controllers\Concerns;

trait FiltersSortsAndPaginates
{
    /**
     * Substring LIKE filter. Treats a missing/empty/non-scalar value (e.g.
     * a malformed `?name[]=a&name[]=b` query string, which PHP parses as an
     * array) as "not provided" rather than letting it reach the query
     * builder, where concatenating an array into a LIKE pattern throws
     * "Array to string conversion". LIKE-special characters in the value
     * are escaped so a literal `%` or `_` in a search term doesn't act as
     * a wildcard.
     */
    protected function applyLikeFilter($query, $request, string $key, string $column)
    {
        $value = $request->input($key);
        if (!is_scalar($value) || $value === '') {
            return;
        }

        $escaped = addcslashes((string) $value, '%_\\');
        $query->where($column, 'LIKE', '%' . $escaped . '%');
    }

    /**
     * "Starts with" LIKE filter -- same guarding/escaping as applyLikeFilter,
     * anchored to the start of the value instead of substring-anywhere.
     */
    protected function applyStartsWithFilter($query, $request, string $key, string $column)
    {
        $value = $request->input($key);
        if (!is_scalar($value) || $value === '') {
            return;
        }

        $escaped = addcslashes((string) $value, '%_\\');
        $query->where($column, 'LIKE', $escaped . '%');
    }

    /**
     * Exact-match filter (e.g. a foreign-key id like sub_categories'
     * category_id). Same array-param guard as the LIKE filters.
     */
    protected function applyEqualsFilter($query, $request, string $key, string $column)
    {
        $value = $request->input($key);
        if (!is_scalar($value) || $value === '') {
            return;
        }

        $query->where($column, $value);
    }

    /**
     * year/month filter against a date/timestamp column. month is only
     * applied when year is also present, matching every controller's
     * existing convention (a month alone is a no-op).
     */
    protected function applyYearMonthFilter($query, $request, string $column = 'created_at')
    {
        $year = $request->input('year');
        if (!is_scalar($year) || $year === '') {
            return;
        }

        $query->whereYear($column, $year);

        $month = $request->input('month');
        if (is_scalar($month) && $month !== '') {
            $query->whereMonth($column, $month);
        }
    }

    /**
     * Min/max range filter against a column that stores numeric data as a
     * varchar (see the Domain-Models vault note) -- CAST to DECIMAL so the
     * comparison is numeric, not lexicographic string comparison. Bound
     * parameters throughout, never string-interpolated.
     */
    protected function applyNumericRangeFilter($query, $request, string $column, string $minKey, string $maxKey)
    {
        $min = $request->input($minKey);
        if (is_scalar($min) && $min !== '') {
            $query->whereRaw('CAST(' . $column . ' AS DECIMAL(10,2)) >= ?', [(float) $min]);
        }

        $max = $request->input($maxKey);
        if (is_scalar($max) && $max !== '') {
            $query->whereRaw('CAST(' . $column . ' AS DECIMAL(10,2)) <= ?', [(float) $max]);
        }
    }

    /**
     * Resolve sort_by/sort_dir against an allow-list (silent fallback to
     * $defaultColumn/'asc' on an invalid value -- never passed raw to
     * orderBy()), apply the primary sort, then apply an UNCONDITIONAL
     * deterministic tiebreaker on $tiebreakerColumn. The tiebreaker is
     * always applied, on every code path, regardless of which column was
     * sorted on -- this is load-bearing: without it, MySQL's result order
     * among ties on a non-unique sort column is undefined and pagination
     * silently duplicates/drops rows across pages (a Critical bug found
     * and fixed in this initiative's Batch 2).
     *
     * $castNumericColumns lists which of $allowedColumns store numeric
     * data as a varchar and need CAST(col AS DECIMAL(10,2)) instead of a
     * plain orderBy() -- see applyNumericRangeFilter's docblock.
     */
    protected function resolveSortAndApply($query, $request, array $allowedColumns, string $defaultColumn, string $tiebreakerColumn = 'id', array $castNumericColumns = [])
    {
        $sortBy = $request->get('sort_by', $defaultColumn);
        $sortDir = $request->get('sort_dir', 'asc');

        if (!in_array($sortBy, $allowedColumns, true)) {
            $sortBy = $defaultColumn;
        }
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        if (in_array($sortBy, $castNumericColumns, true)) {
            $query->orderByRaw('CAST(' . $sortBy . ' AS DECIMAL(10,2)) ' . $sortDir);
        } else {
            $query->orderBy($sortBy, $sortDir);
        }
        $query->orderBy($tiebreakerColumn, $sortDir);
    }

    /**
     * Resolve per_page from the request, clamped to [1, $max]. Falls back
     * to $default specifically when the raw value is missing, empty, or
     * non-numeric -- fixes a bug present in every controller migrated
     * before this trait existed, where `(int) 'abc' == 0` clamped to the
     * floor of 1 instead of the intended default of 10.
     */
    protected function resolvePerPage($request, int $default = 10, int $max = 100)
    {
        $raw = $request->get('per_page', $default);
        $perPage = is_numeric($raw) ? (int) $raw : $default;

        return min(max($perPage, 1), $max);
    }

    /**
     * The standard {success, data, meta} paginated response shape used by
     * every list page in this initiative.
     */
    protected function paginatedResponse($paginator)
    {
        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BuyerCatalogSearch
{
    public function products(Builder $query, string $search): Builder
    {
        return $this->matching($query, 'name', $search);
    }

    public function shops(string $search): Builder
    {
        $query = SellerProfile::query()
            ->where('status', 'approved')
            ->whereIn('user_id', User::eligibleSellers()->select('id'))
            ->with('user.sellerProfile');

        return $this->matching($query, 'business_name', $search);
    }

    private function matching(Builder $query, string $column, string $search): Builder
    {
        $terms = preg_split('/[^\p{L}\p{N}]+/u', Str::lower($search), flags: PREG_SPLIT_NO_EMPTY) ?: [];

        if ($terms === []) {
            return $query->whereRaw('1 = 0');
        }

        $directMatches = $this->whereTerms(clone $query, $column, $terms, false);

        if ($directMatches->exists()) {
            return $directMatches;
        }

        return $this->whereTerms($query, $column, $terms, true);
    }

    /** @param list<string> $terms */
    private function whereTerms(Builder $query, string $column, array $terms, bool $allowTypos): Builder
    {
        foreach ($terms as $term) {
            $patterns = $allowTypos ? $this->typoPatterns($term) : [$term];

            $query->where(function (Builder $matches) use ($column, $patterns): void {
                foreach ($patterns as $pattern) {
                    $matches->orWhereLike($column, '%'.$pattern.'%');
                }
            });
        }

        return $query;
    }

    /**
     * LIKE patterns for one missing, extra, replaced, or transposed character.
     *
     * @return list<string>
     */
    private function typoPatterns(string $term): array
    {
        $patterns = [$term];
        $length = Str::length($term);

        if ($length < 3 || $length > 32) {
            return $patterns;
        }

        for ($index = 0; $index < $length; $index++) {
            $before = Str::substr($term, 0, $index);
            $after = Str::substr($term, $index + 1);

            if ($length >= 4) {
                $patterns[] = $before.$after;
            }

            $patterns[] = $before.'_'.$after;
            $patterns[] = $before.'_'.Str::substr($term, $index);

            if ($index < $length - 1) {
                $patterns[] = $before.Str::substr($term, $index + 1, 1).Str::substr($term, $index, 1).Str::substr($term, $index + 2);
            }
        }

        return array_values(array_unique($patterns));
    }
}

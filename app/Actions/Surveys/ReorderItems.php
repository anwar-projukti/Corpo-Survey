<?php

namespace App\Actions\Surveys;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Applies a new `order` to a set of sibling rows (sections within a
 * version, questions within a section, or options within a question) in
 * one transaction. Shared by every reorder endpoint rather than
 * reimplemented per-controller.
 */
class ReorderItems
{
    /**
     * @param  Relation<*, *, *>  $siblingsQuery  scoped to exactly the rows allowed to be reordered (e.g. $section->questions())
     * @param  array<int, int>  $orderedIds  every sibling's id, in the desired order
     */
    public function handle(Relation $siblingsQuery, array $orderedIds): void
    {
        $siblingIds = $siblingsQuery->pluck('id');

        if ($siblingIds->count() !== count($orderedIds) || $siblingIds->diff($orderedIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'order' => 'The submitted order must contain exactly the current set of items, no more or fewer.',
            ]);
        }

        DB::transaction(function () use ($siblingsQuery, $orderedIds) {
            foreach (array_values($orderedIds) as $position => $id) {
                (clone $siblingsQuery)->whereKey($id)->update(['order' => $position]);
            }
        });
    }
}

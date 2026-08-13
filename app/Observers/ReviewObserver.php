<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\ExpertProfile;
use App\Models\Review;

class ReviewObserver
{
    /**
     * Handle the Review "created" event.
     */
    public function created(Review $review): void
    {
        $this->updateExpertRating($review->expert_id);
    }

    /**
     * Handle the Review "updated" event.
     */
    public function updated(Review $review): void
    {
        if ($review->isDirty('rating')) {
            $this->updateExpertRating($review->expert_id);
        }
    }

    /**
     * Handle the Review "deleted" event.
     */
    public function deleted(Review $review): void
    {
        $this->updateExpertRating($review->expert_id);
    }

    /**
     * Recalculate the expert's average rating and total successful cases.
     */
    private function updateExpertRating(int $expertId): void
    {
        $expertProfile = ExpertProfile::where('user_id', $expertId)->first();

        if (! $expertProfile) {
            return;
        }

        // Calculate average rating
        $avgRating = Review::where('expert_id', $expertId)->avg('rating');

        // Note: successful_cases_count usually counts cases that are completed.
        // We can either count the number of completed cases directly, or count reviews.
        // Usually, not all clients leave a review, so successful_cases_count should be
        // updated when the case itself is marked as 'completed'.
        // However, if the user requested it to be auto-updated here, we can just do that.
        // Let's count actual completed cases in the system for this expert.
        $successfulCases = \App\Models\LegalCase::where('expert_id', $expertId)
            ->where('status', 'completed')
            ->count();

        $expertProfile->update([
            'rating'                 => $avgRating ? round($avgRating, 2) : 0.00,
            'successful_cases_count' => $successfulCases,
        ]);
    }
}

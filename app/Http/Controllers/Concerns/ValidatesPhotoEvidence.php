<?php

namespace App\Http\Controllers\Concerns;

trait ValidatesPhotoEvidence
{
    /**
     * Enforce: the "good" status requires 1-2 photos (note optional); any
     * other status requires a note (photos become optional, capped at 2
     * either way).
     */
    protected function validatePhotoEvidence($status, $goodValue, $note, $existingPhotoCount, $newPhotoCount)
    {
        $errors = [];
        $totalPhotos = $existingPhotoCount + $newPhotoCount;

        if ($status === $goodValue) {
            if ($totalPhotos < 1) {
                $errors['photos'] = ["At least 1 photo is required when marked as \"{$goodValue}\"."];
            } elseif ($totalPhotos > 2) {
                $errors['photos'] = ["A maximum of 2 photos is allowed."];
            }
        } else {
            if (trim((string) $note) === '') {
                $errors['note'] = ["A note is required when not marked as \"{$goodValue}\"."];
            }
            if ($totalPhotos > 2) {
                $errors['photos'] = ["A maximum of 2 photos is allowed."];
            }
        }

        return $errors;
    }
}

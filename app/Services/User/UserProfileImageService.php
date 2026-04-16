<?php

namespace App\Services\User;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserProfileImageService
{
    /**
     * Upload profile image to public assets and optionally remove old image.
     */
    public function uploadProfileImage(Request $request, ?string $oldImage = null): ?string
    {
        if (!$request->hasFile('profile_image')) {
            return $oldImage;
        }

        $file = $request->file('profile_image');
        $directory = public_path('assets/img/profiles');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if ($oldImage) {
            $oldPath = $directory . DIRECTORY_SEPARATOR . $oldImage;
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        $filename = 'user-' . time() . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return $filename;
    }

    /**
     * Resolve a user profile image path/filename to a public URL.
     */
    public function resolveProfileImageUrl(?string $profileImage): string
    {
        $defaultImage = asset('assets/img/profiles/default-avatar.svg');
        $rawProfileImage = trim((string) ($profileImage ?? ''));

        if ($rawProfileImage === '') {
            return $defaultImage;
        }

        if (preg_match('/^https?:\/\//i', $rawProfileImage)) {
            return $rawProfileImage;
        }

        if (str_starts_with($rawProfileImage, '/')) {
            $publicRelativePath = ltrim($rawProfileImage, '/');
        } elseif (str_starts_with($rawProfileImage, 'assets/')) {
            $publicRelativePath = $rawProfileImage;
        } else {
            $publicRelativePath = 'assets/img/profiles/' . $rawProfileImage;
        }

        if (!file_exists(public_path($publicRelativePath))) {
            return $defaultImage;
        }

        return asset($publicRelativePath);
    }
}

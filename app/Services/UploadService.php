<?php

namespace App\Services;

use Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class UploadService {
    public static function upload($requestFile, $folder) {
        try {
            if (Auth::user() && Auth::user()->school_id) {
                $folder = Auth::user()->school_id.'/'.$folder;
            } else {
                $folder = 'super-admin/'.$folder;
            }
            
            $file_name = uniqid('', true) . time() . '.' . $requestFile->getClientOriginalExtension();
            
            // Validate file before processing
            if (!$requestFile || !$requestFile->isValid()) {
                throw new \Exception('Invalid file uploaded');
            }
            
            if (in_array(strtolower($requestFile->getClientOriginalExtension()), ['jpg', 'jpeg', 'png'])) {
                try {
                    // Check the Extension should be jpg or png and do compression
                    $image = Image::make($requestFile)->encode(null, 60);
                    Storage::disk('public')->put($folder . '/' . $file_name, $image);
                } catch (\Exception $e) {
                    // Fallback if image processing fails (GD library issues)
                    \Log::warning('Image processing failed, using original file: ' . $e->getMessage());
                    $file = $requestFile;
                    $file->storeAs($folder, $file_name, 'public');
                }
            } else {
                // Else assign file as it is
                $file = $requestFile;
                $file->storeAs($folder, $file_name, 'public');
            }
            
            return $folder . '/' . $file_name;
            
        } catch (\Exception $e) {
            \Log::error('UploadService error: ' . $e->getMessage());
            throw new \Exception('File upload failed: ' . $e->getMessage());
        }
    }

    /**
     * @param $image = rawOriginalPath
     * @return bool
     */
    public static function delete($image) {
        if ($image && Storage::disk('public')->exists($image)) {
            return Storage::disk('public')->delete($image);
        }


        //Image does not exist in server so feel free to upload new image
        return true;
    }

}

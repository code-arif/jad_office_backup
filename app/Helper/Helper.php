<?php

namespace App\Helper;

use Exception;
use Illuminate\Support\Str;
use Kreait\Firebase\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class Helper
{
    public static function uploadImage($file, $folder)
    {

        if (!$file->isValid()) {
            return null;
        }

        $imageName = time() . '-' . Str::random(5) . '.' . $file->extension(); // Unique name
        $path = public_path('uploads/' . $folder);

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $file->move($path, $imageName);
        return 'uploads/' . $folder . '/' . $imageName;
    }

    public static function fileUpload($file, string $folder, string $name): ?string
    {
        if (!$file->isValid()) {
            return null;
        }

        $imageName = Str::slug($name) . '.' . $file->extension();
        $path      = public_path('uploads/' . $folder);
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $file->move($path, $imageName);
        return 'uploads/' . $folder . '/' . $imageName;
    }



    public static function deleteImage($imageUrl)
    {
        if (!$imageUrl) {
            return false;
        }
        $filePath = public_path($imageUrl);
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }

    // public static function deleteImage(string $path)
    // {
    //     if (file_exists($path)) {
    //         unlink($path);
    //     }
    // }


    public static function deleteImages($imageUrls)
    {
        if (is_array($imageUrls)) {
            foreach ($imageUrls as $imageUrl) {
                $baseUrl = url('/');
                $relativePath = str_replace($baseUrl . '/', '', $imageUrl);
                $fullPath = public_path($relativePath);


                if (file_exists($fullPath) && is_file($fullPath)) {

                    if (!unlink($fullPath)) {
                        return false;
                    }
                }
            }
            return true;
        }

        return false;
    }


    // calculate age from date of birth
    public static function calculateAge($dateOfBirth)
    {
        if (!$dateOfBirth) {
            return null;
        }

        $dob = \Carbon\Carbon::parse($dateOfBirth);
        $now = \Carbon\Carbon::now();

        return (int) $dob->diffInYears($now);
    }

    //! JSON Response
    public static function jsonResponse(bool $status, string $message, int $code, $data = null, bool $paginate = false, $paginateData = null): JsonResponse
    {
        $response = [
            'status'  => $status,
            'message' => $message,
            'code'    => $code,
        ];
        if ($paginate && !empty($paginateData)) {
            $response['data'] = $data;
            $response['pagination'] = [
                'current_page' => $paginateData->currentPage(),
                'last_page' => $paginateData->lastPage(),
                'per_page' => $paginateData->perPage(),
                'total' => $paginateData->total(),
                'first_page_url' => $paginateData->url(1),
                'last_page_url' => $paginateData->url($paginateData->lastPage()),
                'next_page_url' => $paginateData->nextPageUrl(),
                'prev_page_url' => $paginateData->previousPageUrl(),
                'from' => $paginateData->firstItem(),
                'to' => $paginateData->lastItem(),
                'path' => $paginateData->path(),
            ];
        } elseif ($paginate && !empty($data)) {
            $response['data'] = $data->items();
            $response['pagination'] = [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'first_page_url' => $data->url(1),
                'last_page_url' => $data->url($data->lastPage()),
                'next_page_url' => $data->nextPageUrl(),
                'prev_page_url' => $data->previousPageUrl(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
                'path' => $data->path(),
            ];
        } elseif ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    public static function jsonErrorResponse(string $message, int $code = 400, array $errors = []): JsonResponse
    {
        $response = [
            'status'  => false,
            'message' => $message,
            'code'    => $code,
            't-errors'  => $errors,
        ];
        return response()->json($response, $code);
    }

    public static function sendNotifyMobile($token, $notifyData): void
    {
        $path = storage_path('app/private/firebase.json');
        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);

        if (!file_exists($path) || !is_readable($path)) {
            throw new \Exception("Firebase JSON file does not exist or is not readable: $path");
        }

        $factory = (new \Kreait\Firebase\Factory)
            ->withServiceAccount($path);

        $messaging = $factory->createMessaging();

        $notification = \Kreait\Firebase\Messaging\Notification::create(
            $notifyData['title'],
            \Illuminate\Support\Str::limit($notifyData['body'], 100),
            $notifyData['icon'] ?? null
        );

        $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $token)
            ->withNotification($notification);

        $messaging->send($message);
    }
}

<?php

// Read project_id from Firebase credentials file if available
$credentialsPath = storage_path('firebase/firebase_credentials.json');
$projectId = env('FIREBASE_PROJECT_ID', '');

if (empty($projectId) && file_exists($credentialsPath)) {
    try {
        $credentials = json_decode(file_get_contents($credentialsPath), true);
        $projectId = $credentials['project_id'] ?? '';
    } catch (\Exception $e) {
        // If we can't read the file, fall back to env
    }
}

// Derive auth_domain and storage_bucket from project_id if not set
$authDomain = env('FIREBASE_AUTH_DOMAIN', '');
$storageBucket = env('FIREBASE_STORAGE_BUCKET', '');

if (!empty($projectId)) {
    if (empty($authDomain)) {
        $authDomain = $projectId . '.firebaseapp.com';
    }
    if (empty($storageBucket)) {
        $storageBucket = $projectId . '.appspot.com';
    }
}

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Web Configuration
    |--------------------------------------------------------------------------
    |
    | These configuration values are used for Firebase client-side authentication
    | in the web application. You can find these values in your Firebase Console
    | under Project Settings > General > Your apps.
    |
    | The project_id, auth_domain, and storage_bucket are automatically derived
    | from the Firebase credentials file if not set in .env
    |
    */

    'api_key' => env('FIREBASE_API_KEY', ''),
    'auth_domain' => $authDomain,
    'project_id' => $projectId,
    'storage_bucket' => $storageBucket,
    'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID', ''),
    'app_id' => env('FIREBASE_APP_ID', ''),
];

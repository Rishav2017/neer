<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth as FirebaseAuth;

class FirebaseService
{
  protected FirebaseAuth $auth;

  public function __construct()
  {
    $credentialsPath = storage_path('firebase/firebase_credentials.json');
    
    if (!file_exists($credentialsPath)) {
      throw new \RuntimeException('Firebase credentials file not found at: ' . $credentialsPath);
    }

    $factory = (new Factory)->withServiceAccount($credentialsPath);
    $this->auth = $factory->createAuth();
  }

  public function verifyIdToken(string $idToken)
  {
    return $this->auth->verifyIdToken($idToken);
  }
}

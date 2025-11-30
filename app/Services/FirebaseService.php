<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth as FirebaseAuth;

class FirebaseService
{
  protected FirebaseAuth $auth;

  public function __construct()
  {
    $factory = (new Factory)->withServiceAccount(storage_path('firebase/firebase_credentials.json'));
    $this->auth = $factory->createAuth();
  }

  public function verifyIdToken(string $idToken)
  {
    return $this->auth->verifyIdToken($idToken);
  }
}

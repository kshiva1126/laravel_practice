<?php

namespace  App\Services\Cognito;

use App\Services\Cognito\JWTVerifier;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

class CognitoGuard implements Guard
{
    use GuardHelpers;

    /**
     * @var JWTVerifier
     */
    private $JWTVerifier;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var UserProvider
     */
    private $userProvider;

    /**
     * CognitoGuard cosntructor.
     *
     * @param JWTVerifier $jWTVerifier
     * @param Request $request
     * @param UserProvider $userProvider
     */
    public function __construct(
        JWTVerifier $jWTVerifier,
        Request $request,
        UserProvider $userProvider
    )
    {
        $this->JWTVerifier = $jWTVerifier;
        $this->request = $request;
        $this->userProvider = $userProvider;
    }

    public function user()
    {
        if ($this->user) {
            return $this->user;
        }

        $jwt = $this->request->bearerToken();
        if (!$jwt) {
            return null;
        }

        $decoded = $this->JWTVerifier->decode($jwt);
        if ($decoded) {
            return $this->userProvider->retrieveByCredentials([
                'cognito_sub' => $decoded->sub,
            ]);
        }

        return null;
    }

    public function validate(array $credentials = [])
    {
        throw new \RuntimeException('Cognito guard cannot be used for credential based authentication.');
    }
}

<?php

namespace App\Services\Cognito;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;

class JWTVerifier
{
    public function decode(string $jwt)
    {
        $tks = explode('.', $jwt);
        if (count($tks) !== 3) {
            return null;
        }
        [$headb64, $_, $_] = $tks;

        $jwks = $this->fetchJWKs();
        try {
            $kid = $this->getKid($headb64);
            $jwk = $this->getJWK($jwks, $kid);
            $alg = $this->getAlg($jwks, $kid);

            return JWT::decode($jwt, $jwk, [$alg]);
        } catch (\RuntimeException $exeption) {
            return null;
        }
    }

    private function getKid(string $headb64)
    {
        $headb64 = json_decode(JWT::urlsafeB64Decode($headb64), true);
        if (isset($headb64['kid'])) {
            return $headb64['kid'];
        }
        throw new \RuntimeException();
    }

    private function getJWK(array $jwks, string $kid)
    {
        $keys = JWK::parseKeySet($jwks);
        if (isset($keys[$kid])) {
            return $keys[$kid];
        }
        throw new \RuntimeException();
    }

    private function getAlg(array $jwks, string $kid)
    {
        if (!isset($jwks['keys'])) {
            throw new \RuntimeException();
        }

        foreach ($jwks['keys'] as $key) {
            if ($key['kid'] === $kid && isset($key['alg'])) {
                return $key['alg'];
            }
        }
        throw new \RuntimeException();
    }

    private function fetchJWKs()
    {
        $certificateUrl = sprintf(
            'https://cognito-idp.%s.amazonaws.com/%s/.well-known/jwks.json',
            env('AWS_DEFAULT_REGION'),
            env('AWS_COGNITO_USER_POOL_ID')
        );
        $response = Http::get($certificateUrl);
        return json_decode($response->getBody()->getContents(), true) ?: [];
    }
}

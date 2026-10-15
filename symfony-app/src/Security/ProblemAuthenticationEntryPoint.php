<?php

namespace App\Security;

use App\Service\Problem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * `#/components/responses/Unauthorized` with an HTTP basic challenge.
 */
class ProblemAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return Problem::response(
            Response::HTTP_UNAUTHORIZED,
            'Valid HTTP basic credentials are required.',
            headers: ['WWW-Authenticate' => 'Basic realm="Museum API"'],
        );
    }
}

<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\MicrosoftSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationController extends Controller
{
    /**
     * Registra cliente no banco de dados da api.
     */
    function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|confirmed|string',
            'password_confirmation' => 'required|string'
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password'])
        ]);

        return response()->json(
            [
                'token' => $user->createToken($user->email)->plainTextToken,
            ],
            Response::HTTP_ACCEPTED
        );
    }

    /**
     * Efetua login na api
     */
    function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::query()
            ->where('email', $data['email'])
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password))
            return response()->json(
                [
                    'Invalid email or password.',
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );

        return response()->json(
            [
                'token' => $user->createToken($user->email)->plainTextToken,
            ],
            Response::HTTP_ACCEPTED
        );
    }

    /**
     * Authenticacao do usuario para utilizacao de Microsoft Graph
     */
    public function signin(Request $request)
    {
        $user = $request->user();

        // Checa se o usuario ja possui um token autenticado.
        if (!empty($user->ms_token))
            return response()->json(
                [
                    'status' => 'You already has authentication.'
                ],
                Response::HTTP_NOT_ACCEPTABLE
            );

        // Inicia cliente oAuth
        $oaClient = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => config('azure.appId'),
            'clientSecret'            => config('azure.appSecret'),
            'redirectUri'             => config('azure.redirectUri'),
            'urlAuthorize'            => config('azure.authority') . config('azure.authorizeEndpoint'),
            'urlAccessToken'          => config('azure.authority') . config('azure.tokenEndpoint'),
            'urlResourceOwnerDetails' => '',
            'scopes'                  => config('azure.scopes')
        ]);

        // Pega a utl de autenticacao.
        $url = $oaClient->getAuthorizationUrl();

        // Por questoes de seguranca, apaga todas as tentativas de autenticacao do usuario.
        MicrosoftSession::where('user_id', $user->id)->delete();

        // Cria uma sessao de autenticacao na tabela microsoft_sessions.
        MicrosoftSession::create([
            'session' => $oaClient->getState(),
            'user_id' => $user->id
        ]);

        // Retorna a url de authenticacao do usuario.
        return response()->json(
            [
                'url' => $url
            ],
            Response::HTTP_OK
        );
    }

    /**
     * Gerencia o retorno do callback do Microsoft Graph
     */
    public function callback(Request $request)
    {
        $session = $request->query('state');

        // Busca a sessao no banco de dados.
        $ms_session = MicrosoftSession::query()
            ->where('session', $session)
            ->first();

        // Por questoes de seguranca, se a sessao nao existir ou possui mais de 5 minutos ela
        // vai ser apagada e solicitada nova tentativa.
        if (!$ms_session || Carbon::now()->diff($ms_session->created_at, true)->i >= 5) {
            if ($ms_session)
                $ms_session->delete();

            return response()->json(
                [
                    'status' => 'Session expired, try again.'
                ],
                Response::HTTP_NOT_ACCEPTABLE
            );
        }

        $code = $request->query('code');

        if (isset($code)) {
            // Inicia cliente oAuth
            $oaClient = new \League\OAuth2\Client\Provider\GenericProvider([
                'clientId'                => config('azure.appId'),
                'clientSecret'            => config('azure.appSecret'),
                'redirectUri'             => config('azure.redirectUri'),
                'urlAuthorize'            => config('azure.authority') . config('azure.authorizeEndpoint'),
                'urlAccessToken'          => config('azure.authority') . config('azure.tokenEndpoint'),
                'urlResourceOwnerDetails' => '',
                'scopes'                  => config('azure.scopes')
            ]);

            try {
                // Solicita o token do Microsoft Graph
                $accessToken = $oaClient->getAccessToken('authorization_code', [
                    'code' => $code
                ]);

                // Salva token no usuario e exlui a sessao do banco de dados.
                $user = $ms_session->user;
                $ms_session->delete();
                $user->ms_token = $accessToken->getToken();
                $user->save();

            } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
                return response()->json(
                    [
                        'status' => 'IdentityProviderException',
                        'error' => $e->getResponseBody()
                    ],
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        }

        // Retorna status de sucesso.
        return response()->json(
            [
                'status' => 'Token saved successfully.'
            ],
            Response::HTTP_ACCEPTED
        );
    }

    /**
     * Exclui token do Microsoft Graph do usuario.
     */
    public function signout(Request $request)
    {
        $user = $request->user();
        $user->ms_token = null;

        $user->save();

        return response()->json(
            [
                'status' => 'Token deleted successfully.'
            ],
            Response::HTTP_ACCEPTED
        );
    }
}

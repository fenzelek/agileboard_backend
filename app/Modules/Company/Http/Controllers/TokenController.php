<?php

namespace App\Modules\Company\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Db\CompanyToken;
use App\Modules\Company\Http\Requests\Token as TokenRequest;
use App\Services\Paginator;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    /**
     * Display list of tokens.
     *
     * @param Paginator $paginator
     * @param Guard $auth
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Paginator $paginator, Guard $auth, Request $request)
    {
        $query = CompanyToken::with('role')
            ->inCompany($auth->user());
        if ($user_id = $request->input('user_id')) {
            $query->where('user_id', $user_id);
        }

        $tokens = $paginator->get($query, 'token.index');

        return ApiResponse::responseOk($tokens);
    }

    /**
     * Create new token.
     *
     * @param TokenRequest $request
     * @param Guard $auth
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(TokenRequest $request, Guard $auth)
    {
        $token = CompanyToken::create(
            $request->only('role_id', 'user_id', 'domain', 'ip_from', 'ip_to', 'ttl')
            + [
                'company_id' => $auth->user()->getSelectedCompanyId(),
                'token' => str_random(mt_rand(200, 255)),
            ]
        );

        return ApiResponse::responseOk($token->fresh());
    }

    /**
     * Delete token.
     *
     * @param Guard $auth
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Guard $auth, $id)
    {
        CompanyToken::inCompany($auth->user())->findOrFail($id)->delete();

        return ApiResponse::responseOk([], 204);
    }
}

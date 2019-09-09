<?php

namespace App\Extensions\Auth;

use Illuminate\Support\Str;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Repositories\Eloquent\MemberRepository;

/**
 * 擴充 Laravel 身分驗證機制
 *
 * @package App\Extensions
 */
class IesUserProvider implements UserProvider
{
    /**
     * The token repository instance.
     *
     * @var \App\Repositories\Eloquent\MemberRepository
     */
    protected $repository;

    /**
     * The Eloquent user model.
     *
     * @var string
     */
    protected $model;

    /**
     * IesUserProvider constructor.
     *
     * @param MemberRepository $repository
     */
    public function __construct(MemberRepository $repository, $model)
    {
        $this->repository = $repository;
        $this->model = $model;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param mixed $identifier
     *
     * @return Authenticatable|mixed|null
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function retrieveById($identifier)
    {
        return $this->repository->findForUser($identifier);
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed   $identifier
     * @param  string  $token
     */
    public function retrieveByToken($identifier, $token) {}

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $token
     */
    public function updateRememberToken(Authenticatable $user, $token) {}

    /**
     * Retrieve a user by the given credentials.
     *
     * @param array $credentials
     *
     * @return Authenticatable|\Illuminate\Database\Eloquent\Model|null|void|static
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials) ||
            (count($credentials) === 1 &&
                array_key_exists('password', $credentials))) {
            return;
        }

        if (method_exists($this->model, 'findForPassport')) {
            if (!array_key_exists('username', $credentials)) {
                return;
            }
            $user = $this->createModel()->findForPassport($credentials['username']);
        } else {
            // First we will add each credential element to the query as a where clause.
            // Then we can execute the query and, if we found a user, return it in a
            // Eloquent User "model" that will be utilized by the Guard instances.
            $query = $this->createModel()->newQuery();

            foreach ($credentials as $key => $value) {
                if (! Str::contains($key, 'password')) {
                    $query->where($key, $value);
                }
            }

            $user = $query->first();
        }

        return $user;
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param Authenticatable $user
     * @param array $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $plain = $credentials['password'];

        if (method_exists($user, 'validateForPassportPasswordGrant')) {
            return $user->validateForPassportPasswordGrant($plain);
        }

        return $this->hasher->check($plain, $user->getAuthPassword());
    }

    /**
     * Create a new instance of the model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createModel()
    {
        $class = '\\'.ltrim($this->model, '\\');

        return new $class;
    }
}

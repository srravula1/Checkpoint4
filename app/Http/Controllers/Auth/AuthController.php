<?php

namespace Techademia\Http\Controllers\Auth;

use Socialite;
use Validator;
use Techademia\User;
use Illuminate\Http\Request;
use Techademia\AuthenticateUser;
use Illuminate\Support\Facades\Auth;
use Techademia\Http\Controllers\Controller;
use Techademia\Repositories\UserRepository;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;


class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */
    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    private $repository;
    /**
     * @param UserRepository $repository
     */
    public function __construct(UserRepository $repository)
    {
        $this->middleware('guest', ['except' => 'getLogout']);
        $this->repository = $repository;
    }

    /**
     * Returns user registration view
     *
     * @param none
     * @return
     */
    public function getRegister()
    {
        $user = Auth::user();
        return view('pages.register', compact('user'));
    }


    /**
     * Returns the login view
     *
     * @return
     */
    public function getLogin(Request $request)
    {
        $user = Auth::user();
        return view('pages.login', compact('user'));
    }

    /**
     * Handles login
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function postLogin(Request $request)
    {
        $v = Validator::make($request->all(), [
            'email'         => 'required',
            'password'      => 'required',
        ]);

        $authStatus = Auth::attempt($request->only(['email', 'password']));

        if (! $authStatus) {
            return redirect()->back()->with('warning', 'Credentials supplied do not match our records.');
        }

        return redirect('/');
    }

    /**
     * Log out current user
     *
     * @return
     */
    public function getLogout()
    {
        Auth::logout();
        return redirect('/');
    }


    /**
     * Handles user registration
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function postRegister(Request $request)
    {
        $v = Validator::make($request->all(), [
            'fullname'      => 'required',
            'username'      => 'required|max:255',
            'occupation'    => 'required',
            'email'         => 'required|email|unique:users',
            'password'      => 'required',
        ]);

        if ($v->fails()) {
            return redirect()->back()->withErrors($v->errors());
        }

        $data = $request->all();
        $data['password'] = bcrypt($request->input('password'));

        User::create($data);

        return redirect('/auth/login');
    }



    public function doSocial(AuthenticateUser $authenticate, Request $request, $provider = null)
    {
        return $authenticate->execute($request->all(), $this, $provider) ;
    }

    public function userAuthenticated($user)
    {
        $authUser = $this->repository->findBySocialIdOrCreate($user);
        Auth::login($authUser, true);
        return redirect()->route('dashboard');
    }
}
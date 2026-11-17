<?php
namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use App\Services\CartService;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;

class AuthController extends Controller
{
    public function loginForm() { return view('auth.login', ['seo' => Seo::make(['title' => 'Login', 'noindex' => true])]); }
    public function registerForm() { return view('auth.register', ['seo' => Seo::make(['title' => 'Create Account', 'noindex' => true])]); }

    private function afterLogin(Request $r, string $oldSession)
    {
        $uid = Auth::id();
        app(CartService::class)->mergeGuestCart($oldSession, $uid);
        WishlistController::mergeGuest($oldSession, $uid);
        Auth::user()->forceFill(['last_login_at' => now()])->save();
    }

    public function login(Request $r)
    {
        $d = $r->validate(['email' => 'required|email', 'password' => 'required']);
        $old = $r->session()->getId();
        if (!Auth::attempt(['email' => $d['email'], 'password' => $d['password'], 'is_active' => true], $r->boolean('remember'))) {
            return back()->withInput($r->only('email'))->withErrors(['email' => 'These credentials do not match our records.']);
        }
        $r->session()->regenerate();
        if ($r->input('to') === 'admin' && !Auth::user()->isStaff()) {
            Auth::logout(); $r->session()->invalidate(); $r->session()->regenerateToken();
            return back()->withErrors(['email' => 'This account does not have admin access.']);
        }
        $this->afterLogin($r, $old);
        return Auth::user()->isStaff() && $r->input('to') === 'admin' ? redirect()->route('admin.dashboard') : redirect()->intended(route('account'));
    }

    public function register(Request $r)
    {
        $d = $r->validate([
            'name' => 'required|string|max:100', 'email' => 'required|email|max:150|unique:users,email',
            'phone' => 'nullable|string|max:32', 'password' => 'required|string|min:8|confirmed',
        ]);
        $old = $r->session()->getId();
        $user = User::create(['name' => $d['name'], 'email' => strtolower($d['email']), 'phone' => $d['phone'] ?? null, 'password' => $d['password']]);
        // link any guest order history placed with the same email
        $customer = Customer::where('email', $user->email)->whereNull('user_id')->first();
        $customer ? $customer->update(['user_id' => $user->id, 'name' => $user->name]) : Customer::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'phone' => $user->phone]);
        \App\Models\Order::where('customer_email', $user->email)->whereNull('user_id')->update(['user_id' => $user->id]);
        Auth::login($user);
        $r->session()->regenerate();
        $this->afterLogin($r, $old);
        return redirect()->route('account')->with('status', 'Welcome to Turtle Boss!');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect('/');
    }

    public function forgotForm() { return view('auth.forgot', ['seo' => Seo::make(['title' => 'Forgot Password', 'noindex' => true])]); }
    public function sendReset(Request $r)
    {
        $r->validate(['email' => 'required|email']);
        Password::sendResetLink($r->only('email'));
        return back()->with('status', 'If that email exists, a reset link has been sent.'); // no account enumeration
    }
    public function resetForm(Request $r, string $token) { return view('auth.reset', ['token' => $token, 'email' => $r->email, 'seo' => Seo::make(['title' => 'Reset Password', 'noindex' => true])]); }
    public function reset(Request $r)
    {
        $r->validate(['token' => 'required', 'email' => 'required|email', 'password' => 'required|min:8|confirmed']);
        $status = Password::reset($r->only('email', 'password', 'password_confirmation', 'token'), function ($user, $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });
        return $status === Password::PASSWORD_RESET ? redirect()->route('login')->with('status', 'Password reset. You can log in now.') : back()->withErrors(['email' => __($status)]);
    }
}

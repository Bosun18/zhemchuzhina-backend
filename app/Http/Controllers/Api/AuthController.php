<?php

namespace App\Http\Controllers\Api;

use App\Filament\Resources\Users\UserResource;
use App\Http\Controllers\Controller;
use App\Mail\NewUserRegistered;
use App\Mail\Welcome;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'city' => 'required|string|max:255',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'city' => $data['city'],
        ]);

        $user->assignRole('guest');

        activity()->useLog('user')
            ->performedOn($user)
            ->causedBy($user)
            ->log('Зарегистрировался');

        Mail::to($user->email)->send(new Welcome($user));

        $user->notify(new UserNotification(
            title: 'Добро пожаловать!',
            body: 'Спасибо за регистрацию в гостевом доме «Жемчужина».',
            icon: 'heroicon-o-sparkles',
            color: 'success',
        ));

        foreach (Setting::get('notification_emails_registration', []) as $email) {
            Mail::to($email)->send(new NewUserRegistered($user));
        }

        $staffNotification = new UserNotification(
            title: 'Новый пользователь зарегистрировался',
            body: "{$user->name} ({$user->email}) зарегистрировался на сайте.",
            icon: 'heroicon-o-user-plus',
            color: 'info',
            url: UserResource::getUrl('edit', ['record' => $user]),
        );

        foreach (User::role('admin')->get() as $admin) {
            $admin->notify($staffNotification);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'city' => $user->city,
                'role' => $user->getRoleNames()->first(),
            ],
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль.'],
            ]);
        }

        activity()->useLog('user')
            ->performedOn($user)
            ->causedBy($user)
            ->log('Вошёл в систему');

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'city' => $user->city,
                'role' => $user->getRoleNames()->first(),
            ],
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Выход выполнен успешно']);
    }
}

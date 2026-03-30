// app/Actions/Fortify/AuthenticateUser.php
use Laravel\Fortify\Fortify;

Fortify::authenticateUsing(function (Request $request) {
    // Sanitize before it even hits the DB
    $request->validate([
        'email'    => ['required', 'string', 'email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'max:128'],
    ]);

    $user = User::where('email', $request->email)->first();

    if ($user && Hash::check($request->password, $user->password)) {
        return $user;
    }
});
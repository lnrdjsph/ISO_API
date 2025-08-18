<!DOCTYPE html>
<html lang="en">

		<head>
				<meta charset="UTF-8">
				<meta
						name="viewport"
						content="width=device-width, initial-scale=1.0"
				>
				<title>{{ config('app.name') }} - Login</title>
				<link
						rel="icon"
						type="image/png"
						href="{{ asset('images/MarengEms_Logo.png') }}"
				>
				@vite('resources/css/app.css')
		</head>

		<body class="antialiased">
				{{ $slot }}
		</body>

</html>

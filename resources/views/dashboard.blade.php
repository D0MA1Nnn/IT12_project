<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard - Senador Coco Lumber and Construction Supply</title>
</head>

<body>

    <h1>Dashboard</h1>

    <p>
        Welcome,
        <strong>{{ Auth::user()->username }}</strong>
    </p>

    <p>
        Role:
        <strong>{{ Auth::user()->role }}</strong>
    </p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf

        <button type="submit">
            Logout
        </button>
    </form>

</body>
</html>
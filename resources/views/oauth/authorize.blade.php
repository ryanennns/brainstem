<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Authorize MCP client</title>
    </head>
    <body>
        <main>
            <h1>Authorize MCP client</h1>
            <p>{{ $client['client_name'] }} is requesting access for {{ $user->email }}.</p>

            <ul>
                @foreach ($scopes as $scope)
                    <li>{{ $scope }}</li>
                @endforeach
            </ul>

            <form method="POST" action="{{ route('mcp.oauth.approve') }}">
                @csrf
                <input type="hidden" name="authorization_token" value="{{ $authToken }}">
                <button type="submit" name="decision" value="approve">Approve</button>
                <button type="submit" name="decision" value="deny">Deny</button>
            </form>
        </main>
    </body>
</html>

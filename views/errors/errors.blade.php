<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body { text-align: center; padding: 50px; font-family: sans-serif; }
        h1 { font-size: 50px; color: #333; }
        p { font-size: 20px; color: #666; }
        a { text-decoration: none; color: blue; font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ $code }}</h1>
    <h2>{{ $title }}</h2>
    <p>{{ $errorMessage }}</p>
    <br>
    <a href="{{ $base_path }}/">메인으로 돌아가기</a>
</body>
</html>
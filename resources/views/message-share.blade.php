<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $shareUrl }}">

    <meta property="og:type" content="article">
    <meta property="og:site_name" content="LFC-JAHI MEDIA">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $shareUrl }}">
    <meta property="og:image" content="{{ $image }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $image }}">
    <meta name="twitter:url" content="{{ $shareUrl }}">

    <meta http-equiv="refresh" content="0;url={{ $frontendUrl }}">
    <script>
        window.location.replace(@json($frontendUrl));
    </script>
</head>
<body>
    <main style="font-family: Arial, sans-serif; padding: 24px; max-width: 720px; margin: 0 auto;">
        <h1 style="margin-bottom: 12px;">{{ $title }}</h1>
        <p style="margin: 0 0 8px;">{{ $description }}</p>
        @if ($speaker !== '' || $service !== '' || $date !== '')
            <p style="margin: 0 0 16px; color: #555;">
                {{ implode(' | ', array_values(array_filter([$speaker, $service, $date]))) }}
            </p>
        @endif
        <p style="margin: 0;">
            <a href="{{ $frontendUrl }}">Open message</a>
        </p>
    </main>
</body>
</html>

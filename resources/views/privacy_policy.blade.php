<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $page->title ?? 'Privacy Policy' }}</title>

    <style>
        body {
            background-color: #2953A5;
            color: #ffffff;
            font-family: Georgia, serif;
            line-height: 1.6;
            margin: 0;
            padding: 40px 20px;
        }

        main {
            max-width: 1000px;
            margin: auto;
            background-color: #ffffff;
            color: black;
            padding: 40px;
            border-radius: 8px;
        }

        h1,
        h2,
        h3 {
            color: black;
            font-weight: 700;
        }

        h1 {
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 0.5rem;
        }

        h2 {
            font-size: 1.4rem;
            margin-top: 2rem;
            border-bottom: 1px solid #2953A5;
            padding-bottom: 0.2rem;
            text-transform: uppercase;
        }

        a {
            color: black;
            font-weight: 600;
            text-decoration: underline;
        }

        hr {
            border: none;
            border-bottom: 1px solid #2953A5;
            margin: 1.5rem 0;
            opacity: 0.3;
        }
    </style>

</head>

<body>

<main>

    <h1>{{ $page->title ?? 'Privacy Policy' }}</h1>

    {!! $page->page_content !!}

</main>

</body>

</html>
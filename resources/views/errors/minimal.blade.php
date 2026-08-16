<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title')</title>

        {{-- Fonts come from Google rather than our own build: if this page is showing, the app's
             assets cannot be assumed to work. display=swap keeps text readable if the fetch fails. --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Kanit:wght@400&family=Work+Sans:wght@400&display=swap">

        <style>
            :root {
                --w2f-bg: #161925;
                --w2f-panel: #2f3549;
                --w2f-primary: #ddb81c;
                --w2f-primary-hover: #c9a719;
                --w2f-text: #e4f0ff;
                --w2f-offwhite: #fffdf7;
                --w2f-muted: #6c757d;
                --w2f-error: #ff8a85;
            }

            *, *::before, *::after {
                box-sizing: border-box;
            }

            html, body {
                height: 100%;
            }

            body {
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0;
                padding: 2rem 1rem;
                background-color: var(--w2f-bg);
                color: var(--w2f-text);
                font-family: 'Work Sans', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                font-size: .875rem;
                line-height: 1.6;
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
            }

            .error {
                width: 100%;
                max-width: 36rem;
            }

            .error-icon {
                display: block;
                width: 100%;
                max-width: 11rem;
                height: auto;
                margin: 0 auto 1.75rem;
            }

            .error-icon-trail,
            .error-icon-ground {
                fill: none;
                stroke: var(--w2f-muted);
                stroke-width: 2;
                stroke-linecap: round;
            }

            .error-icon-trail {
                stroke-dasharray: 5 6;
            }

            .error-icon-debris {
                fill: var(--w2f-muted);
                opacity: .55;
            }

            .error-icon-body {
                fill: none;
                stroke: var(--w2f-muted);
                stroke-width: 2;
                stroke-linecap: round;
                stroke-linejoin: round;
            }

            .error-headline {
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: 'Kanit', 'Work Sans', sans-serif;
                text-align: center;
            }

            .error-code {
                padding-right: 1rem;
                border-right: 1px solid var(--w2f-panel);
                color: var(--w2f-primary);
                font-size: 1.75rem;
                line-height: 1;
                letter-spacing: .05em;
            }

            .error-message {
                padding-left: 1rem;
                font-size: 1.125rem;
                letter-spacing: .05em;
                text-transform: uppercase;
            }

            @media (max-width: 30rem) {
                .error-headline {
                    flex-direction: column;
                }

                .error-code {
                    padding: 0 0 .5rem;
                    border-right: 0;
                    border-bottom: 1px solid var(--w2f-panel);
                }

                .error-message {
                    padding: .5rem 0 0;
                }
            }
        </style>
    </head>
    <body>
        <main class="error">
            {{-- 500 overrides this with its own icon; everything else gets the lost one. --}}
            @hasSection('icon')
                @yield('icon')
            @else
                @include('errors.icons.lost')
            @endif

            <div class="error-headline">
                <span class="error-code">@yield('code')</span>
                <span class="error-message">@yield('message')</span>
            </div>

            @yield('feedback')
        </main>

        @yield('scripts')
    </body>
</html>

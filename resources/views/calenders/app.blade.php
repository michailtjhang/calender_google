<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Calender Google</title>
    
    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    @vite('resources/css/app.css')
</head>

<body class="bg-base-200">
    <div id="app">
        <nav class="bg-base-100 shadow-lg">
            <div class="container mx-auto flex justify-between items-center px-4 py-2">
                <a class="text-xl font-bold text-primary" href="{{ url('/') }}">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <button class="btn btn-square btn-ghost lg:hidden" id="navbar-toggler">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                </button>
            </div>
        </nav>

        <main class="py-6">
            @yield('content')
        </main>
    </div>

    <script>
        document.getElementById('navbar-toggler').addEventListener('click', function() {
            const navContent = document.getElementById('navbarSupportedContent');
            navContent.classList.toggle('hidden');
        });
    </script>
</body>

</html>

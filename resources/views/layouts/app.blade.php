<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart Product Table</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
  <!-- Navbar -->
  <nav class="bg-white shadow-md py-4 px-6">
    <h1 class="text-xl font-bold text-gray-800">
        <a href="{{ route('products.index') }}">
            Product Management
        </a>
    </h1>
  </nav>
  <!-- Main Content -->
  <div class="container mx-auto p-6">
    @yield('content')
  </div>


</body>
</html>

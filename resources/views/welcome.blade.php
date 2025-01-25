<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KORICHA - Premium Leather Goods</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        saddle: '#8b4513',
                        coffee: '#5e2f0d',
                        latte: '#d2b48c',
                        cream: '#f5e5d5',
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-cream">
    <main>
        <!-- Hero Section -->
        <section class="container mx-auto px-4 py-16">
            <div class="grid gap-8 md:grid-cols-2 md:items-center">
                <div class="space-y-6">
                    <div class="mb-4">
                        <h1 class="animate-[pulse_3s_ease-in-out_infinite] bg-gradient-to-r from-coffee via-saddle to-latte bg-clip-text text-6xl font-extrabold text-transparent md:text-8xl">
                            KORICHA
                        </h1>
                    </div>
                    <h1 class="text-4xl font-bold text-saddle md:text-5xl">Discover Premium Products In Our Ecommerce</h1>
                    <p class="text-lg text-coffee">
                        Explore our collection of handcrafted leather products, inspired by the timeless elegance of horse saddles.
                    </p>
                    <a href="#" class="inline-flex items-center rounded-full bg-saddle px-6 py-3 text-white transition-colors hover:bg-latte hover:text-saddle">
                        Shop Now
                        <svg xmlns="http://www.w3.org/2000/svg" class="ml-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
                <div>
                    <img src="{{ asset('images/hero-image.png') }}" alt="Premium leather goods" class="rounded-lg shadow-2xl">
                </div>
            </div>
        </section>

        <!-- Featured Products -->
        <section class="bg-latte py-16">
            <div class="container mx-auto px-4">
                <h2 class="mb-8 text-center text-3xl font-bold text-coffee">Featured Products</h2>
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    @php
                    $products = [
                        ['name' => 'Classic Leather Wallet', 'price' => '$79.99'],
                        ['name' => 'Vintage Messenger Bag', 'price' => '$149.99'],
                        ['name' => 'Handcrafted Belt', 'price' => '$59.99'],
                        ['name' => 'Leather Watch Strap', 'price' => '$39.99'],
                    ];
                    @endphp

                    @foreach ($products as $product)
                    <div class="overflow-hidden rounded-lg bg-white p-4 shadow-lg">
                        <img src="{{ asset('images/products/' . Str::slug($product['name']) . '.jpg') }}" alt="{{ $product['name'] }}" class="mb-4 h-48 w-full object-cover">
                        <h3 class="text-lg font-semibold text-saddle">{{ $product['name'] }}</h3>
                        <p class="text-coffee">{{ $product['price'] }}</p>
                        <div class="mt-2 flex items-center">
                            @for ($i = 0; $i < 5; $i++)
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            @endfor
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section class="container mx-auto px-4 py-16">
            <div class="grid gap-8 md:grid-cols-2 md:items-center">
                <div>
                    <img src="{{ asset('images/craftsmanship.jpg') }}" alt="Leather craftsmanship" class="rounded-lg shadow-2xl">
                </div>
                <div class="space-y-6">
                    <h2 class="text-3xl font-bold text-saddle">Our Craftsmanship</h2>
                    <p class="text-lg text-coffee">
                        At KORICHA, we blend traditional techniques with modern design to create leather goods that stand the test of time. Each piece is meticulously crafted to ensure the highest quality and durability.
                    </p>
                    <a href="#" class="inline-flex items-center rounded-full bg-saddle px-6 py-3 text-white transition-colors hover:bg-latte hover:text-saddle">
                        Learn More
                        <svg xmlns="http://www.w3.org/2000/svg" class="ml-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </div>
        </section>

        <!-- Newsletter -->
        <section class="bg-saddle py-16 text-white">
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="mb-4 text-3xl font-bold">Join Our Newsletter</h2>
                    <p class="mb-8">Stay updated with our latest products and exclusive offers.</p>
                    <form action="" method="POST" class="flex flex-col space-y-4 sm:flex-row sm:space-x-4 sm:space-y-0">
                        @csrf
                        <input type="email" name="email" placeholder="Enter your email" class="w-full rounded-full bg-white px-4 py-2 text-saddle" required>
                        <button type="submit" class="rounded-full bg-latte px-6 py-2 text-saddle transition-colors hover:bg-cream">
                            Subscribe
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-coffee py-8 text-white">
        <div class="container mx-auto px-4">
            <div class="grid gap-8 sm:grid-cols-2 md:grid-cols-4">
                <div>
                    <h3 class="mb-4 font-semibold">Shop</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-latte">Wallets</a></li>
                        <li><a href="#" class="hover:text-latte">Bags</a></li>
                        <li><a href="#" class="hover:text-latte">Belts</a></li>
                        <li><a href="#" class="hover:text-latte">Accessories</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="mb-4 font-semibold">About</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-latte">Our Story</a></li>
                        <li><a href="#" class="hover:text-latte">Craftsmanship</a></li>
                        <li><a href="#" class="hover:text-latte">Sustainability</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="mb-4 font-semibold">Customer Service</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-latte">Contact Us</a></li>
                        <li><a href="#" class="hover:text-latte">Shipping & Returns</a></li>
                        <li><a href="#" class="hover:text-latte">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="mb-4 font-semibold">Follow Us</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="hover:text-latte">
                            <span class="sr-only">Facebook</span>
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" />
                            </svg>
                        </a>
                        <a href="#" class="hover:text-latte">
                            <span class="sr-only">Instagram</span>
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd" />
                            </svg>
                        </a>
                        <a href="#" class="hover:text-latte">
                            <span class="sr-only">Twitter</span>
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
            <div class="mt-8 border-t border-saddle pt-8 text-center">
                <p>&copy; {{ date('Y') }} KORICHA. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Add any custom JavaScript here
    </script>
</body>
</html>


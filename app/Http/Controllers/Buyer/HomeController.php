<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $categories = $this->sampleCategories();
        $bestSellers = $this->sampleProducts(8, 'bestseller');
        $recommended = $this->paginatedRecommended($request);

        return view('buyer.home', compact('categories', 'bestSellers', 'recommended'));
    }

    private function sampleCategories(): array
    {
        return [
            ['name' => 'Pet Supplies', 'icon' => 'paw', 'subcategories' => [
                'Dog Food & Treats',
                'Cat Litter & Accessories',
                'Aquariums & Fish Supplies',
                'Bird Feeders & Food',
                'Pet Grooming Products',
                'Pet Health & Wellness',
            ]],
            ['name' => 'Electronics and Gadgets', 'icon' => 'device', 'subcategories' => [
                'Mobile Phones & Accessories',
                'Laptops, Desktops & Monitors',
                'Audio & Video Equipment',
                'Smart Home Devices',
                'Cameras & Photography',
                'Wearable Technology',
            ]],
            ['name' => "Women's Apparel", 'icon' => 'dress', 'subcategories' => [
                'Dresses & Skirts',
                'Tops & Blouses',
                'Activewear & Yoga Pants',
                'Lingerie & Sleepwear',
                'Jackets & Coats',
                'Shoes & Accessories',
            ]],
            ['name' => "Men's Apparel", 'icon' => 'shirt', 'subcategories' => [
                'Suits & Blazers',
                'Casual Shirts & Pants',
                'Outerwear & Jackets',
                'Activewear & Fitness Gear',
                'Shoes & Accessories',
                'Grooming Products',
            ]],
            ['name' => 'Kids and Baby', 'icon' => 'baby', 'subcategories' => [
                'Baby Clothes & Accessories',
                'Toys & Games',
                'Educational Materials',
                'Strollers & Gear',
                'Nursery Furniture',
                'Safety and Health',
            ]],
            ['name' => 'Home and Garden', 'icon' => 'home', 'subcategories' => [
                'Kitchen Appliances',
                'Furniture & Decor',
                'Gardening Tools',
                'Outdoor Living',
                'Home Improvement Tools',
                'Bedding & Bath',
            ]],
            ['name' => 'Sports and Outdoors', 'icon' => 'ball', 'subcategories' => [
                'Fitness Equipment',
                'Camping & Hiking Gear',
                'Sports Apparel',
                'Cycling & Bikes',
                'Water Sports',
                'Team Sports Equipment',
            ]],
            ['name' => 'Health and Beauty', 'icon' => 'heart', 'subcategories' => [
                'Skincare Products',
                'Haircare Solutions',
                'Makeup & Cosmetics',
                'Personal Care Appliances',
                "Men's Grooming",
                'Health Supplements',
            ]],
            ['name' => 'Books and Media', 'icon' => 'book', 'subcategories' => [
                'Fiction & Non-Fiction Books',
                'Magazines & Periodicals',
                'Music CDs & Vinyl Records',
                'Movie DVDs & Blu-ray',
                'Video Games & Consoles',
                'Educational DVDs',
            ]],
            ['name' => 'Food and Gourmet', 'icon' => 'food', 'subcategories' => [
                'Baking Supplies & Ingredients',
                'Coffee, Tea & Beverages',
                'Snacks & Candy',
                'Specialty Foods & International Cuisine',
                'Organic and Health Foods',
                'Meal Kits & Prepped Foods',
            ]],
            ['name' => 'Automotive & Motorcycle', 'icon' => 'car', 'subcategories' => [
                'Protective Gear',
                'Maintenance & Repair Tools',
                'Parts & Accessories',
                'Electrical Components',
                'Tires, Wheels, and Fluids',
            ]],
            ['name' => 'Furniture and Office Equipment', 'icon' => 'office', 'subcategories' => [
                'Office Desks & Chairs',
                'Storage Cabinets & Shelving',
                'Conference & Meeting Furniture',
                'Computer Tables & Workstations',
                'Ergonomic Accessories',
                'Office Lighting & Fixtures',
            ]],
            ['name' => 'Jewelry and Watches', 'icon' => 'gem', 'subcategories' => [
                'Necklaces & Pendants',
                'Rings & Earrings',
                'Bracelets & Bangles',
                'Watches for Men & Women',
                'Fashion Jewelry',
                'Jewelry Storage & Care',
            ]],
            ['name' => 'Office and School Supplies', 'icon' => 'pencil', 'subcategories' => [
                'Notebooks & Paper Products',
                'Writing Instruments',
                'Office Furniture',
                'Printers & Printing Supplies',
                'School Bags & Backpacks',
                'Arts & Craft Materials',
            ]],
        ];
    }

    private function sampleProducts(int $count, string $seedPrefix = 'p'): array
    {
        $names = [
            'Wireless Bluetooth Earbuds',
            'Cotton Oversized T-Shirt',
            'Stainless Steel Water Bottle',
            'LED Desk Lamp',
            'Non-Stick Frying Pan Set',
            'Running Shoes Unisex',
            'Portable Power Bank 20000mAh',
            'Ceramic Coffee Mug Set',
            'Yoga Mat Non-Slip',
            'Skincare Set Vitamin C',
            'Backpack Waterproof 30L',
            'Mechanical Keyboard RGB',
        ];
        $locations = ['Metro Manila', 'Cebu City', 'Davao City', 'Cavite', 'Laguna', 'Bulacan'];

        $products = [];
        for ($i = 0; $i < $count; $i++) {
            $price = rand(150, 3500);
            $hasDiscount = rand(0, 1) === 1;

            $products[] = (object) [
                'id' => "{$seedPrefix}-{$i}",
                'name' => $names[array_rand($names)],
                'image_placeholder' => true, // TODO: real image_url once products exist
                'price' => $price,
                'original_price' => $hasDiscount ? $price + rand(50, 800) : null,
                'sold_count' => rand(12, 4800),
                'location' => $locations[array_rand($locations)],
                'rating' => round(rand(35, 50) / 10, 1),
            ];
        }

        return $products;
    }

    private function paginatedRecommended(Request $request): LengthAwarePaginator
    {
        $all = $this->sampleProducts(48, 'r');
        $perPage = 16;
        $page = LengthAwarePaginator::resolveCurrentPage();

        $items = array_slice($all, ($page - 1) * $perPage, $perPage);

        return new LengthAwarePaginator(
            $items,
            count($all),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }
}

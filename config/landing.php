<?php
return [

    'nav' => [
        ['label' => 'Home',         'href' => '#home'],
        ['label' => 'Categories',   'href' => '#categories'],
        ['label' => "Today's Deals", 'href' => '#deals'],
        ['label' => 'How It Works', 'href' => '#how-it-works'],
        ['label' => 'Riders',       'href' => '#riders'],
        ['label' => 'About',        'href' => '#about'],
    ],

    'trending_searches' => ['wireless earbuds', 'running shoes', 'skincare set', 'desk lamp', 'rice cooker'],

    // cta_target: a route name, "rider" (rider signup), or a "#anchor"
    'slides' => [
        [
            'eyebrow'    => 'New here? Get ₱150 off your first order',
            'headline'   => 'Everything you need, from sellers you can trust.',
            'copy'       => 'Browse thousands of listings across fashion, electronics, home, and more — with buyer protection on every order.',
            'cta'        => 'Start Shopping',
            'cta_target' => 'register',
            'image'      => 'Landing-1.png',
        ],
        [
            'eyebrow'    => 'Payday Sale · Sept 9–15',
            'headline'   => 'Up to 40% off home and living essentials.',
            'copy'       => 'Restock the kitchen, refresh the living room, or finally get that desk lamp — all with cash-on-delivery available.',
            'cta'        => 'See the Deals',
            'cta_target' => '#deals',
            'image'      => 'Landing-2.png',
        ],
        [
            'eyebrow'    => 'Now hiring riders nationwide',
            'headline'   => 'Deliver on your schedule, get paid weekly.',
            'copy'       => 'Use your own motorcycle or bike, choose your own hours, and start earning as a Shopleap rider.',
            'cta'        => 'Apply to Ride',
            'cta_target' => 'rider',
            'image'      => 'Landing-3.png',
        ],
    ],

    'trust' => [
        [
            'label' => 'Buyer Protection',
            'sub'   => "Refund if items don't arrive as described",
            'path'  => 'M12 3l7 3v6c0 4.5-3 8-7 9-4-1-7-4.5-7-9V6l7-3z',
            'bg'    => 'bg-emerald-50',
            'text'  => 'text-emerald-600',
        ],
        [
            'label' => 'Verified Sellers',
            'sub'   => 'Every seller account is reviewed',
            'path'  => 'M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'bg'    => 'bg-blue-50',
            'text'  => 'text-blue-600',
        ],
        [
            'label' => 'Secure Payments',
            'sub'   => 'GCash, Maya, cards, and cash on delivery',
            'path'  => 'M3 7h18M5 7v12h14V7M8 7V5a4 4 0 018 0v2',
            'bg'    => 'bg-purple-50',
            'text'  => 'text-purple-600',
        ],
        [
            'label' => 'Nationwide Delivery',
            'sub'   => 'Tracked shipping to any address',
            'path'  => 'M3 7h11v9H3zM14 10h4l3 3v3h-7zM7 19a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z',
            'bg'    => 'bg-orange-50',
            'text'  => 'text-orange-600',
        ],
    ],

    // slug = file name in public/images/categories (without .png)
    'categories' => [
        ['name' => 'Pet Supplies', 'slug' => 'pet-supplies', 'iconBg' => 'bg-amber-50',
            'subcategories' => ['Dog Food & Treats', 'Cat Litter & Accessories', 'Aquariums & Fish Supplies', 'Bird Feeders & Food', 'Pet Grooming Products', 'Pet Health & Wellness']],
        ['name' => 'Electronics and Gadgets', 'slug' => 'electronics-and-gadgets', 'iconBg' => 'bg-blue-50',
            'subcategories' => ['Mobile Phones & Accessories', 'Laptops, Desktops & Monitors', 'Audio & Video Equipment', 'Smart Home Devices', 'Cameras & Photography', 'Wearable Technology']],
        ['name' => "Women's Apparel", 'slug' => 'womens-apparel', 'iconBg' => 'bg-pink-50',
            'subcategories' => ['Dresses & Skirts', 'Tops & Blouses', 'Activewear & Yoga Pants', 'Lingerie & Sleepwear', 'Jackets & Coats', 'Shoes & Accessories']],
        ['name' => "Men's Apparel", 'slug' => 'mens-apparel', 'iconBg' => 'bg-indigo-50',
            'subcategories' => ['Suits & Blazers', 'Casual Shirts & Pants', 'Outerwear & Jackets', 'Activewear & Fitness Gear', 'Shoes & Accessories', 'Grooming Products']],
        ['name' => 'Kids and Baby', 'slug' => 'kids-and-baby', 'iconBg' => 'bg-yellow-50',
            'subcategories' => ['Baby Clothes & Accessories', 'Toys & Games', 'Educational Materials', 'Strollers & Gear', 'Nursery Furniture', 'Safety and Health']],
        ['name' => 'Home and Garden', 'slug' => 'home-and-garden', 'iconBg' => 'bg-emerald-50',
            'subcategories' => ['Kitchen Appliances', 'Furniture & Decor', 'Gardening Tools', 'Outdoor Living', 'Home Improvement Tools', 'Bedding & Bath']],
        ['name' => 'Sports and Outdoors', 'slug' => 'sports-and-outdoors', 'iconBg' => 'bg-orange-50',
            'subcategories' => ['Fitness Equipment', 'Camping & Hiking Gear', 'Sports Apparel', 'Cycling & Bikes', 'Water Sports', 'Team Sports Equipment']],
        ['name' => 'Health and Beauty', 'slug' => 'health-and-beauty', 'iconBg' => 'bg-fuchsia-50',
            'subcategories' => ['Skincare Products', 'Haircare Solutions', 'Makeup & Cosmetics', 'Personal Care Appliances', "Men's Grooming", 'Health Supplements']],
        ['name' => 'Books and Media', 'slug' => 'books-and-media', 'iconBg' => 'bg-violet-50',
            'subcategories' => ['Fiction & Non-Fiction Books', 'Magazines & Periodicals', 'Music CDs & Vinyl Records', 'Movie DVDs & Blu-ray', 'Video Games & Consoles', 'Educational DVDs']],
        ['name' => 'Food and Gourmet', 'slug' => 'food-and-gourmet', 'iconBg' => 'bg-red-50',
            'subcategories' => ['Baking Supplies & Ingredients', 'Coffee, Tea & Beverages', 'Snacks & Candy', 'Specialty Foods & International Cuisine', 'Organic and Health Foods', 'Meal Kits & Prepped Foods']],
        ['name' => 'Automotive & Motorcycle', 'slug' => 'automotive-and-motorcycle', 'iconBg' => 'bg-slate-100',
            'subcategories' => ['Protective Gear', 'Maintenance & Repair Tools', 'Parts & Accessories', 'Electrical Components', 'Tires, Wheels, and Fluids']],
        ['name' => 'Furniture and Office Equipment', 'slug' => 'furniture-and-office-equipment', 'iconBg' => 'bg-teal-50',
            'subcategories' => ['Office Desks & Chairs', 'Storage Cabinets & Shelving', 'Conference & Meeting Furniture', 'Computer Tables & Workstations', 'Ergonomic Accessories', 'Office Lighting & Fixtures']],
        ['name' => 'Jewelry and Watches', 'slug' => 'jewelry-and-watches', 'iconBg' => 'bg-amber-100',
            'subcategories' => ['Necklaces & Pendants', 'Rings & Earrings', 'Bracelets & Bangles', 'Watches for Men & Women', 'Fashion Jewelry', 'Jewelry Storage & Care']],
        ['name' => 'Office and School Supplies', 'slug' => 'office-and-school-supplies', 'iconBg' => 'bg-cyan-50',
            'subcategories' => ['Notebooks & Paper Products', 'Writing Instruments', 'Office Furniture', 'Printers & Printing Supplies', 'School Bags & Backpacks', 'Arts & Craft Materials']],
    ],

    'features' => [
        ['title' => 'Reviewed Sellers',     'desc' => 'Every seller completes an approval process before their store goes live.'],
        ['title' => 'Order Protection',     'desc' => "Full refund if an order doesn't arrive or doesn't match its listing."],
        ['title' => 'Real-Time Tracking',   'desc' => 'Follow every order from checkout to your doorstep, courier by courier.'],
        ['title' => 'Flexible Payments',    'desc' => 'Pay with GCash, Maya, major cards, or cash on delivery.'],
    ],

    'rider_benefits' => [
        'Flexible hours — ride whenever it works for you',
        'Weekly payouts straight to your GCash or bank account',
        'Simple application — no prior courier experience required',
        'In-app navigation and support for every delivery',
    ],

    'faqs' => [
        ['q' => 'How do I become a seller on Shopleap?',
            'a' => 'Tap "Become a Seller," fill in your business details, and submit for review. Every seller account is checked before it can list products.'],
        ['q' => 'How do I become a rider?',
            'a' => 'Tap "Ride with Shopleap," complete the application with your license and vehicle details, and our logistics team will reach out once it\'s reviewed.'],
        ['q' => 'What payment methods are supported?',
            'a' => 'GCash, Maya, major debit/credit cards, and cash on delivery, depending on the seller and delivery area.'],
        ['q' => 'Is my order protected if something goes wrong?',
            'a' => 'Yes — orders that don\'t arrive, or arrive not matching the listing, are covered under our buyer protection policy.'],
        ['q' => 'Can I track my delivery in real time?',
            'a' => 'Yes, every order can be tracked from checkout through to your doorstep, courier by courier.'],
    ],

    'payment_methods'  => ['GCash', 'Maya', 'Visa', 'Mastercard', 'COD'],
    'delivery_partners' => ['J&T', 'LBC', 'Ninja Van', 'Flash'],
];

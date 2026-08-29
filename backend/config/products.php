<?php
/**
 * ============================================================
 *  PRODUCT CATALOG
 *  Central product database. Each product has an id, name,
 *  price, description, image, category, and stock info.
 * ============================================================
 *  In production you would load this from your MySQL database.
 *  For simplicity with shared hosting, products are defined here.
 * ============================================================
 */

return [
    [
        'id' => 'cow-dung',
        'name' => 'Cow Dung Compost',
        'name_hi' => 'गोबर खाद',
        'price' => 299,
        'old_price' => 399,
        'category' => 'fertilizer',
        'image' => 'images/1Cow.png',
        'short_desc' => 'Nutrient-rich organic cow dung manure for all crops. Improves soil structure naturally.',
        'long_desc' => 'Our premium cow dung compost is fully decomposed and enriched with beneficial microbes. Perfect base fertilizer for vegetables, fruits, and field crops. Improves water retention and soil aeration.',
        'pack_size' => '5 kg',
        'unit' => 'bag',
        'stock' => 100,
        'featured' => true
    ],
    [
        'id' => 'vermi-compost',
        'name' => 'Vermicompost',
        'name_hi' => 'वर्मीकम्पोस्ट',
        'price' => 375,
        'old_price' => 450,
        'category' => 'fertilizer',
        'image' => 'images/2Vermi.png',
        'short_desc' => 'Premium earthworm compost rich in NPK and humus. Boosts soil fertility fast.',
        'long_desc' => 'High-quality vermicompost produced by earthworms, rich in nitrogen, phosphorus, potassium, calcium, and humic acid. Enhances nutrient uptake and promotes vigorous plant growth.',
        'pack_size' => '5 kg',
        'unit' => 'bag',
        'stock' => 150,
        'featured' => true
    ],
    [
        'id' => 'green-compost',
        'name' => 'Green Compost',
        'name_hi' => 'हरी खाद',
        'price' => 349,
        'old_price' => 0,
        'category' => 'fertilizer',
        'image' => 'images/3Green.png',
        'short_desc' => 'Balanced green compost with optimal carbon-nitrogen ratio for healthy soil.',
        'long_desc' => 'Green compost made from garden and crop residues with a balanced C:N ratio. Ideal for improving soil organic matter and microbial activity. Suitable for all farming systems.',
        'pack_size' => '10 kg',
        'unit' => 'bag',
        'stock' => 120,
        'featured' => true
    ],
    [
        'id' => 'jeevaamrut',
        'name' => 'Jeevaamrut',
        'name_hi' => 'जीवामृत',
        'price' => 299,
        'old_price' => 400,
        'category' => 'soil',
        'image' => 'images/Jeevaamrut1.jpg',
        'short_desc' => 'Traditional fermented microbial culture to boost soil life and fertility.',
        'long_desc' => 'Jeevaamrut is a traditional fermented bio-culture made from cow dung, urine, and natural ingredients. Adds billions of beneficial microbes to the soil, improving nutrient availability and plant immunity.',
        'pack_size' => '1 L',
        'unit' => 'bottle',
        'stock' => 200,
        'featured' => true
    ],
    [
        'id' => 'neem-oil',
        'name' => 'Neem Oil Pesticide',
        'name_hi' => 'नीम तेल',
        'price' => 420,
        'old_price' => 520,
        'category' => 'pesticide',
        'image' => 'images/Neem-Oil.jpg',
        'short_desc' => 'Cold-pressed neem oil for effective organic pest & fungal control.',
        'long_desc' => '100% pure cold-pressed neem oil. Effective against aphids, whiteflies, mealybugs, and fungal diseases. Safe for beneficial insects, humans, and the environment. Use as foliar spray diluted in water.',
        'pack_size' => '500 ml',
        'unit' => 'bottle',
        'stock' => 90,
        'featured' => true
    ],
    [
        'id' => 'bio-gas-fertilizer',
        'name' => 'Bio-Gas Slurry Fertilizer',
        'name_hi' => 'बायोगैस स्लरी खाद',
        'price' => 449,
        'old_price' => 0,
        'category' => 'biogas',
        'image' => 'images/Frame-Compost2.jpg',
        'short_desc' => 'Nutrient-rich organic fertilizer produced from biogas plant slurry.',
        'long_desc' => 'Our bio-gas fertilizer is the nutrient-rich byproduct of biogas digestion, rich in nitrogen, phosphorous, and potassium with high organic content. An excellent, low-cost organic fertilizer that improves soil health and reduces chemical dependence.',
        'pack_size' => '10 kg',
        'unit' => 'bag',
        'stock' => 80,
        'featured' => true
    ],
    [
        'id' => 'compost-blend',
        'name' => 'Premium Compost Blend',
        'name_hi' => 'प्रीमियम कम्पोस्ट',
        'price' => 499,
        'old_price' => 599,
        'category' => 'fertilizer',
        'image' => 'images/Frame-Compost7.jpg',
        'short_desc' => 'Multi-source compost blend for maximum soil enrichment.',
        'long_desc' => 'A premium blend of cow dung, vermi, green, and bio-gas composts. Provides a complete spectrum of nutrients and beneficial microbes for superior soil enrichment and higher crop yields.',
        'pack_size' => '15 kg',
        'unit' => 'bag',
        'stock' => 60,
        'featured' => true
    ],
    [
        'id' => 'organic-manure-pack',
        'name' => 'Organic Manure Pack',
        'name_hi' => 'जैविक खाद पैक',
        'price' => 649,
        'old_price' => 749,
        'category' => 'fertilizer',
        'image' => 'images/Frame-Compost9.jpg',
        'short_desc' => 'Complete 3-in-1 organic manure bundle for home & farm.',
        'long_desc' => 'Our complete organic manure bundle includes cow dung, vermicompost, and green compost in one convenient pack. Ideal for kitchen gardens, nurseries, and orchards.',
        'pack_size' => '20 kg',
        'unit' => 'bundle',
        'stock' => 50,
        'featured' => true
    ],
    [
        'id' => 'growth-promoter',
        'name' => 'GrowthVita Bio Stimulant',
        'name_hi' => 'ग्रोथविटा बायो',
        'price' => 349,
        'old_price' => 0,
        'category' => 'growth',
        'image' => 'images/Jeevaamrut1.jpg',
        'short_desc' => 'Bio-stimulant with amino acids & seaweed for vigorous growth.',
        'long_desc' => 'Liquid bio-stimulant enriched with amino acids, seaweed extract, and trace elements. Promotes root development, flowering, and overall plant vigor. Apply as foliar spray or soil drench.',
        'pack_size' => '500 ml',
        'unit' => 'bottle',
        'stock' => 110,
        'featured' => false
    ]
];

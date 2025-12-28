<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Notebooks
        $notebookCat = Category::where('slug', 'notebooks')->first();

        Product::create([
            'category_id' => $notebookCat->id,
            'name' => ['en' => 'Leather Journal', 'sk' => 'Kožený zápisník'],
            'description' => ['en' => 'Premium A5 notebook.', 'sk' => 'Prémiový zápisník formátu A5.'],
            'price' => 2499, // €24.99
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $notebookCat->id,
            'name' => ['en' => 'Sketchpad', 'sk' => 'Skicár'],
            'description' => ['en' => 'For your best ideas.', 'sk' => 'Pre vaše najlepšie nápady.'],
            'price' => 1250, // €12.50
            'is_active' => true,
        ]);

        // 2. Pens
        $penCat = Category::where('slug', 'pens')->first();

        Product::create([
            'category_id' => $penCat->id,
            'name' => ['en' => 'Gel Pen Black', 'sk' => 'Gélové pero čierne'],
            'description' => ['en' => 'Smooth writing experience.', 'sk' => 'Hladké písanie.'],
            'price' => 399, // €3.99
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $penCat->id,
            'name' => ['en' => 'Mechanical Pencil', 'sk' => 'Mechanická ceruzka'],
            'description' => ['en' => '0.5mm lead.', 'sk' => '0.5mm tuha.'],
            'price' => 550, // €5.50
            'is_active' => true,
        ]);

        // 3. Accessories
        $accCat = Category::where('slug', 'accessories')->first();

        Product::create([
            'category_id' => $accCat->id,
            'name' => ['en' => 'Desk Organizer', 'sk' => 'Organizér na stôl'],
            'description' => ['en' => 'Keep your desk tidy.', 'sk' => 'Udržujte si poriadok na stole.'],
            'price' => 1999, // €19.99
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $accCat->id,
            'name' => ['en' => 'Mouse Pad', 'sk' => 'Podložka pod myš'],
            'description' => ['en' => 'Ergonomic design.', 'sk' => 'Ergonomický dizajn.'],
            'price' => 800, // €8.00
            'is_active' => true,
        ]);
    }
}
